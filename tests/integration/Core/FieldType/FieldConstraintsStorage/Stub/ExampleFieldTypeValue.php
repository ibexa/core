<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\FieldType\FieldConstraintsStorage\Stub;

use Ibexa\Core\FieldType\Value;

final class ExampleFieldTypeValue extends Value
{
    public function __toString(): string
    {
        return '';
    }
}
