<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;

/**
 * Interface for generating ranges for aggregations.
 *
 * @phpstan-template TValue
 */
interface RangesGeneratorInterface
{
    /**
     * @return Range<TValue>[]
     */
    public function generate(): iterable;
}
