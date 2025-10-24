<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Handler for external storages.
 */
class StorageHandler
{
    /**
     * Storage registry.
     *
     * @var StorageRegistry
     */
    protected $storageRegistry;

    /**
     * Array with database context.
     *
     * @var array
     */
    protected $context;

    /**
     * Creates a new storage handler.
     *
     * @param StorageRegistry $storageRegistry
     * @param array $context
     */
    public function __construct(
        StorageRegistry $storageRegistry,
        array $context
    ) {
        $this->storageRegistry = $storageRegistry;
        $this->context = $context;
    }

    /**
     * Stores data from $field in its corresponding external storage.
     *
     * @param VersionInfo $versionInfo
     * @param Field $field
     */
    public function storeFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        return $this->storageRegistry->getStorage($field->type)->storeFieldData(
            $versionInfo,
            $field
        );
    }

    /**
     * @param VersionInfo $versionInfo
     * @param Field $field
     * @param Field $originalField
     */
    public function copyFieldData(
        VersionInfo $versionInfo,
        Field $field,
        Field $originalField
    ) {
        return $this->storageRegistry->getStorage($field->type)->copyLegacyField(
            $versionInfo,
            $field,
            $originalField
        );
    }

    /**
     * Fetches external data for $field from its corresponding external storage.
     *
     * @param VersionInfo $versionInfo
     * @param Field $field
     */
    public function getFieldData(
        VersionInfo $versionInfo,
        Field $field
    ) {
        $storage = $this->storageRegistry->getStorage($field->type);
        if ($field->id !== null && $storage->hasFieldData()) {
            $storage->getFieldData($versionInfo, $field);
        }
    }

    /**
     * Deletes data for field $ids from external storage of $fieldType.
     *
     * @param string $fieldType
     * @param VersionInfo $versionInfo
     * @param mixed[] $ids
     */
    public function deleteFieldData(
        $fieldType,
        VersionInfo $versionInfo,
        array $ids
    ) {
        $this->storageRegistry->getStorage($fieldType)
            ->deleteFieldData($versionInfo, $ids);
    }
}
