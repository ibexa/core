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
 * Validate ranges of integer value.
 *
 * @property int $minIntegerValue The minimum allowed integer value.
 * @property int $maxIntegerValue The maximum allowed integer value.
 */
class IntegerValueValidator extends BaseNumericValidator
{
    protected $constraints = [
        'minIntegerValue' => null,
        'maxIntegerValue' => null,
    ];

    protected $constraintsSchema = [
        'minIntegerValue' => [
            'type' => 'int',
            'default' => 0,
        ],
        'maxIntegerValue' => [
            'type' => 'int',
            'default' => null,
        ],
    ];

    protected function getConstraintsValidationErrorMessage(string $name, mixed $value): ?string
    {
        return match ($name) {
            'minIntegerValue', 'maxIntegerValue' => $value !== null && !is_int($value)
                ? "Validator parameter '%parameter%' value must be of integer type"
                : null,
            default => "Validator parameter '%parameter%' is unknown",
        };
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function validate(BaseValue $value, ?FieldDefinition $fieldDefinition = null): bool
    {
        $isValid = true;

        if ($this->constraints['maxIntegerValue'] !== null && $value->value > $this->constraints['maxIntegerValue']) {
            $this->errors[] = new ValidationError(
                'The value can not be higher than %size%.',
                null,
                [
                    '%size%' => $this->constraints['maxIntegerValue'],
                ],
                'value'
            );
            $isValid = false;
        }

        if ($this->constraints['minIntegerValue'] !== null && $value->value < $this->constraints['minIntegerValue']) {
            $this->errors[] = new ValidationError(
                'The value can not be lower than %size%.',
                null,
                [
                    '%size%' => $this->constraints['minIntegerValue'],
                ],
                'value'
            );
            $isValid = false;
        }

        return $isValid;
    }
}
