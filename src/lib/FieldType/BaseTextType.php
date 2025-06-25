<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as FieldTypeValueInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Core\Base\Exceptions\InvalidArgumentType;
use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * @internal
 *
 * Base implementation for TextLine\Type and TextBlock\Type which extends TextLine\Type.
 */
abstract class BaseTextType extends FieldType
{
    public function isSearchable(): bool
    {
        return true;
    }

    /**
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     */
    public function getName(
        FieldTypeValueInterface $value,
        FieldDefinition $fieldDefinition,
        string $languageCode
    ): string {
        return (string)$value->text;
    }

    /**
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     */
    public function isEmptyValue(FieldTypeValueInterface $value): bool
    {
        return $value->text === null || trim($value->text) === '';
    }

    /**
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the value does not match the expected structure.
     */
    protected function checkValueStructure(BaseValue $value): void
    {
        if (!is_string($value->text)) {
            throw new InvalidArgumentType(
                '$value->text',
                'string',
                $value->text
            );
        }
    }

    protected function buildUnknownValidatorError(string $parameterName, string $validatorIdentifier): ValidationError
    {
        return new ValidationError(
            "Validator '$parameterName' is unknown",
            null,
            [
                $parameterName => $validatorIdentifier,
            ]
        );
    }

    /**
     * @param \Ibexa\Core\FieldType\TextLine\Value $value
     */
    public function toHash(FieldTypeValueInterface $value): ?string
    {
        if ($this->isEmptyValue($value)) {
            return null;
        }

        return $value->text;
    }
}
