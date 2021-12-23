<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub;

use Ibexa\Contracts\Core\FieldType\Value;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\FieldType\FieldType;

final class ExampleFieldType extends FieldType
{
    public const FIELD_TYPE_IDENTIFIER = 'example';

    protected function createValueFromInput($inputValue): ExampleFieldTypeValue
    {
        return new ExampleFieldTypeValue();
    }

    public function getFieldTypeIdentifier(): string
    {
        return self::FIELD_TYPE_IDENTIFIER;
    }

    public function getName(Value $value, FieldDefinition $fieldDefinition, string $languageCode): string
    {
        return '';
    }

    public function getEmptyValue(): ExampleFieldTypeValue
    {
        return new ExampleFieldTypeValue();
    }

    public function fromHash($hash): ExampleFieldTypeValue
    {
        return new ExampleFieldTypeValue();
    }

    protected function checkValueStructure(Value $value): void
    {
        // Nothing to do here.
    }

    public function toHash(Value $value)
    {
        return null;
    }

    protected static function checkValueType($value): void
    {
        // Nothing to do here.
    }

    public function validateFieldSettings($fieldSettings): array
    {
        return [];
    }

    public function validateValidatorConfiguration($validatorConfiguration): array
    {
        return [];
    }
}
