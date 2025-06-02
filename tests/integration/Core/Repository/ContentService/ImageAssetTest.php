<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\ContentService;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
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

        $this->contentService = self::getContentService();
        $this->contentTypeService = self::getContentTypeService();
    }

    public function testAssetRelationIsRemoved(): void
    {
        $contentType = $this->createContentTypeWithImageAsset();
        $destinationContent = $this->createImageContent();

        $struct = $this->contentService->newContentCreateStruct($contentType, 'eng-US');
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

    private function createContentTypeWithImageAsset(): ContentType
    {
        $struct = $this->contentTypeService->newContentTypeCreateStruct('content_type_with_image_asset');
        $struct->names = ['eng-US' => 'Content Type with Image Asset'];

        $struct->addFieldDefinition(
            $this->contentTypeService->newFieldDefinitionCreateStruct('asset', 'ibexa_image_asset')
        );
        $struct->mainLanguageCode = 'eng-US';
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
        $struct = $this->contentService->newContentCreateStruct($imageContentType, 'eng-US');
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
