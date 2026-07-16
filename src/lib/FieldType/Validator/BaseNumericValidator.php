<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator;

abstract class BaseNumericValidator extends Validator
{
    abstract protected function getConstraintsValidationErrorMessage(
        string $name,
        mixed $value
    ): ?string;

    /**
     * @param array<string, mixed> $constraints
     */
    public function validateConstraints($constraints): array
    {
        $validationErrors = [];
        foreach ($constraints as $name => $value) {
            $validationErrorMessage = $this->getConstraintsValidationErrorMessage($name, $value);
            if (null !== $validationErrorMessage) {
                $validationErrors[] = new ValidationError(
                    $validationErrorMessage,
                    null,
                    [
                        '%parameter%' => $name,
                    ]
                );
            }
        }

        return $validationErrors;
    }
}
