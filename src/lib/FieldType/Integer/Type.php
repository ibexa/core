<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Integer;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseNumericType;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Integer field types.
 *
 * Represents integers.
 */
class Type extends BaseNumericType implements TranslationContainerInterface
{
    protected $validatorConfigurationSchema = [
        'IntegerValueValidator' => [
            'minIntegerValue' => [
                'type' => 'int',
                'default' => null,
            ],
            'maxIntegerValue' => [
                'type' => 'int',
                'default' => null,
            ],
        ],
    ];

    protected function getValidators(): array
    {
        return ['IntegerValueValidator' => new Validator\IntegerValueValidator()];
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_integer';
    }

    /**
     * @param Value $value
     */
    public function getName(
        SPIValue $value,
        FieldDefinition $fieldDefinition,
        string $languageCode
    ): string {
        return (string)$value;
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     */
    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Returns if the given $value is considered empty by the field type.
     *
     * @param Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->value === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|Value $inputValue
     *
     * @return Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_int($inputValue)) {
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
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_int($value->value)) {
            throw new InvalidArgumentType(
                '$value->value',
                'integer',
                $value->value
            );
        }
    }

    /**
     * @param Value $value
     */
    protected function getSortInfo(BaseValue $value)
    {
        return $value->value;
    }

    /**
     * Converts a <code>$hash</code> to the Value defined by the field type.
     *
     * @param int|string|null $hash
     *
     * @return Value $value
     */
    public function fromHash($hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value((int)$hash);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param Value $value
     */
    public function toHash(SPIValue $value): ?int
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->value;
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

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_integer.name', 'ibexa_fieldtypes')->setDesc('Integer'),
        ];
    }
}
