<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @phpstan-template TValue
 */
final class RangeAggregationResultEntry extends ValueObject
{
    /**
     * @phpstan-var Range<TValue>
     */
    private Range $key;

    private int $count;

    /**
     * @param Range<TValue> $key
     */
    public function __construct(
        Range $key,
        int $count
    ) {
        parent::__construct();

        $this->key = $key;
        $this->count = $count;
    }

    /**
     * @phpstan-return Range<TValue>
     */
    public function getKey(): Range
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
