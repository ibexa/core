<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use JMS\TranslationBundle\Translation\TranslationContainerInterface;

abstract class BaseNumericType extends FieldType implements TranslationContainerInterface
{
    abstract protected function isConfigurationValidatorSupported(string $validatorIdentifier): bool;

    abstract protected function validateValidatorConfigurationNumericConstraint(
        string $validatorIdentifier,
        string $name,
        mixed $value
    ): ?string;

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

        foreach ($validatorConfiguration as $validatorIdentifier => $constraints) {
            if (!$this->isConfigurationValidatorSupported($validatorIdentifier)) {
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

            foreach ($constraints as $name => $value) {
                $validationError = $this->validateValidatorConfigurationNumericConstraint($validatorIdentifier, $name, $value);
                if (null !== $validationError) {
                    $validationErrors[] = new ValidationError(
                        $validationError,
                        null,
                        [
                            '%parameter%' => $name,
                        ],
                        "[$validatorIdentifier][$name]"
                    );
                }
            }
        }

        return $validationErrors;
    }
}
