<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType;

use Ibexa\Contracts\Core\FieldType\Value as ValueInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Abstract class for all field value classes.
 *
 * A field value object is associated with its field type.
 */
abstract class Value extends ValueObject implements ValueInterface
{
    /**
     * Returns a string representation of the field value.
     */
    abstract public function __toString(): string;
}
