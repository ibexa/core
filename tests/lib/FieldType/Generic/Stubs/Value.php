<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\FieldType\Generic\Stubs;

use Ibexa\Contracts\Core\FieldType\Value as ValueInterface;

final class Value implements ValueInterface
{
    private mixed $value;

    public function __construct(mixed $value = null)
    {
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }
}
