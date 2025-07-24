<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Integer;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseNumericType;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Integer field types.
 *
 * Represents integers.
 */
class Type extends BaseNumericType implements TranslationContainerInterface
{
    protected array $validatorConfigurationSchema = [
        'IntegerValueValidator' => [
            'minIntegerValue' => [
                'type' => 'int',
                'default' => null,
            ],
            'maxIntegerValue' => [
                'type' => 'int',
                'default' => null,
            ],
        ],
    ];

    protected function getValidators(): array
    {
        return ['IntegerValueValidator' => new Validator\IntegerValueValidator()];
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_integer';
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->value === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|\Ibexa\Core\FieldType\Integer\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Integer\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_int($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_int($value->value)) {
            throw new InvalidArgumentType(
                '$value->value',
                'integer',
                $value->value
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    protected function getSortInfo(SPIValue $value): ?int
    {
        return $value->value;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value((int)$hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Integer\Value $value
     */
    public function toHash(SPIValue $value): ?int
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->value;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_integer.name', 'ibexa_fieldtypes')->setDesc('Integer'),
        ];
    }
}
