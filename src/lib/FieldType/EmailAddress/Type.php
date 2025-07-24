<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\EmailAddress;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator\EmailAddressValidator;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The EMailAddress field type.
 *
 * This field type represents an email address.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    protected array $validatorConfigurationSchema = [
        'EmailAddressValidator' => [],
    ];

    /**
     * @param \Ibexa\Core\FieldType\EmailAddress\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return $this->transformationProcessor->transformByGroup((string)$value, 'lowercase');
    }

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
        $validator = new EmailAddressValidator();

        foreach ($validatorConfiguration as $validatorIdentifier => $constraints) {
            if ($validatorIdentifier !== 'EmailAddressValidator') {
                $validationErrors[] = new ValidationError(
                    "Validator '%validator%' is unknown",
                    null,
                    [
                        '%validator%' => $validatorIdentifier,
                    ],
                    "[$validatorIdentifier]"
                );
                continue;
            }
            $validationErrors += $validator->validateConstraints($constraints);
        }

        return $validationErrors;
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\EmailAddress\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $errors = [];

        if ($this->isEmptyValue($value)) {
            return $errors;
        }

        $validatorConfiguration = $fieldDef->getValidatorConfiguration();
        $constraints = isset($validatorConfiguration['EmailAddressValidator']) ?
            $validatorConfiguration['EmailAddressValidator'] :
            [];
        $validator = new EmailAddressValidator();
        $validator->initializeWithConstraints($constraints);

        if (!$validator->validate($value)) {
            return $validator->getMessage();
        }

        return [];
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_email';
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|\Ibexa\Core\FieldType\EmailAddress\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\EmailAddress\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\EmailAddress\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_string($value->email)) {
            throw new InvalidArgumentType(
                '$value->email',
                'string',
                $value->email
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\EmailAddress\Value $value
     *
     * @todo String normalization should occur here.
     */
    protected function getSortInfo(SPIValue $value): string
    {
        return $value->email;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\EmailAddress\Value $value
     */
    public function toHash(SPIValue $value): ?string
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->email;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_email.name', 'ibexa_fieldtypes')->setDesc('Email address'),
        ];
    }
}
