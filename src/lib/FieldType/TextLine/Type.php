<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\TextLine;

use Ibexa\Contracts\Core\Exception\InvalidArgumentType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\BaseTextType;
use Ibexa\Core\FieldType\Validator\StringLengthValidator;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;

/**
 * The TextLine field type.
 *
 * This field type represents a simple string.
 */
class Type extends BaseTextType
{
    protected $validatorConfigurationSchema = [
        'StringLengthValidator' => [
            'minStringLength' => [
                'type' => 'int',
                'default' => 0,
            ],
            'maxStringLength' => [
                'type' => 'int',
                'default' => null,
            ],
        ],
    ];

    /**
     * Validates the validatorConfiguration of a FieldDefinitionCreateStruct or FieldDefinitionUpdateStruct.
     *
     * @param array<string, mixed> $validatorConfiguration
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateValidatorConfiguration($validatorConfiguration): array
    {
        $validationErrors = [];
        $validators = ['StringLengthValidator' => new StringLengthValidator()];

        foreach ($validatorConfiguration as $validatorIdentifier => $constraints) {
            if (!isset($validators[$validatorIdentifier])) {
                $validationErrors[] = $this->buildUnknownValidatorError('%validator%', $validatorIdentifier);
                continue;
            }
            $validationErrors += $validators[$validatorIdentifier]->validateConstraints($constraints);
        }

        return $validationErrors;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDefinition The field definition of the field
     * @param \Ibexa\Core\FieldType\TextLine\Value $fieldValue The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue): array
    {
        $validationErrors = [];

        if ($this->isEmptyValue($fieldValue)) {
            return $validationErrors;
        }

        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        $constraints = $validatorConfiguration['StringLengthValidator'] ?? [];
        $validator = new StringLengthValidator();
        $validator->initializeWithConstraints($constraints);

        return false === $validator->validate($fieldValue, $fieldDefinition) ? $validator->getMessage() : [];
    }

    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_string';
    }

    /**
     * @return \Ibexa\Core\FieldType\TextLine\Value
     */
    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * @param string|\Ibexa\Core\FieldType\TextLine\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\TextLine\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    protected static function checkValueType(mixed $value): void
    {
        if (!$value instanceof Value) {
            throw new InvalidArgumentType('$value', Value::class, $value);
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     */
    protected function getSortInfo(BaseValue $value): string
    {
        return $this->transformationProcessor->transformByGroup((string)$value, 'lowercase');
    }

    /**
     * @param string $hash
     */
    public function fromHash($hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @return list<\JMS\TranslationBundle\Model\Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_string.name', 'ibexa_fieldtypes')->setDesc('Text line'),
        ];
    }
}
