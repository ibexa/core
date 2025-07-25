<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

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

    public function getEmptyValue(): Value
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
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     */
    public function validate(FieldDefinition $fieldDef, SPIValue $value): array
    {
        $validationErrors = [];

        foreach ($value->values as $keyword) {
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
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     */
    protected function getSortInfo(SPIValue $value): string
    {
        return implode(',', $value->values);
    }

    public function fromHash(mixed $hash): Value
    {
        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     *
     * @return string[]
     */
    public function toHash(SPIValue $value): array
    {
        return $value->values;
    }

    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * @param \Ibexa\Core\FieldType\Keyword\Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        return new FieldValue(
            [
                'data' => null,
                'externalData' => $value->values,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    public function fromPersistenceValue(FieldValue $fieldValue): Value
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
