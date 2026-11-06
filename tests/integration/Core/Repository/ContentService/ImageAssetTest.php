<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\FieldType\Image\Value;
use Ibexa\Core\FieldType\ImageAsset\Value as AssetValue;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

final class ImageAssetTest extends RepositoryTestCase
{
    private ContentService $contentService;

    private ContentTypeService $contentTypeService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->contentService = $this->getIbexaTestCore()->getContentService();
        $this->contentTypeService = $this->getIbexaTestCore()->getContentTypeService();
    }

    public function testAssetRelationIsRemoved(): void
    {
        $contentType = $this->createContentTypeWithImageAsset();
        $destinationContent = $this->createImageContent();

        $struct = $this->contentService->newContentCreateStruct($contentType, 'eng-GB');
        $struct->setField('asset', new AssetValue(
            $destinationContent->getId(),
        ));
        $assetContent = $this->contentService->publishVersion(
            $this->contentService->createContent($struct)->getVersionInfo()
        );

        self::assertEquals(1, $this->contentService->countRelations($assetContent->getVersionInfo()));
        self::assertEquals(1, $this->contentService->countReverseRelations($destinationContent->getContentInfo()));

        $this->contentService->deleteContent($destinationContent->getContentInfo());

        self::assertEquals(0, $this->contentService->countRelations($assetContent->getVersionInfo()));
        self::assertEquals(0, $this->contentService->countReverseRelations($destinationContent->getContentInfo()));

        $assetContent = $this->contentService->loadContentByContentInfo($assetContent->getContentInfo());

        $value = $assetContent->getFieldValue('asset');
        self::assertInstanceOf(AssetValue::class, $value);
        self::assertNull($value->destinationContentId);
    }

    /**
     * @dataProvider provideForDanglingAssetValue
     */
    public function testDanglingAssetValueDoesNotBreakPublishing(bool $updateWithSameValue): void
    {
        $contentType = $this->createContentTypeWithImageAsset(false);
        [$assetContent, $destinationContentId] = $this->createContentWithDanglingAsset($contentType);

        $draft = $this->contentService->createContentDraft($assetContent->getContentInfo());
        if ($updateWithSameValue) {
            $updateStruct = $this->contentService->newContentUpdateStruct();
            $updateStruct->setField('asset', new AssetValue($destinationContentId));
            $draft = $this->contentService->updateContent($draft->getVersionInfo(), $updateStruct);
        }

        $published = $this->contentService->publishVersion($draft->getVersionInfo());

        self::assertSame(2, $published->getVersionInfo()->versionNo);
        if ($updateWithSameValue) {
            $value = $published->getFieldValue('asset');
            self::assertInstanceOf(AssetValue::class, $value);
            self::assertNull($value->destinationContentId);
        }
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public function provideForDanglingAssetValue(): iterable
    {
        yield 'publish untouched draft' => [false];
        yield 'update draft with the stored value, then publish' => [true];
    }

    public function testDanglingAssetValueFailsPublishingWhenFieldIsRequired(): void
    {
        $contentType = $this->createContentTypeWithImageAsset(true);
        [$assetContent] = $this->createContentWithDanglingAsset($contentType);

        $draft = $this->contentService->createContentDraft($assetContent->getContentInfo());

        $this->expectException(ContentFieldValidationException::class);
        $this->contentService->publishVersion($draft->getVersionInfo());
    }

    /**
     * Creates content pointing to an image that is then permanently deleted, and puts the stale
     * reference back into the database, as left behind by deletions done before IBX-9262.
     *
     * @return array{Content, int}
     */
    private function createContentWithDanglingAsset(ContentType $contentType): array
    {
        $destinationContent = $this->createImageContent();
        $destinationContentId = $destinationContent->getId();

        $struct = $this->contentService->newContentCreateStruct($contentType, 'eng-GB');
        $struct->setField('asset', new AssetValue($destinationContentId));
        $assetContent = $this->contentService->publishVersion(
            $this->contentService->createContent($struct)->getVersionInfo()
        );

        $this->contentService->deleteContent($destinationContent->getContentInfo());

        $this->getIbexaTestCore()->getServiceByClassName(Connection::class)->executeStatement(
            'UPDATE ezcontentobject_attribute SET data_int = :destination_content_id WHERE contentobject_id = :content_id',
            [
                'destination_content_id' => $destinationContentId,
                'content_id' => $assetContent->getId(),
            ]
        );

        return [$assetContent, $destinationContentId];
    }

    private function createContentTypeWithImageAsset(bool $isRequired = false): ContentType
    {
        $struct = $this->contentTypeService->newContentTypeCreateStruct('content_type_with_image_asset');
        $struct->names = ['eng-GB' => 'Content Type with Image Asset'];

        $fieldDefinitionStruct = $this->contentTypeService->newFieldDefinitionCreateStruct('asset', 'ezimageasset');
        $fieldDefinitionStruct->isRequired = $isRequired;
        $struct->addFieldDefinition($fieldDefinitionStruct);
        $struct->mainLanguageCode = 'eng-GB';
        $contentType = $this->contentTypeService->createContentType(
            $struct,
            [$this->contentTypeService->loadContentTypeGroupByIdentifier('Content')],
        );
        $this->contentTypeService->publishContentTypeDraft($contentType);

        return $this->contentTypeService->loadContentType($contentType->id);
    }

    private function createImageContent(): Content
    {
        $path = __DIR__ . '/../_fixtures/image/square.png';

        $imageContentType = $this->contentTypeService->loadContentTypeByIdentifier('image');
        $struct = $this->contentService->newContentCreateStruct($imageContentType, 'eng-GB');
        $struct->setField('name', 'Image Name');
        $struct->setField('image', new Value(
            [
                'fileName' => 'square.jpg',
                'inputUri' => $path,
                'fileSize' => filesize($path),
            ],
        ));
        $content = $this->contentService->createContent(
            $struct
        );

        return $this->contentService->publishVersion($content->getVersionInfo());
    }
}
