<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;

/**
 * Struct that stores extra target information for a RandomTarget object.
 */
class RandomTarget extends Target
{
    /**
     * For storage which does not support seed in this type,
     * it should be normalized to proper value inside storage implementation.
     */
    public ?int $seed;

    public function __construct(?int $seed)
    {
        $this->seed = $seed;
    }
}
