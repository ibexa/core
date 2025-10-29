<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\ImageAsset;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\FieldType\Image;
use Ibexa\Core\FieldType\ImageAsset\AssetMapper;
use Ibexa\Core\Repository\ContentTypeService;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Repository\Values\ContentType\FieldDefinition;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AssetMapperTest extends TestCase
{
    public const EXAMPLE_CONTENT_ID = 487;

    /** @var ContentService|MockObject */
    private $contentService;

    /** @var LocationService|MockObject */
    private $locationService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService|MockObject */
    private $contentTypeService;

    /** @var ConfigResolverInterface|MockObject */
    private $configResolver;

    /** @var array */
    private $mappings = [
        'content_type_identifier' => 'image',
        'content_field_identifier' => 'image',
        'name_field_identifier' => 'name',
        'parent_location_id' => 51,
    ];

    protected function setUp(): void
    {
        $this->contentService = $this->createMock(ContentService::class);
        $this->locationService = $this->createMock(LocationService::class);
        $this->contentTypeService = $this->createMock(ContentTypeService::class);
        $this->configResolver = $this->mockConfigResolver();
    }

    public function testCreateAsset(): void
    {
        $name = 'Example asset';
        $value = new Image\Value();
        $contentType = new ContentType();
        $languageCode = 'eng-GB';
        $contentCreateStruct = $this->createMock(ContentCreateStruct::class);
        $locationCreateStruct = new LocationCreateStruct();
        $contentDraft = new Content([
            'versionInfo' => new VersionInfo(),
        ]);
        $content = new Content();

        $this->contentTypeService
            ->expects(self::once())
            ->method('loadContentTypeByIdentifier')
            ->with($this->mappings['content_type_identifier'])
            ->willReturn($contentType);

        $this->contentService
            ->expects(self::once())
            ->method('newContentCreateStruct')
            ->with($contentType, $languageCode)
            ->willReturn($contentCreateStruct);

        $contentCreateStruct
            ->expects(self::at(0))
            ->method('setField')
            ->with($this->mappings['name_field_identifier'], $name);

        $contentCreateStruct
            ->expects(self::at(1))
            ->method('setField')
            ->with($this->mappings['content_field_identifier'], $value);

        $this->locationService
            ->expects(self::once())
            ->method('newLocationCreateStruct')
            ->with($this->mappings['parent_location_id'])
            ->willReturn($locationCreateStruct);

        $this->contentService
            ->expects(self::once())
            ->method('createContent')
            ->with($contentCreateStruct, [$locationCreateStruct])
            ->willReturn($contentDraft);

        $this->contentService
            ->expects(self::once())
            ->method('publishVersion')
            ->with($contentDraft->versionInfo)
            ->willReturn($content);

        $mapper = $this->createMapper();
        $mapper->createAsset($name, $value, $languageCode);
    }

    public function testGetAssetField(): void
    {
        $expectedValue = new Field();
        $content = $this->createContentWithId(self::EXAMPLE_CONTENT_ID);

        $mapper = $this->createPartialMapper(['isAsset']);
        $mapper
            ->expects(self::once())
            ->method('isAsset')
            ->with($content)
            ->willReturn(true);

        $content
            ->expects(self::once())
            ->method('getField')
            ->with($this->mappings['content_field_identifier'])
            ->willReturn($expectedValue);

        self::assertEquals($expectedValue, $mapper->getAssetField($content));
    }

    public function testGetAssetFieldThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $content = $this->createContentWithId(self::EXAMPLE_CONTENT_ID);

        $mapper = $this->createPartialMapper(['isAsset']);
        $mapper
            ->expects(self::once())
            ->method('isAsset')
            ->with($content)
            ->willReturn(false);

        $mapper->getAssetField($content);
    }

    public function testGetAssetFieldDefinition(): void
    {
        $fieldDefinition = new FieldDefinition();

        $contentType = $this->createMock(ContentType::class);
        $contentType
            ->expects(self::once())
            ->method('getFieldDefinition')
            ->with($this->mappings['content_field_identifier'])
            ->willReturn($fieldDefinition);

        $this->contentTypeService
            ->expects(self::once())
            ->method('loadContentTypeByIdentifier')
            ->with($this->mappings['content_type_identifier'])
            ->willReturn($contentType);

        self::assertEquals($fieldDefinition, $this->createMapper()->getAssetFieldDefinition());
    }

    public function testGetAssetValue(): void
    {
        $expectedValue = new Image\Value();
        $content = $this->createContentWithId(self::EXAMPLE_CONTENT_ID);

        $mapper = $this->createPartialMapper(['isAsset']);
        $mapper
            ->expects(self::once())
            ->method('isAsset')
            ->with($content)
            ->willReturn(true);

        $content
            ->expects(self::once())
            ->method('getFieldValue')
            ->with($this->mappings['content_field_identifier'])
            ->willReturn($expectedValue);

        self::assertEquals($expectedValue, $mapper->getAssetValue($content));
    }

    public function testGetAssetValueThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $content = $this->createContentWithId(self::EXAMPLE_CONTENT_ID);

        $mapper = $this->createPartialMapper(['isAsset']);
        $mapper
            ->expects(self::once())
            ->method('isAsset')
            ->with($content)
            ->willReturn(false);

        $mapper->getAssetField($content);
    }

    /**
     * @dataProvider dataProviderForIsAsset
     */
    public function testIsAsset(
        int $contentContentTypeId,
        int $assetContentTypeId,
        bool $expected
    ): void {
        $assetContentType = new ContentType([
            'id' => $assetContentTypeId,
        ]);

        $this->contentTypeService
            ->expects(self::once())
            ->method('loadContentTypeByIdentifier')
            ->with($this->mappings['content_type_identifier'])
            ->willReturn($assetContentType);

        $actual = $this
            ->createMapper()
            ->isAsset($this->createContentWithContentType($contentContentTypeId));

        self::assertEquals($expected, $actual);
    }

    public function dataProviderForIsAsset(): array
    {
        return [
            [487, 487, true],
            [487, 784, false],
        ];
    }

    public function testGetContentFieldIdentifier(): void
    {
        $mapper = $this->createMapper();

        self::assertEquals(
            $this->mappings['content_field_identifier'],
            $mapper->getContentFieldIdentifier()
        );
    }

    public function testGetParentLocationId(): void
    {
        $mapper = $this->createMapper();

        self::assertEquals(
            $this->mappings['parent_location_id'],
            $mapper->getParentLocationId()
        );
    }

    public function testGetContentTypeIdentifier(): void
    {
        $mapper = $this->createMapper();

        self::assertEquals(
            $this->mappings['content_type_identifier'],
            $mapper->getContentTypeIdentifier()
        );
    }

    private function createMapper(): AssetMapper
    {
        return new AssetMapper(
            $this->contentService,
            $this->locationService,
            $this->contentTypeService,
            $this->configResolver
        );
    }

    private function createPartialMapper(array $methods = []): AssetMapper
    {
        return $this
            ->getMockBuilder(AssetMapper::class)
            ->setConstructorArgs([
                $this->contentService,
                $this->locationService,
                $this->contentTypeService,
                $this->configResolver,
            ])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods($methods)
            ->getMock();
    }

    private function createContentWithId(int $id): Content
    {
        $content = $this->createMock(Content::class);
        $content
            ->expects(self::any())
            ->method('__get')
            ->with('id')
            ->willReturn($id);

        return $content;
    }

    private function createContentWithContentType(int $contentTypeId): Content
    {
        $contentInfo = new ContentInfo([
            'contentTypeId' => $contentTypeId,
        ]);

        $content = $this->createMock(Content::class);
        $content
            ->expects(self::any())
            ->method('__get')
            ->with('contentInfo')
            ->willReturn($contentInfo);

        return $content;
    }

    /**
     * @return ConfigResolverInterface|MockObject
     */
    private function mockConfigResolver(): ConfigResolverInterface
    {
        $mock = $this->createMock(ConfigResolverInterface::class);
        $mock
            ->method('getParameter')
            ->with('fieldtypes.ibexa_image_asset.mappings', null, null)
            ->willReturn($this->mappings)
        ;

        return $mock;
    }
}
