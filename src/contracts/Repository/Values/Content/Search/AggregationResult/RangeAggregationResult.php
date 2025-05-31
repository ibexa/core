<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use ArrayIterator;
use Countable;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;
use Iterator;
use IteratorAggregate;

/**
 * @phpstan-template TValue
 */
final class RangeAggregationResult extends AggregationResult implements IteratorAggregate, Countable
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry<TValue>[] */
    private iterable $entries;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry<TValue>[] $entries
     */
    public function __construct(string $name, iterable $entries = [])
    {
        parent::__construct($name);

        $this->entries = $entries;
    }

    public function isEmpty(): bool
    {
        return empty($this->entries);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry<TValue>[]
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }

    /**
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue> $key
     *
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry<TValue>|null
     */
    public function getEntry(Range $key): ?RangeAggregationResultEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->getKey() == $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue> $key
     */
    public function hasEntry(Range $key): bool
    {
        return $this->getEntry($key) !== null;
    }

    /**
     * Return available keys (ranges).
     *
     * @return iterable<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue>>
     */
    public function getKeys(): iterable
    {
        foreach ($this->entries as $entry) {
            yield $entry->getKey();
        }
    }

    public function count(): int
    {
        return iterator_count($this->entries);
    }

    /**
     * @return \Iterator<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<TValue>, int>
     */
    public function getIterator(): Iterator
    {
        if (empty($this->entries)) {
            return new ArrayIterator();
        }

        foreach ($this->entries as $entry) {
            yield $entry->getKey() => $entry->getCount();
        }
    }
}
