<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Validator to validate ranges in float values.
 *
 * Note that this validator can be limited by limitation on precision when
 * dealing with floating point numbers, and conversions.
 *
 * @property float $minFloatValue Minimum value for float.
 * @property float $maxFloatValue Maximum value for float.
 */
class FloatValueValidator extends BaseNumericValidator
{
    protected $constraints = [
        'minFloatValue' => null,
        'maxFloatValue' => null,
    ];

    protected $constraintsSchema = [
        'minFloatValue' => [
            'type' => 'float',
            'default' => null,
        ],
        'maxFloatValue' => [
            'type' => 'float',
            'default' => null,
        ],
    ];

    protected function getConstraintsValidationErrorMessage(string $name, mixed $value): ?string
    {
        return match ($name) {
            'minFloatValue', 'maxFloatValue' => $value !== null && !is_numeric($value)
                ? "Validator parameter '%parameter%' value must be of numeric type"
                : null,
            default => "Validator parameter '%parameter%' is unknown",
        };
    }

    /**
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    public function validate(BaseValue $value, ?FieldDefinition $fieldDefinition = null): bool
    {
        $isValid = true;

        if (isset($this->constraints['maxFloatValue']) && $value->value > $this->constraints['maxFloatValue']) {
            $this->errors[] = new ValidationError(
                'The value can not be higher than %size%.',
                null,
                [
                    '%size%' => $this->constraints['maxFloatValue'],
                ],
                'value'
            );
            $isValid = false;
        }

        if (isset($this->constraints['minFloatValue']) && $value->value < $this->constraints['minFloatValue']) {
            $this->errors[] = new ValidationError(
                'The value can not be lower than %size%.',
                null,
                [
                    '%size%' => $this->constraints['minFloatValue'],
                ],
                'value'
            );
            $isValid = false;
        }

        return $isValid;
    }
}
