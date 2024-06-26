<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;

/**
 * Interface for setting field type data.
 *
 * Methods in this interface are called by storage engine.
 *
 * $context array passed to most methods is deprecated and will be dropped in the next major version.
 */
interface FieldStorage
{
    /**
     * Allows custom field types to store data in an external source (e.g. another DB table).
     *
     * Stores value for $field in an external data source.
     * The whole {@see \Ibexa\Contracts\Core\Persistence\Content\Field} object is passed and its value
     * is accessible through the {@see \Ibexa\Contracts\Core\Persistence\Content\FieldValue} 'value' property.
     * This value holds the data filled by the user as a {@see \Ibexa\Core\FieldType\Value} based object,
     * according to the field type (e.g. for TextLine, it will be a {@see \Ibexa\Core\FieldType\TextLine\Value} object).
     *
     * $field->id = unique ID from the attribute tables (needs to be generated by
     * database back end on create, before the external data source may be
     * called from storing).
     *
     * The context array is deprecated and will be dropped in the next major version.
     *
     * This method might return true if $field needs to be updated after storage done here (to store a PK for instance).
     * In any other case, this method must not return anything (null).
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param array $context Deprecated. Rely on injected Connection instead.
     *
     * @return mixed null|true
     */
    public function storeFieldData(VersionInfo $versionInfo, Field $field, array $context);

    /**
     * Populates $field value property based on the external data.
     * $field->value is a {@see \Ibexa\Contracts\Core\Persistence\Content\FieldValue} object.
     * This value holds the data as a {@see \Ibexa\Core\FieldType\Value} based object,
     * according to the field type (e.g. for TextLine, it will be a {@see \Ibexa\Core\FieldType\TextLine\Value} object).
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param array $context Deprecated. Rely on injected Connection instead.
     */
    public function getFieldData(VersionInfo $versionInfo, Field $field, array $context);

    /**
     * Deletes field data for all $fieldIds in the version identified by
     * $versionInfo.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param array $fieldIds Array of field IDs
     * @param array $context Deprecated. Rely on injected Connection instead.
     *
     * @return bool
     */
    public function deleteFieldData(VersionInfo $versionInfo, array $fieldIds, array $context);

    /**
     * Checks if field type has external data to deal with.
     *
     * @return bool
     */
    public function hasFieldData();

    /**
     * Get index data for external data for search backend.
     *
     * @deprecated Use {@see \Ibexa\Contracts\Core\FieldType\Indexable}
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\VersionInfo $versionInfo
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     * @param array $context Deprecated. Rely on injected Connection instead.
     *
     * @return \Ibexa\Contracts\Core\Search\Field[]
     */
    public function getIndexData(VersionInfo $versionInfo, Field $field, array $context);
}
