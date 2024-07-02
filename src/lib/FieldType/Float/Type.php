<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Float;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseNumericType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;

/**
 * Float field types.
 *
 * Represents floats.
 */
class Type extends BaseNumericType
{
    protected $validatorConfigurationSchema = [
        'FloatValueValidator' => [
            'minFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
            'maxFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
        ],
    ];

    protected function isConfigurationValidatorSupported(string $validatorIdentifier): bool
    {
        return $validatorIdentifier === 'FloatValueValidator';
    }

    protected function validateValidatorConfigurationNumericConstraint(
        string $validatorIdentifier,
        string $name,
        mixed $value
    ): ?string {
        return match ($name) {
            'minFloatValue', 'maxFloatValue' => $value !== null && !is_numeric($value)
                ? "Validator parameter '%parameter%' value must be of numeric type"
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
     * @param \Ibexa\Core\FieldType\Float\Value $fieldValue The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue): array
    {
        if ($this->isEmptyValue($fieldValue)) {
            return [];
        }

        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        $constraints = $validatorConfiguration['FloatValueValidator'] ?? [];

        $validationErrors = [];

        if (isset($constraints['maxFloatValue']) && $fieldValue->value > $constraints['maxFloatValue']) {
            $validationErrors[] = new ValidationError(
                'The value can not be higher than %size%.',
                null,
                [
                    '%size%' => $constraints['maxFloatValue'],
                ],
                'value'
            );
        }

        if (isset($constraints['minFloatValue']) && $fieldValue->value < $constraints['minFloatValue']) {
            $validationErrors[] = new ValidationError(
                'The value can not be lower than %size%.',
                null,
                [
                    '%size%' => $constraints['minFloatValue'],
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
        return 'ezfloat';
    }

    /**
     * @param \Ibexa\Core\FieldType\Float\Value $value
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
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->value === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|float|\Ibexa\Core\FieldType\Float\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Float\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_numeric($inputValue)) {
            $inputValue = (float)$inputValue;
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_float($value->value)) {
            throw new InvalidArgumentType(
                '$value->value',
                'float',
                $value->value
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    protected function getSortInfo(BaseValue $value)
    {
        return $value->value;
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * @param mixed $hash
     *
     * @return \Ibexa\Core\FieldType\Float\Value $value
     */
    public function fromHash($hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value((float)$hash);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param \Ibexa\Core\FieldType\Float\Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value): mixed
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->value;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ezfloat.name', 'ibexa_fieldtypes')->setDesc('Float'),
        ];
    }
}
