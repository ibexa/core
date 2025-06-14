<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\ImageAsset;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\FieldType\Image\Value as ImageValue;

class AssetMapper
{
    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Ibexa\Contracts\Core\Repository\LocationService */
    private $locationService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentTypeService */
    private $contentTypeService;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface */
    private $configResolver;

    /** @var int */
    private $contentTypeId = null;

    public function __construct(
        ContentService $contentService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        ConfigResolverInterface $configResolver
    ) {
        $this->contentService = $contentService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->configResolver = $configResolver;
    }

    /**
     * Creates an Image Asset.
     *
     * @param string $name
     * @param \Ibexa\Core\FieldType\Image\Value $image
     * @param string $languageCode
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    public function createAsset(string $name, ImageValue $image, string $languageCode): Content
    {
        $mappings = $this->getMappings();

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier(
            $mappings['content_type_identifier']
        );

        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, $languageCode);
        $contentCreateStruct->setField($mappings['name_field_identifier'], $name);
        $contentCreateStruct->setField($mappings['content_field_identifier'], $image);

        $contentDraft = $this->contentService->createContent($contentCreateStruct, [
            $this->locationService->newLocationCreateStruct($mappings['parent_location_id']),
        ]);

        return $this->contentService->publishVersion($contentDraft->versionInfo);
    }

    /**
     * Returns field which is used to store the Image Asset value from specified content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getAssetField(Content $content): Field
    {
        if (!$this->isAsset($content)) {
            throw new InvalidArgumentException('contentId', "Content {$content->id} is not an image asset.");
        }

        return $content->getField($this->getContentFieldIdentifier());
    }

    /**
     * Returns definition of the field which is used to store value of the Image Asset.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition
     */
    public function getAssetFieldDefinition(): FieldDefinition
    {
        $mappings = $this->getMappings();

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier(
            $mappings['content_type_identifier']
        );

        return $contentType->getFieldDefinition(
            $mappings['content_field_identifier']
        );
    }

    /**
     * Returns field value of the Image Asset from specified content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Core\FieldType\Image\Value
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getAssetValue(Content $content): ImageValue
    {
        if (!$this->isAsset($content)) {
            throw new InvalidArgumentException('contentId', "Content {$content->id} is not an image asset.");
        }

        return $content->getFieldValue($this->getContentFieldIdentifier());
    }

    /**
     * Returns TRUE if content is an Image Asset.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return bool
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function isAsset(Content $content): bool
    {
        if ($this->contentTypeId === null) {
            $contentType = $this->contentTypeService->loadContentTypeByIdentifier(
                $this->getContentTypeIdentifier()
            );

            $this->contentTypeId = $contentType->id;
        }

        return $content->contentInfo->contentTypeId === $this->contentTypeId;
    }

    /**
     * Return identifier of the content type used as Assets.
     */
    public function getContentTypeIdentifier(): string
    {
        return $this->getMappings()['content_type_identifier'];
    }

    /**
     * Return identifier of the field used to store Image Asset value.
     *
     * @return string
     */
    public function getContentFieldIdentifier(): string
    {
        return $this->getMappings()['content_field_identifier'];
    }

    /**
     * Return ID of the base location for the Image Assets.
     *
     * @return int
     */
    public function getParentLocationId(): int
    {
        return $this->getMappings()['parent_location_id'];
    }

    protected function getMappings(): array
    {
        return $this->configResolver->getParameter('fieldtypes.ibexa_image_asset.mappings');
    }
}
