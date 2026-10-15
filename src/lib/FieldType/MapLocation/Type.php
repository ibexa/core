<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\MapLocation;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * MapLocation field types.
 *
 * Represents keywords.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_gmap_location';
    }

    /**
     * @param Value|SPIValue $value
     */
    public function getName(
        SPIValue $value,
        FieldDefinition $fieldDefinition,
        string $languageCode
    ): string {
        return (string)$value->address;
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return Value
     */
    public function getEmptyValue()
    {
        return new Value();
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->latitude === null && $value->longitude === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|Value $inputValue
     *
     * @return Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_array($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws InvalidArgumentException If the value does not match the expected structure.
     *
     * @param Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_float($value->latitude) && !is_int($value->latitude)) {
            throw new InvalidArgumentType(
                '$value->latitude',
                'float',
                $value->latitude
            );
        }
        if (!is_float($value->longitude) && !is_int($value->longitude)) {
            throw new InvalidArgumentType(
                '$value->longitude',
                'float',
                $value->longitude
            );
        }
        if (!is_string($value->address)) {
            throw new InvalidArgumentType(
                '$value->address',
                'string',
                $value->address
            );
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param Value $value
     *
     * @return string
     */
    protected function getSortInfo(BaseValue $value)
    {
        return $this->transformationProcessor->transformByGroup((string)$value, 'lowercase');
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * @param mixed $hash
     *
     * @return Value $value
     */
    public function fromHash($hash)
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value)
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return [
            'latitude' => $value->latitude,
            'longitude' => $value->longitude,
            'address' => $value->address,
        ];
    }

    /**
     * Returns whether the field type is searchable.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Converts a $value to a persistence value.
     *
     * @param Value $value
     *
     * @return FieldValue
     */
    public function toPersistenceValue(SPIValue $value)
    {
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $this->toHash($value),
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value.
     *
     * @param FieldValue $fieldValue
     *
     * @return Value
     */
    public function fromPersistenceValue(FieldValue $fieldValue)
    {
        if ($fieldValue->externalData === null) {
            return $this->getEmptyValue();
        }

        return $this->fromHash($fieldValue->externalData);
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_gmap_location.name', 'ibexa_fieldtypes')->setDesc('Map location'),
        ];
    }
}
