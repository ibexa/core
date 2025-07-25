<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Url;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The Url field type.
 *
 * This field type represents a simple string.
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
        return 'ibexa_url';
    }

    /**
     * @param \Ibexa\Core\FieldType\Url\Value|\Ibexa\Contracts\Core\FieldType\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value->text;
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param string|\Ibexa\Core\FieldType\Url\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Url\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue)
    {
        if (is_string($inputValue)) {
            $inputValue = new Value($inputValue);
        }

        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Url\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        if (null !== $value->link && !is_string($value->link)) {
            throw new InvalidArgumentType(
                '$value->link',
                'string',
                $value->link
            );
        }

        if (null !== $value->text && !is_string($value->text)) {
            throw new InvalidArgumentType(
                '$value->text',
                'string',
                $value->text
            );
        }
    }

    protected function getSortInfo(SPIValue $value): false
    {
        return false;
    }

    public function fromHash(mixed $hash): Value
    {
        if ($hash === null) {
            return $this->getEmptyValue();
        }

        if (isset($hash['text'])) {
            return new Value($hash['link'], $hash['text']);
        }

        return new Value($hash['link']);
    }

    /**
     * @param \Ibexa\Core\FieldType\Url\Value $value
     *
     * @return array{link: string|null, text: string|null}|null
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return ['link' => $value->link, 'text' => $value->text];
    }

    /**
     * @param \Ibexa\Core\FieldType\Url\Value $value
     */
    public function toPersistenceValue(SPIValue $value): FieldValue
    {
        return new FieldValue(
            [
                'data' => [
                    'urlId' => null,
                    'text' => $value->text,
                ],
                'externalData' => $value->link,
                'sortKey' => $this->getSortInfo($value),
            ]
        );
    }

    public function fromPersistenceValue(FieldValue $fieldValue): Value
    {
        if ($fieldValue->externalData === null) {
            return $this->getEmptyValue();
        }

        return new Value(
            $fieldValue->externalData,
            $fieldValue->data['text'] ?? null
        );
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_url.name', 'ibexa_fieldtypes')->setDesc('URL'),
        ];
    }
}
