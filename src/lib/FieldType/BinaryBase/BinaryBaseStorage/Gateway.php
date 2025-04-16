<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\BinaryBase\BinaryBaseStorage;

use Ibexa\Contracts\Core\FieldType\StorageGateway;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

abstract class Gateway extends StorageGateway
{
    /**
     * Stores the file reference in $field for $versionNo.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    abstract public function storeFileReference(VersionInfo $versionInfo, Field $field): bool;

    /**
     * Returns the file reference data for the given $fieldId in $versionNo.
     *
     * @return array<string, mixed>|null
     */
    abstract public function getFileReferenceData(int $fieldId, int $versionNo): ?array;

    /**
     * Removes all file references for the given $fieldIds.
     *
     * @param int[] $fieldIds
     */
    abstract public function removeFileReferences(array $fieldIds, int $versionNo): void;

    /**
     * Removes a specific file reference for $fieldId and $versionId.
     */
    abstract public function removeFileReference(int $fieldId, int $versionNo): void;

    /**
     * Returns a map of files referenced by the given $fieldIds.
     *
     * @param int[] $fieldIds
     *
     * @phpstan-return list<string>
     */
    abstract public function getReferencedFiles(array $fieldIds, int $versionNo): array;

    /**
     * Returns a map with the number of references each file from $files has.
     *
     * @param string[] $files file paths
     *
     * @return array<string, int>
     */
    abstract public function countFileReferences(array $files): array;
}
