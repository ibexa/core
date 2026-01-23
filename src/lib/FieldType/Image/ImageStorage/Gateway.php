<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Image\ImageStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Image Field Type external storage gateway.
 */
abstract class Gateway extends StorageGateway
{
    /**
     * Returns the node path string of $versionInfo.
     *
     * @param VersionInfo $versionInfo
     *
     * @return string
     */
    abstract public function getNodePathString(VersionInfo $versionInfo);

    /**
     * Stores a reference to the image in $path for $fieldId.
     */
    abstract public function storeImageReference(
        string $uri,
        mixed $fieldId
    ): void;

    /**
     * Returns a the XML content stored for the given $fieldIds.
     */
    abstract public function getXmlForImages(
        int $versionNo,
        array $fieldIds
    ): array;

    /**
     * Removes all references from $fieldId to a path that starts with $path.
     */
    abstract public function removeImageReferences(
        string $uri,
        int $versionNo,
        mixed $fieldId
    ): void;

    /**
     * Returns the number of recorded references to the given $path.
     */
    abstract public function countImageReferences(string $uri): int;

    /**
     * Returns true if there is reference to the given $uri.
     */
    abstract public function isImageReferenced(string $uri): bool;

    /**
     * Returns the public uris for the images stored in $xml.
     *
     * @return array<string, string>|null
     */
    abstract public function extractFilesFromXml(?string $xml): ?array;

    abstract public function getAllVersionsImageXmlForFieldId(int $fieldId): array;

    abstract public function updateImageData(
        int $fieldId,
        int $versionNo,
        string $xml
    ): void;

    abstract public function getImagesData(
        int $offset,
        int $limit
    ): array;

    abstract public function updateImagePath(
        int $fieldId,
        string $oldPath,
        string $newPath
    ): void;

    abstract public function countDistinctImagesData(): int;

    abstract public function hasImageReference(
        string $uri,
        int $fieldId
    ): bool;
}
