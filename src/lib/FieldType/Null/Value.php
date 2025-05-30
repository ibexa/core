<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Null;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for Null field type.
 */
class Value extends BaseValue
{
    /**
     * Content of the value.
     *
     * @var mixed
     */
    public $value = null;

    /**
     * Construct a new Value object and initialize with $value.
     */
    public function __construct(mixed $value = null)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
