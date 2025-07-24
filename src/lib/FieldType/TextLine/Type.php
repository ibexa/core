<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\TextLine;

use Ibexa\Contracts\Core\Exception\InvalidArgumentType;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\BaseTextType;
use Ibexa\Core\FieldType\Validator\StringLengthValidator;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The TextLine field type.
 *
 * This field type represents a simple string.
 */
class Type extends BaseTextType implements TranslationContainerInterface
{
    protected array $validatorConfigurationSchema = [
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
     * @param mixed $validatorConfiguration
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     */
    public function validateValidatorConfiguration(mixed $validatorConfiguration): array
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
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\TextLine\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $validationErrors = [];

        if ($this->isEmptyValue($value)) {
            return $validationErrors;
        }

        $validatorConfiguration = $fieldDef->getValidatorConfiguration();
        $constraints = $validatorConfiguration['StringLengthValidator'] ?? [];
        $validator = new StringLengthValidator();
        $validator->initializeWithConstraints($constraints);

        return false === $validator->validate($value, $fieldDef) ? $validator->getMessage() : [];
    }

    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_string';
    }

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
     * @return list<\JMS\TranslationBundle\Model\Message>
     */
    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_string.name', 'ibexa_fieldtypes')->setDesc('Text line'),
        ];
    }
}
