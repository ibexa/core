<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * Keyword field types.
 *
 * Represents keywords.
 */
class Type extends FieldType implements TranslationContainerInterface
{
    public const MAX_KEYWORD_LENGTH = 255;

    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_keyword';
    }

    /**
     * @param \Ibexa\Core\FieldType\Keyword\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return implode(', ', $value->values);
    }

    /**
     * Returns the fallback default value of field type when no such default
     * value is provided in the field definition in content types.
     *
     * @return \Ibexa\Core\FieldType\Keyword\Value
     */
    public function getEmptyValue()
    {
        return new Value([]);
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param mixed $inputValue
     *
     * @return \Ibexa\Core\FieldType\Keyword\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = [$inputValue];
        }

        if (is_array($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (!is_array($value->values)) {
            throw new InvalidArgumentType(
                '$value->values',
                'array',
                $value->values
            );
        }

        foreach ($value->values as $keyword) {
            if (!is_string($keyword) || mb_strlen($keyword) > self::MAX_KEYWORD_LENGTH) {
                throw new InvalidArgumentType(
                    '$value->values[]',
                    'string up to ' . self::MAX_KEYWORD_LENGTH . ' characters',
                    $keyword
                );
            }
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\Keyword\Value $fieldValue
     */
    public function validate(FieldDefinition $fieldDefinition, SPIValue $fieldValue): array
    {
        $validationErrors = [];

        foreach ($fieldValue->values as $keyword) {
            if (!is_string($keyword)) {
                $validationErrors[] = new ValidationError(
                    'Each keyword must be a string.',
                    null,
                    [],
                    'values'
                );
            } elseif (mb_strlen($keyword) > self::MAX_KEYWORD_LENGTH) {
                $validationErrors[] = new ValidationError(
                    'Keyword value must be less than or equal to ' . self::MAX_KEYWORD_LENGTH . ' characters.',
                    null,
                    [],
                    'values'
                );
            }
        }

        return $validationErrors;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSortInfo(BaseValue $value): string
    {
        return implode(',', $value->values);
    }

    /**
     * Converts an $hash to the Value defined by the field type.
     *
     * @param mixed $hash
     *
     * @return \Ibexa\Core\FieldType\Keyword\Value $value
     */
    public function fromHash($hash)
    {
        return new Value($hash);
    }

    /**
     * Converts a $Value to a hash.
     *
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     *
     * @return mixed
     */
    public function toHash(SPIValue $value)
    {
        return $value->values;
    }

    /**
     * Returns whether the field type is searchable.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * Converts a $value to a persistence value.
     *
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     *
     * @return \Ibexa\Contracts\Core\Persistence\Content\FieldValue
     */
    public function toPersistenceValue(SPIValue $value)
    {
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $value->values,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    /**
     * Converts a persistence $fieldValue to a Value.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\FieldValue $fieldValue
     *
     * @return \Ibexa\Core\FieldType\Keyword\Value
     */
    public function fromPersistenceValue(FieldValue $fieldValue)
    {
        return new Value($fieldValue->externalData);
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_keyword.name', 'ibexa_fieldtypes')->setDesc('Keywords'),
        ];
    }
}
