<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Ibexa\Core\FieldType\Value as BaseValue;

/**
 * Value for the TextLine field type.
 */
class ValueStub extends BaseValue
{
    public function __construct(public readonly string $value)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
