<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Common\FieldValueMapper;

use Ibexa\Core\Search\Common\FieldValueMapper;

/**
 * @internal
 */
abstract class BaseIntegerMapper extends FieldValueMapper
{
    /**
     * Convert to a proper search engine representation.
     */
    protected function convert(mixed $value): int
    {
        return (int)$value;
    }
}
