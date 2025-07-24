<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\BinaryFile;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Persistence\Content\FieldValue;
use Ibexa\Core\FieldType\BinaryBase\Type as BinaryBaseType;
use Ibexa\Core\FieldType\Value as BaseValue;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;

/**
 * The TextLine field type.
 *
 * This field type represents a simple string.
 */
class Type extends BinaryBaseType implements TranslationContainerInterface
{
    /**
     * Returns the field type identifier for this field type.
     *
     * @return string
     */
    public function getFieldTypeIdentifier(): string
    {
        return 'ibexa_binaryfile';
    }

    public function getEmptyValue(): Value
    {
        return new Value();
    }

    /**
     * Creates a specific value of the derived class from $inputValue.
     *
     * @param array $inputValue
     *
     * @return \Ibexa\Core\FieldType\BinaryFile\Value
     */
    protected function createValue(array $inputValue)
    {
        $inputValue = $this->regenerateUri($inputValue);

        return new Value($inputValue);
    }

    /**
     * Attempts to complete the data in $value.
     *
     * @param \Ibexa\Core\FieldType\BinaryFile\Value|\Ibexa\Core\FieldType\Value $value
     */
    protected function completeValue(Basevalue $value)
    {
        parent::completeValue($value);

        if (isset($value->downloadCount) && $value->downloadCount === null) {
            $value->downloadCount = 0;
        }
    }

    /**
     * @param \Ibexa\Core\FieldType\BinaryFile\Value $value
     */
    public function toHash(SPIValue $value): ?array
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        $hash = parent::toHash($value);

        $hash['downloadCount'] = $value->downloadCount;

        return $hash;
    }

    public function fromPersistenceValue(FieldValue $fieldValue): SPIValue
    {
        if ($fieldValue->externalData === null) {
            return $this->getEmptyValue();
        }

        /** @var \Ibexa\Core\FieldType\BinaryFile\Value $result */
        $result = parent::fromPersistenceValue($fieldValue);

        $result->downloadCount = (int)($fieldValue->externalData['downloadCount'] ?? 0);

        return $result;
    }

    public static function getTranslationMessages(): array
    {
        return [
            Message::create('ibexa_binaryfile.name', 'ibexa_fieldtypes')->setDesc('File'),
        ];
    }
}
