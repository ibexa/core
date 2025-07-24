<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\FieldType\Integer;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the Integer field type.
 */
class Value extends BaseValue
{
    public function __construct(public readonly ?int $value = null)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
