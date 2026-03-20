<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\FieldType\GatewayBasedStorage;
use Ibexa\Contracts\Core\FieldType\ReferenceAwareExternalStorage;
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
     * @var \Ibexa\Core\Persistence\Legacy\Content\StorageRegistry
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
    public function __construct(StorageRegistry $storageRegistry, array $context)
    {
        $this->storageRegistry = $storageRegistry;
        $this->context = $context;
    }

    /**
     * Stores data from $field in its corresponding external storage.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field)
    {
        return $this->storageRegistry->getStorage($field->type)->storeFieldData(
            $versionInfo,
            $field,
            $this->context
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $originalField
     */
    public function copyFieldData(VersionInfo $versionInfo, Field $field, Field $originalField)
    {
        return $this->storageRegistry->getStorage($field->type)->copyLegacyField(
            $versionInfo,
            $field,
            $originalField,
            $this->context
        );
    }

    /**
     * Fetches external data for $field from its corresponding external storage.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field)
    {
        $storage = $this->storageRegistry->getStorage($field->type);
        if ($field->id !== null && $storage->hasFieldData()) {
            $storage->getFieldData($versionInfo, $field, $this->context);
        }
    }

    /**
     * Creates a reference to the original field's external data instead of copying it.
     *
     * If the storage implements {@see ReferenceAwareExternalStorage}, it stores a lightweight
     * reference. Otherwise, falls back to a full copy via {@see copyFieldData()}.
     */
    public function referenceFieldData(VersionInfo $versionInfo, Field $field, Field $originalField): ?bool
    {
        $storage = $this->storageRegistry->getStorage($field->type);

        if ($storage instanceof ReferenceAwareExternalStorage) {
            return $storage->referenceLegacyField(
                $versionInfo,
                $field,
                $originalField
            );
        }

        return $storage instanceof GatewayBasedStorage
            ? $storage->copyLegacyField(
                $versionInfo,
                $field,
                $originalField,
                $this->context
            ) : false;
    }

    /**
     * Deletes data for field $ids from external storage of $fieldType.
     *
     * @param string $fieldType
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param mixed[] $ids
     */
    public function deleteFieldData($fieldType, VersionInfo $versionInfo, array $ids)
    {
        $this->storageRegistry->getStorage($fieldType)
            ->deleteFieldData($versionInfo, $ids, $this->context);
    }
}

class_alias(StorageHandler::class, 'eZ\Publish\Core\Persistence\Legacy\Content\StorageHandler');
