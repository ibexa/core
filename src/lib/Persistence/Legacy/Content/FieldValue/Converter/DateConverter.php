<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\FieldType\Date\Type as DateType;
use Ibexa\Core\FieldType\FieldSettings;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

/**
 * Date field value converter class.
 */
class DateConverter implements Converter
{
    /**
     * Converts data from $value to $storageFieldValue.
     *
     * @param FieldValue $value
     * @param StorageFieldValue $storageFieldValue
     */
    public function toStorageValue(
        FieldValue $value,
        StorageFieldValue $storageFieldValue
    ) {
        $storageFieldValue->dataInt = ($value->data !== null ? $value->data['timestamp'] : null);
        $storageFieldValue->sortKeyInt = (int)$value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param StorageFieldValue $value
     * @param FieldValue $fieldValue
     */
    public function toFieldValue(
        StorageFieldValue $value,
        FieldValue $fieldValue
    ) {
        if ($value->dataInt === null || $value->dataInt == 0) {
            return;
        }

        $fieldValue->data = [
            'timestamp' => $value->dataInt,
            'rfc850' => null,
        ];
        $fieldValue->sortKey = $value->sortKeyInt;
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     *
     * @param FieldDefinition $fieldDef
     * @param StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition(
        FieldDefinition $fieldDef,
        StorageFieldDefinition $storageDef
    ) {
        $storageDef->dataInt1 = $fieldDef->fieldTypeConstraints->fieldSettings['defaultType'] ?? null;
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * @param StorageFieldDefinition $storageDef
     * @param FieldDefinition $fieldDef
     */
    public function toFieldDefinition(
        StorageFieldDefinition $storageDef,
        FieldDefinition $fieldDef
    ) {
        $fieldDef->fieldTypeConstraints->fieldSettings = new FieldSettings(
            [
                'defaultType' => $storageDef->dataInt1,
            ]
        );

        // Building default value
        switch ($fieldDef->fieldTypeConstraints->fieldSettings['defaultType']) {
            case DateType::DEFAULT_CURRENT_DATE:
                $data = [
                    'rfc850' => null,
                    'timestring' => 'now',
                ];
                break;
            default:
                $data = null;
        }

        $fieldDef->defaultValue->data = $data;
    }

    /**
     * Returns the name of the index column in the attribute table.
     *
     * Returns the name of the index column the datatype uses, which is either
     * "sort_key_int" or "sort_key_string". This column is then used for
     * filtering and sorting for this type.
     *
     * @return string
     */
    public function getIndexColumn(): string
    {
        return 'sort_key_int';
    }
}
