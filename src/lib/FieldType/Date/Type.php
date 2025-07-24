<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Date;

use DateTime;
use DateTimeInterface;
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
    public const DEFAULT_CURRENT_DATE = 1;

    protected array $settingsSchema = [
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
        return 'ibexa_date';
    }

    /**
     * @param \Ibexa\Core\FieldType\Date\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        if ($this->isEmptyValue($value)) {
            return '';
        }

        return $value->date->format('l d F Y');
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|int|\DateTime|\Ibexa\Core\FieldType\Date\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Date\Value The potentially converted and structurally plausible value.
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
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Date\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!$value->date instanceof DateTime) {
            throw new InvalidArgumentType(
                (string)$value->date,
                'DateTime',
                $value->date
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Date\Value $value
     */
    protected function getSortInfo(SPIValue $value): int|null
    {
        return $value->date?->getTimestamp();
    }

    /**
     * @param mixed $hash Null or associative array containing one of the following (first value found in the order below is picked):
     *                    'rfc850': Date in RFC 850 format (DateTime::RFC850)
     *                    'timestring': Date in parseable string format supported by DateTime (e.g. 'now', '+3 days')
     *                    'timestamp': Unix timestamp
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        if (isset($hash['rfc850']) && $hash['rfc850']) {
            return Value::fromString($hash['rfc850']);
        }

        if (isset($hash['timestring']) && is_string($hash['timestring'])) {
            return Value::fromString($hash['timestring']);
        }

        return Value::fromTimestamp((int)$hash['timestamp']);
    }

    /**
     * @param \Ibexa\Core\FieldType\Date\Value $value
     *
     * @return array{timestamp: int, rfc850: string|null}|null
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        if ($value->date instanceof DateTime) {
            return [
                'timestamp' => $value->date->getTimestamp(),
                'rfc850' => $value->date->format(DateTimeInterface::RFC850),
            ];
        }

        return [
            'timestamp' => 0,
            'rfc850' => null,
        ];
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
                case 'defaultType':
                    $definedTypes = [
                        self::DEFAULT_EMPTY,
                        self::DEFAULT_CURRENT_DATE,
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
            Message::create('ibexa_date.name', 'ibexa_fieldtypes')->setDesc('Date'),
        ];
    }
}
