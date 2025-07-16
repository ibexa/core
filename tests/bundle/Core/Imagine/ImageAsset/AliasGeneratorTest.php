<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\Imagine\ImageAsset;

use Ibexa\Bundle\Core\Imagine\ImageAsset\AliasGenerator;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Variation\Values\Variation;
use Ibexa\Contracts\Core\Variation\VariationHandler;
use Ibexa\Core\FieldType\Image;
use Ibexa\Core\FieldType\ImageAsset;
use Ibexa\Core\FieldType\ImageAsset\AssetMapper;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\Imagine\ImageAsset\AliasGenerator
 */
final class AliasGeneratorTest extends TestCase
{
    private AliasGenerator $aliasGenerator;

    private VariationHandler & MockObject $innerAliasGenerator;

    private ContentService & MockObject $contentService;

    private AssetMapper & MockObject $assetMapper;

    protected function setUp(): void
    {
        $this->innerAliasGenerator = $this->createMock(VariationHandler::class);
        $this->contentService = $this->createMock(ContentService::class);
        $this->assetMapper = $this->createMock(AssetMapper::class);

        $this->aliasGenerator = new AliasGenerator(
            $this->innerAliasGenerator,
            $this->contentService,
            $this->assetMapper
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testGetVariationOfImageAsset(): void
    {
        $assetField = new Field([
            'value' => new ImageAsset\Value(486),
        ]);
        $imageField = new Field([
            'value' => new Image\Value([
                'id' => 'images/6/8/4/0/486-10-eng-GB/photo.jpg',
            ]),
        ]);

        $assetVersionInfo = new VersionInfo();
        $imageVersionInfo = new VersionInfo();
        $imageContent = new Content([
            'versionInfo' => $imageVersionInfo,
        ]);

        $variationName = 'thumbnail';
        $parameters = [];

        $expectedVariation = new Variation();

        $this->contentService
            ->expects(self::once())
            ->method('loadContent')
            ->with($assetField->value->destinationContentId)
            ->willReturn($imageContent);

        $this->assetMapper
            ->expects(self::once())
            ->method('getAssetField')
            ->with($imageContent)
            ->willReturn($imageField);

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($imageField, $imageVersionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $actualVariation = $this->aliasGenerator->getVariation(
            $assetField,
            $assetVersionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($expectedVariation, $actualVariation);
    }

    public function testGetVariationOfNonImageAsset(): void
    {
        $imageField = new Field([
            'value' => new Image\Value([
                'id' => 'images/6/8/4/0/486-10-eng-GB/photo.jpg',
            ]),
        ]);

        $imageVersionInfo = new VersionInfo();
        $variationName = 'thumbnail';
        $parameters = [];

        $expectedVariation = new Variation();

        $this->contentService
            ->expects(self::never())
            ->method('loadContent');

        $this->assetMapper
            ->expects(self::never())
            ->method('getAssetField');

        $this->innerAliasGenerator
            ->expects(self::once())
            ->method('getVariation')
            ->with($imageField, $imageVersionInfo, $variationName, $parameters)
            ->willReturn($expectedVariation);

        $actualVariation = $this->aliasGenerator->getVariation(
            $imageField,
            $imageVersionInfo,
            $variationName,
            $parameters
        );

        self::assertEquals($expectedVariation, $actualVariation);
    }

    public function testSupport(): void
    {
        self::assertTrue($this->aliasGenerator->supportsValue(new ImageAsset\Value()));
        self::assertFalse($this->aliasGenerator->supportsValue(new Image\Value()));
    }
}
