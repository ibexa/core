<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;

abstract class BaseNumericType extends FieldType
{
    /**
     * @return array<string, \Ibexa\Core\FieldType\Validator>
     */
    abstract protected function getValidators(): array;

    public function getValidator(string $validatorIdentifier): ?Validator
    {
        return $this->getValidators()[$validatorIdentifier] ?? null;
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
        $validatorValidationErrors = [];
        foreach ($validatorConfiguration as $validatorIdentifier => $constraints) {
            $validator = $this->getValidator($validatorIdentifier);
            if (null === $validator) {
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

            $validatorValidationErrors[] = $validator->validateConstraints($constraints);
        }

        return array_merge($validationErrors, ...$validatorValidationErrors);
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\PropertyNotFoundException
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $value): array
    {
        if ($this->isEmptyValue($value)) {
            return [];
        }

        $errors = [];
        $validatorConfiguration = $fieldDefinition->getValidatorConfiguration();
        foreach ($this->getValidators() as $validatorIdentifier => $validator) {
            $validator->initializeWithConstraints($validatorConfiguration[$validatorIdentifier] ?? []);
            if (!$validator->validate($value, $fieldDefinition)) {
                $errors[] = $validator->getMessage();
            }
        }

        return array_merge(...$errors);
    }
}
