<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;

use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\Converter;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition;
use Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue;

class TextLineConverter implements Converter
{
    public const STRING_LENGTH_VALIDATOR_IDENTIFIER = 'StringLengthValidator';

    /**
     * Converts data from $value to $storageFieldValue.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $value
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
        $storageFieldValue->dataText = $value->data;
        $storageFieldValue->sortKeyString = $value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        $fieldValue->data = $value->dataText;
        $fieldValue->sortKey = $value->sortKeyString;
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef)
    {
        if (isset($fieldDef->fieldTypeConstraints->validators[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['maxStringLength'])) {
            $storageDef->dataInt1 = $fieldDef->fieldTypeConstraints->validators[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['maxStringLength'];
        } else {
            $storageDef->dataInt1 = 0;
        }

        if (isset($fieldDef->fieldTypeConstraints->validators[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['minStringLength'])) {
            $storageDef->dataInt2 = $fieldDef->fieldTypeConstraints->validators[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['minStringLength'];
        } else {
            $storageDef->dataInt2 = 0;
        }

        $storageDef->dataText1 = $fieldDef->defaultValue->data;
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef)
    {
        $validatorConstraints = [];

        if (isset($storageDef->dataInt1)) {
            $validatorConstraints[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['maxStringLength'] =
                $storageDef->dataInt1 != 0 ?
                    (int)$storageDef->dataInt1 :
                    null;
        }
        if (isset($storageDef->dataInt2)) {
            $validatorConstraints[self::STRING_LENGTH_VALIDATOR_IDENTIFIER]['minStringLength'] =
                $storageDef->dataInt2 != 0 ?
                    (int)$storageDef->dataInt2 :
                    null;
        }

        $fieldDef->fieldTypeConstraints->validators = $validatorConstraints;
        $fieldDef->defaultValue->data = $storageDef->dataText1 ?: null;
        $fieldDef->defaultValue->sortKey = $storageDef->dataText1 ?: '';
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
        return 'sort_key_string';
    }
}
