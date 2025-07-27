<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Validator;

use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Validator for checking min. and max. length of strings.
 *
 * @property int $maxStringLength The maximum allowed length of the string.
 * @property int $minStringLength The minimum allowed length of the string.
 */
class StringLengthValidator extends Validator
{
    private const string PARAMETER_NAME = '%parameter%';

    protected $constraints = [
        'maxStringLength' => false,
        'minStringLength' => false,
    ];

    protected $constraintsSchema = [
        'minStringLength' => [
            'type' => 'int',
            'default' => 0,
        ],
        'maxStringLength' => [
            'type' => 'int',
            'default' => null,
        ],
    ];

    public function validateConstraints($constraints)
    {
        $validationErrors = [];
        foreach ($constraints as $name => $value) {
            switch ($name) {
                case 'minStringLength':
                case 'maxStringLength':
                    if ($value !== false && !is_int($value) && !(null === $value)) {
                        $validationErrors[] = new ValidationError(
                            sprintf('Validator parameter \'%s\' value must be of integer type', self::PARAMETER_NAME),
                            null,
                            [
                                self::PARAMETER_NAME => $name,
                            ]
                        );
                    } elseif ($value < 0) {
                        $validationErrors[] = new ValidationError(
                            "Validator parameter '%parameter%' value can't be negative",
                            null,
                            [
                                self::PARAMETER_NAME => $name,
                            ]
                        );
                    }
                    break;
                default:
                    $validationErrors[] = new ValidationError(
                        "Validator parameter '%parameter%' is unknown",
                        null,
                        [
                            self::PARAMETER_NAME => $name,
                        ]
                    );
            }
        }

        // if no errors above, check if minStringLength is shorter or equal than maxStringLength
        if (empty($validationErrors) && !$this->validateConstraintsOrder($constraints)) {
            $validationErrors[] = new ValidationError(
                "Validator parameter 'maxStringLength' can't be shorter than validator parameter 'minStringLength' value"
            );
        }

        return $validationErrors;
    }

    /**
     * Check if max string length is greater or equal than min string length in
     * case both are set. Returns also true in case one of them is not set.
     *
     * @param $constraints
     *
     * @return bool
     */
    protected function validateConstraintsOrder($constraints): bool
    {
        return !isset($constraints['minStringLength'], $constraints['maxStringLength'])
            || ($constraints['minStringLength'] <= $constraints['maxStringLength']);
    }

    /**
     * Checks if the string $value is in desired range.
     *
     * The range is determined by $maxStringLength and $minStringLength.
     *
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     *
     * @return bool
     */
    public function validate(BaseValue $value, ?FieldDefinition $fieldDefinition = null): bool
    {
        $isValid = true;

        // BC: these constraints can be not set, null, or false
        $minStringLength = $this->constraints['minStringLength'] ?? false;
        $maxStringLength = $this->constraints['maxStringLength'] ?? false;

        if ($maxStringLength !== false &&
            $maxStringLength !== 0 &&
            mb_strlen($value->text) > $maxStringLength) {
            $this->errors[] = new ValidationError(
                'The string can not exceed %size% character.',
                'The string can not exceed %size% characters.',
                [
                    '%size%' => $maxStringLength,
                ],
                'text'
            );
            $isValid = false;
        }
        if ($minStringLength !== false &&
            $minStringLength !== 0 &&
            mb_strlen($value->text) < $minStringLength) {
            $this->errors[] = new ValidationError(
                'The string cannot be shorter than %size% character.',
                'The string cannot be shorter than %size% characters.',
                [
                    '%size%' => $minStringLength,
                ],
                'text'
            );
            $isValid = false;
        }

        return $isValid;
    }
}
