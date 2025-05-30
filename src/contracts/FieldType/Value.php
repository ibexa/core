<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\FieldType;

use Stringable;

/**
 * Interface for field value classes.
 */
interface Value extends Stringable
{
    /**
     * Returns a string representation of the field value.
     */
    public function __toString(): string;
}
