<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Null;

use Ibexa\Contracts\Core\FieldType\Value as SPIValue;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\FieldType;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * ATTENTION: For testing purposes only!
 */
class Type extends FieldType
{
    /**
     * @param string $fieldTypeIdentifier Identifier for the field type that is being mocked.
     */
    public function __construct(protected readonly string $fieldTypeIdentifier)
    {
    }

    /**
     * Returns the field type identifier for this field type.
     */
    public function getFieldTypeIdentifier(): string
    {
        return $this->fieldTypeIdentifier;
    }

    /**
     * @param \Ibexa\Core\FieldType\Null\Value $value
     */
    public function getName(SPIValue $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return (string)$value->value;
    }

    public function getEmptyValue(): Value
    {
        return new Value(null);
    }

    /**
     * Inspects given $inputValue and potentially converts it into a dedicated value object.
     *
     * @param \Ibexa\Core\FieldType\Null\Value $inputValue
     *
     * @return \Ibexa\Core\FieldType\Null\Value The potentially converted and structurally plausible value.
     */
    protected function createValueFromInput($inputValue): Value
    {
        return $inputValue;
    }

    /**
     * Throws an exception if value structure is not of expected format.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     *
     * @param \Ibexa\Core\FieldType\Null\Value $value
     */
    protected function checkValueStructure(BaseValue $value)
    {
        // Does nothing
    }

    public function fromHash(mixed $hash): Value
    {
        return new Value($hash);
    }

    /**
     * @param \Ibexa\Core\FieldType\Null\Value $value
     */
    public function toHash(SPIValue $value): null
    {
        return null;
    }

    public function isSearchable(): bool
    {
        return true;
    }
}
