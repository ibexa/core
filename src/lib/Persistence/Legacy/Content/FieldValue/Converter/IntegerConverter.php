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

class IntegerConverter implements Converter
{
    public const FLOAT_VALIDATOR_IDENTIFIER = 'IntegerValueValidator';

    public const HAS_MIN_VALUE = 1;
    public const HAS_MAX_VALUE = 2;

    /**
     * Converts data from $value to $storageFieldValue.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $value
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $storageFieldValue
     */
    public function toStorageValue(FieldValue $value, StorageFieldValue $storageFieldValue)
    {
        $storageFieldValue->dataInt = $value->data;
        $storageFieldValue->sortKeyInt = (int)$value->sortKey;
    }

    /**
     * Converts data from $value to $fieldValue.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldValue $value
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     */
    public function toFieldValue(StorageFieldValue $value, FieldValue $fieldValue)
    {
        $fieldValue->data = $value->dataInt;
        $fieldValue->sortKey = $value->sortKeyInt;
    }

    /**
     * Converts field definition data in $fieldDef into $storageFieldDef.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     */
    public function toStorageFieldDefinition(FieldDefinition $fieldDef, StorageFieldDefinition $storageDef)
    {
        if (isset($fieldDef->fieldTypeConstraints->validators[self::FLOAT_VALIDATOR_IDENTIFIER]['minIntegerValue'])) {
            $storageDef->dataInt1 = $fieldDef->fieldTypeConstraints->validators[self::FLOAT_VALIDATOR_IDENTIFIER]['minIntegerValue'];
        }
        if (isset($fieldDef->fieldTypeConstraints->validators[self::FLOAT_VALIDATOR_IDENTIFIER]['maxIntegerValue'])) {
            $storageDef->dataInt2 = $fieldDef->fieldTypeConstraints->validators[self::FLOAT_VALIDATOR_IDENTIFIER]['maxIntegerValue'];
        }

        if ($storageDef->dataInt1 === false || $storageDef->dataInt2 === false) {
            @trigger_error(
                "The IntegerValueValidator constraint value 'false' is deprecated, and will be removed in 7.0. Use 'null' instead.",
                E_USER_DEPRECATED
            );
            $storageDef->dataInt1 = $storageDef->dataInt1 === false ? null : $storageDef->dataInt1;
            $storageDef->dataInt2 = $storageDef->dataInt2 === false ? null : $storageDef->dataInt2;
        }

        // Defining dataInt4 which holds the validator state (min value/max value/minMax value)
        $storageDef->dataInt4 = $this->getStorageDefValidatorState($storageDef->dataInt1, $storageDef->dataInt2);
        $storageDef->dataInt3 = $fieldDef->defaultValue->data;
    }

    /**
     * Converts field definition data in $storageDef into $fieldDef.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Content\StorageFieldDefinition $storageDef
     * @param \Ibexa\Contracts\Core\Persistence\Content\Type\FieldDefinition $fieldDef
     */
    public function toFieldDefinition(StorageFieldDefinition $storageDef, FieldDefinition $fieldDef)
    {
        $validatorParameters = ['minIntegerValue' => null, 'maxIntegerValue' => null];
        if ($storageDef->dataInt4 & self::HAS_MIN_VALUE) {
            $validatorParameters['minIntegerValue'] = $storageDef->dataInt1;
        }

        if ($storageDef->dataInt4 & self::HAS_MAX_VALUE) {
            $validatorParameters['maxIntegerValue'] = $storageDef->dataInt2;
        }
        $fieldDef->fieldTypeConstraints->validators[self::FLOAT_VALIDATOR_IDENTIFIER] = $validatorParameters;
        $fieldDef->defaultValue->data = $storageDef->dataInt3;
        $fieldDef->defaultValue->sortKey = ($storageDef->dataInt3 === null ? 0 : $storageDef->dataInt3);
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

    /**
     * Returns validator state for storage definition.
     * Validator state is a bitfield value composed of:
     *   - {@link self::HAS_MAX_VALUE}
     *   - {@link self::HAS_MIN_VALUE}.
     *
     * @param int|null $minValue Minimum int value, or null if not set
     * @param int|null $maxValue Maximum int value, or null if not set
     *
     * @return int
     */
    private function getStorageDefValidatorState($minValue, $maxValue): int
    {
        $state = 0;

        if ($minValue !== null) {
            $state |= self::HAS_MIN_VALUE;
        }

        if ($maxValue !== null) {
            $state |= self::HAS_MAX_VALUE;
        }

        return $state;
    }
}
