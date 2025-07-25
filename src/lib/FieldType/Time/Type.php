<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Time;

use DateTime;
use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

class Type extends FieldType implements TranslationContainerInterface
{
    /**
     * Default value type empty.
     */
    public const DEFAULT_EMPTY = 0;

    /**
     * Default value type current date.
     */
    public const DEFAULT_CURRENT_TIME = 1;

    protected array $settingsSchema = [
        'useSeconds' => [
            'type' => 'bool',
            'default' => false,
        ],
        // One of the DEFAULT_* class constants
        'defaultType' => [
            'type' => 'choice',
            'default' => self::DEFAULT_EMPTY,
        ],
    ];

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_time';
    }

    /**
     * @param \Ibexa\Core\FieldType\Time\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     *
     * @throws \Exception
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        if ($this->isEmptyValue($value)) {
            return '';
        }

        $dateTime = new DateTime("@{$value->time}");

        return $dateTime->format('g:i:s a');
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|int|\DateTime|\Ibexa\Core\FieldType\Time\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Time\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = Value::fromString($inputValue);
        }

        if (is_int($inputValue)) {
            $inputValue = Value::fromTimestamp($inputValue);
        }

        if ($inputValue instanceof DateTime) {
            $inputValue = Value::fromDateTime($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Time\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_int($value->time)) {
            throw new InvalidArgumentType(
                '$value->time',
                'DateTime',
                $value->time
            );
        }
    }

    /**
     * Returns information for FieldValue->$sortKey relevant to the field type.
     *
     * @param \Ibexa\Core\FieldType\Time\Value $value
     */
    protected function getSortInfo(SPIValue $value): ?int
    {
        return $value->time;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Time\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->time === null;
    }

    /**
     * @param \Ibexa\Core\FieldType\Time\Value $value
     */
    public function toHash(SPIValue $value): ?int
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->time;
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
                case 'useSeconds':
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
                case 'defaultType':
                    $definedTypes = [
                        self::DEFAULT_EMPTY,
                        self::DEFAULT_CURRENT_TIME,
                    ];
                    if (!in_array($value, $definedTypes, true)) {
                        $validationErrors[] = new ValidationError(
                            "Setting '%setting%' is of unknown type",
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

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_time.name', 'ibexa_fieldtypes')->setDesc('Time'),
        ];
    }
}
