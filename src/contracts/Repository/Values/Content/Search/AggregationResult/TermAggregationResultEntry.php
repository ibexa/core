<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\AbstractTermAggregation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * Represents a single entry in a term aggregation result.
 *
 * @see AbstractTermAggregation
 *
 * @phpstan-template TKey of object|scalar
 */
final class TermAggregationResultEntry extends ValueObject
{
    /** @phpstan-var TKey */
    private mixed $key;

    private int $count;

    /**
     * @phpstan-param TKey $key
     */
    public function __construct(
        mixed $key,
        int $count
    ) {
        parent::__construct();

        $this->key = $key;
        $this->count = $count;
    }

    /**
     * @phpstan-return TKey
     */
    public function getKey(): mixed
    {
        return $this->key;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}
