<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Checkbox;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Checkbox field type.
 *
 * Represent boolean values.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_boolean';
    }

    /**
     * @param \Ibexa\Core\FieldType\Checkbox\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return $value->bool ? '1' : '0';
    }

    public function getEmptyValue(): Value
    {
        return new Value(false);
    }

    public function isEmptyValue(SPIValue $value): bool
    {
        return false;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param bool|\Ibexa\Core\FieldType\Checkbox\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Checkbox\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_bool($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Checkbox\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!$value instanceof Value) {
            throw new InvalidArgumentType(
                '$value',
                Value::class,
                $value
            );
        }

        if (!is_bool($value->bool)) {
            throw new InvalidArgumentType(
                '$value->bool',
                'boolean',
                $value->bool
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Checkbox\Value $value
     */
    protected function getSortInfo(SPIValue $value): int
    {
        return (int)$value->bool;
    }

    public function fromHash(mixed $hash): Value
    {
        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Checkbox\Value $value
     */
    public function toHash(SPIValue $value): bool
    {
        return $value->bool;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_boolean.name', 'ibexa_fieldtypes')->setDesc('Checkbox'),
        ];
    }
}
