<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;

use ArrayIterator;
use Countable;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult;
use Iterator;
use IteratorAggregate;

/**
 * Represents the result of a term aggregation.
 *
 * @phpstan-template TKey of object|scalar
 *
 * @phpstan-implements \IteratorAggregate<TKey, int>
 */
class TermAggregationResult extends AggregationResult implements IteratorAggregate, Countable
{
    /** @phpstan-var TermAggregationResultEntry<TKey>[] */
    private iterable $entries;

    /**
     * @phpstan-param iterable<TermAggregationResultEntry<TKey>> $entries
     */
    public function __construct(
        string $name,
        iterable $entries = []
    ) {
        parent::__construct($name);

        $this->entries = $entries;
    }

    public function isEmpty(): bool
    {
        return empty($this->entries);
    }

    /**
     * @phpstan-return TermAggregationResultEntry<TKey>[]
     */
    public function getEntries(): iterable
    {
        return $this->entries;
    }

    /**
     * @phpstan-param TKey $key
     *
     * @phpstan-return TermAggregationResultEntry<TKey>|null
     */
    public function getEntry(mixed $key): ?TermAggregationResultEntry
    {
        foreach ($this->entries as $entry) {
            if ($entry->getKey() == $key) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @phpstan-param TKey $key
     */
    public function hasEntry(mixed $key): bool
    {
        return $this->getEntry($key) !== null;
    }

    /**
     * Returns available keys (terms).
     *
     * @phpstan-return iterable<TKey>
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

    public function getIterator(): Iterator
    {
        if (empty($this->entries)) {
            return new ArrayIterator();
        }

        foreach ($this->entries as $entry) {
            yield $entry->getKey() => $entry->getCount();
        }
    }

    /**
     * Creates a TermAggregationResult from an Aggregation object.
     *
     * @phpstan-param iterable<TermAggregationResultEntry<TKey>> $entries
     *
     * @phpstan-return self<TKey>
     */
    public static function createForAggregation(
        Aggregation $aggregation,
        iterable $entries = []
    ): self {
        return new self($aggregation->getName(), $entries);
    }
}
