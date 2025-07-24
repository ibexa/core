<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\ISBN;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The ISBN field type.
 *
 * This field type represents a simple string.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    public const ISBN13_PREFIX_LENGTH = 3;
    public const ISBN13_CHECK_LENGTH = 1;
    public const ISBN13_LENGTH = 13;
    public const ISBN13_PREFIX_978 = '978';
    public const ISBN13_PREFIX_979 = '979';

    protected array $settingsSchema = [
        'isISBN13' => [
            'type' => 'boolean',
            'default' => true,
        ],
    ];

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_isbn';
    }

    /**
     * @param \Ibexa\Core\FieldType\ISBN\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @return string
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value->isbn;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * @param \Ibexa\Core\FieldType\ISBN\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->isbn === null || trim($value->isbn) === '';
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|\Ibexa\Core\FieldType\ISBN\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\ISBN\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = $this->fromHash($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\ISBN\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_string($value->isbn)) {
            throw new InvalidArgumentType(
                '$value->isbn',
                'string',
                $value->isbn
            );
        }
    }

    /**
     * Validates a field based on the validators in the field definition.
     *
     * Does not use validators.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition $fieldDef The field definition of the field
     * @param \Ibexa\Core\FieldType\ISBN\Value $value The field value for which an action is performed
     *
     * @return \Ibexa\Contracts\Core\FieldType\ValidationError[]
     *
     *@throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $validationErrors = [];
        if ($this->isEmptyValue($value)) {
            return $validationErrors;
        }

        $fieldSettings = $fieldDef->getFieldSettings();
        $isbnTestNumber = preg_replace("/[\s|\-]/", '', trim($value->isbn));

        // Check if value and settings are inline
        if ((!isset($fieldSettings['isISBN13']) || $fieldSettings['isISBN13'] === false)
            && strlen($isbnTestNumber) !== 10) {
            $validationErrors[] = new ValidationError(
                'ISBN-10 must be 10 character length',
                null,
                [],
                'isbn'
            );
        } elseif (strlen($isbnTestNumber) === 10) {
            // ISBN-10 check
            if (!$this->validateISBNChecksum($isbnTestNumber)) {
                $validationErrors[] = new ValidationError(
                    'ISBN value must be in a valid ISBN-10 format',
                    null,
                    [],
                    'isbn'
                );
            }
        } else {
            // ISBN-13 check
            if (!$this->validateISBN13Checksum($isbnTestNumber, $error)) {
                $validationErrors[] = new ValidationError(
                    $error,
                    null,
                    [],
                    'isbn'
                );
            }
        }

        return $validationErrors;
    }

    /**
     * @param \Ibexa\Core\FieldType\ISBN\Value $value
     */
    protected function getSortInfo(SPIValue $value): string
    {
        return $this->transformationProcessor->transformByGroup((string)$value, 'lowercase');
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null || $hash === '') {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\ISBN\Value $value
     */
    public function toHash(SPIValue $value): ?string
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->isbn;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public function validateFieldSettings(array $fieldSettings): array
    {
        $validationErrors = [];

        foreach ($fieldSettings as $name => $value) {
            if (!isset($this->settingsSchema[$name])) {
                $validationErrors[] = new ValidationError(
                    "Setting '%setting%' is unknown",
                    null,
                    [
                        '%setting%' => $name,
                    ],
                    "[$name]"
                );
                continue;
            }

            switch ($name) {
                case 'isISBN13':
                    if (!is_bool($value)) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' value must be of boolean type",
                            null,
                            [
                                '%setting%' => $name,
                            ],
                            "[$name]"
                        );
                    }
                    break;
            }
        }

        return $validationErrors;
    }

    /**
     * Validates the ISBN number.
     * All characters should be numeric except the last digit that may be the character X,
     * which should be calculated as 10.
     *
     * @param string $isbnNr A string containing the number without any dashes.
     *
     * @return bool
     */
    private function validateISBNChecksum($isbnNr): bool
    {
        $result = 0;
        $isbnNr = strtoupper($isbnNr);
        for ($i = 10; $i > 0; --$i) {
            if (is_numeric($isbnNr[$i - 1]) || ($i === 10 && $isbnNr[$i - 1] === 'X')) {
                if (($i === 1) && ($isbnNr[9] === 'X')) {
                    $result += 10 * $i;
                } else {
                    $result += $isbnNr[10 - $i] * $i;
                }
            } else {
                return false;
            }
        }

        return $result % 11 === 0;
    }

    /**
     *  Validates the ISBN-13 number.
     *
     * @param string $isbnNr A string containing the number without any dashes.
     * @param string $error is used to send back an error message that will be shown to the user if the
     *                      ISBN number validated.
     *
     * @return bool
     */
    private function validateISBN13Checksum($isbnNr, &$error): bool
    {
        if (!$isbnNr) {
            return false;
        }

        if (strlen($isbnNr) !== self::ISBN13_LENGTH) {
            $error = 'ISBN-13 must be 13 digit, digit count is: ' . strlen($isbnNr);

            return false;
        }

        if (substr($isbnNr, 0, self::ISBN13_PREFIX_LENGTH) !== self::ISBN13_PREFIX_978 &&
             substr($isbnNr, 0, self::ISBN13_PREFIX_LENGTH) !== self::ISBN13_PREFIX_979) {
            $error = 'ISBN-13 value must start with 978 or 979, got: ' . substr($isbnNr, 0, self::ISBN13_PREFIX_LENGTH);

            return false;
        }

        $checksum13 = 0;
        $weight13 = 1;
        //compute checksum
        $val = 0;
        for ($i = 0; $i < self::ISBN13_LENGTH; ++$i) {
            $val = $isbnNr[$i];
            if (!is_numeric($isbnNr[$i])) {
                $error = 'All ISBN-13 characters need to be numeric';

                return false;
            }
            $checksum13 = $checksum13 + $weight13 * $val;
            $weight13 = ($weight13 + 2) % 4;
        }
        if (($checksum13 % 10) !== 0) {
            // Calculate the last digit from the 12 first numbers.
            $checkDigit = (10 - (($checksum13 - (($weight13 + 2) % 4) * $val) % 10)) % 10;
            //bad checksum
            $error = 'Bad checksum, last digit of ISBN-13 should be ' . $checkDigit;

            return false;
        }

        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_isbn.name', 'ibexa_fieldtypes')->setDesc('ISBN'),
        ];
    }
}
