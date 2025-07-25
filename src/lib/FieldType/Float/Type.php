<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Float;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\BaseNumericType;
use Ibexa\Core\FieldType\Validator;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Float field types.
 *
 * Represents floats.
 */
class Type extends BaseNumericType implements TranslationContainerInterface
{
    protected array $validatorConfigurationSchema = [
        'FloatValueValidator' => [
            'minFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
            'maxFloatValue' => [
                'type' => 'float',
                'default' => null,
            ],
        ],
    ];

    protected function getValidators(): array
    {
        return ['FloatValueValidator' => new Validator\FloatValueValidator()];
    }

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_float';
    }

    /**
     * @param \Ibexa\Core\FieldType\Float\Value $value
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
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    public function isEmptyValue(SPIValue $value): bool
    {
        return $value->value === null;
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param int|float|\Ibexa\Core\FieldType\Float\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Float\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_numeric($inputValue)) {
            $inputValue = (float)$inputValue;
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_float($value->value)) {
            throw new InvalidArgumentType(
                '$value->value',
                'float',
                $value->value
            );
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    protected function getSortInfo(SPIValue $value): ?float
    {
        return $value->value;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        return new Value((float)$hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Float\Value $value
     */
    public function toHash(SPIValue $value): ?float
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
            Message::create('ibexa_float.name', 'ibexa_fieldtypes')->setDesc('Float'),
        ];
    }
}
