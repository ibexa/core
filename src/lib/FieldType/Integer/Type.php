<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Integer;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseNumericType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;

/**
 * Integer field types.
 *
 * Represents integers.
 */
class Type extends BaseNumericType
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

    protected function isConfigurationValidatorSupported(string $validatorIdentifier): bool
    {
        return $validatorIdentifier === 'IntegerValueValidator';
    }

    protected function validateValidatorConfigurationNumericConstraint(
        string $validatorIdentifier,
        string $name,
        mixed $value
    ): ?string {
        return match ($name) {
            'minIntegerValue', 'maxIntegerValue' => $value !== null && !is_int($value)
                ? "Validator parameter '%parameter%' value must be of integer type"
                : null,
            default => "Validator parameter '%parameter%' is unknown",
        };
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \Ibexa\Core\FieldType\Integer\Value $fieldValue The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue): array
    {
        if ($this->isEmptyValue($fieldValue)) {
            return [];
        }

        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        $constraints = $validatorConfiguration['IntegerValueValidator'] ?? [];

        $validationErrors = [];

        // 0 and False are unlimited value for maxIntegerValue
        if (isset($constraints['maxIntegerValue']) &&
            $constraints['maxIntegerValue'] !== 0 &&
            $constraints['maxIntegerValue'] !== false &&
            $fieldValue->value > $constraints['maxIntegerValue']
        ) {
            $validationErrors[] = new ValidationError(
                'The value can not be higher than %size%.',
                null,
                [
                    '%size%' => $constraints['maxIntegerValue'],
                ],
                'value'
            );
        }

        if (isset($constraints['minIntegerValue']) &&
            $constraints['minIntegerValue'] !== false && $fieldValue->value < $constraints['minIntegerValue']) {
            $validationErrors[] = new ValidationError(
                'The value can not be lower than %size%.',
                null,
                [
                    '%size%' => $constraints['minIntegerValue'],
                ],
                'value'
            );
        }

        return $validationErrors;
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ezinteger';
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
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
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->value === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|\Ibexa\Core\FieldType\Integer\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Integer\Value The potentially converted and structurally plausible value.
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Integer\Value $value
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
     * @param \Ibexa\Core\FieldType\Integer\Value $value
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
     * @return \Ibexa\Core\FieldType\Integer\Value $value
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
     * @param \Ibexa\Core\FieldType\Integer\Value $value
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
            Message::create('ezinteger.name', 'ibexa_fieldtypes')->setDesc('Integer'),
        ];
    }
}
