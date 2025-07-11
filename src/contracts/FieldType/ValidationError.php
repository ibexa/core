<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\FieldType;

use Ibexa\Contracts\Core\Repository\Translatable;

/**
 * Interface for validation errors.
 *
 * Enforces to return a translatable message, since it will be necessary to
 * present validation errors to the user. Thus we need plural form handling and
 * replacements of placeholders and so on.
 */
interface ValidationError extends Translatable
{
    /**
     * Sets the target element on which the error occurred.
     *
     * E.g. Property of a Field value which didn't validate against validation.
     * Can be a property path compatible with Symfony PropertyAccess component.
     *
     * Examples:
     * - "[StringLengthValidator][minStringLength]" => Target is the "minStringLength" key under "StringLengthValidator" key (field type validator configuration)
     * - "my_field_definition_identifier"
     */
    public function setTarget(string $target): void;

    /**
     * Returns the target element on which the error occurred.
     */
    public function getTarget(): ?string;
}
