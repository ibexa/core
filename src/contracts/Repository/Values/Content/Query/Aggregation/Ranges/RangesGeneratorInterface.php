<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges;

/**
 * Interface for generating ranges for aggregations.
 *
 * @phpstan-template TValue
 */
interface RangesGeneratorInterface
{
    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue>[]
     */
    public function generate(): iterable;
}
