<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\MapLocation;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
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
     * @param \Ibexa\Core\FieldType\MapLocation\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value->address;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * @param \Ibexa\Core\FieldType\MapLocation\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->latitude === null && $value->longitude === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param array|\Ibexa\Core\FieldType\MapLocation\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\MapLocation\Value The potentially converted and structurally plausible value.
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\MapLocation\Value $value
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
     * @param \Ibexa\Core\FieldType\MapLocation\Value $value
     */
    protected function getSortInfo(SPIValue $value): string
    {
        return $this->transformationProcessor->transformByGroup((string)$value, 'lowercase');
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\MapLocation\Value $value
     *
     * @return array{latitude: float|null, longitude: float|null, address: string|null}|null
     */
    public function toHash(SPIValue $value): ?array
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

    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * @param \Ibexa\Core\FieldType\MapLocation\Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $this->toHash($value),
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    public function fromPersistenceValue(FieldValue $fieldValue): Value
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
