<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search;

use ArrayIterator;
use Countable;
use Ibexa\Contracts\Core\Repository\Exceptions\OutOfBoundsException;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Iterator;
use IteratorAggregate;

final class AggregationResultCollection implements Countable, IteratorAggregate
{
    /** @var AggregationResult[] */
    private array $entries;

    /**
     * @param AggregationResult[] $results
     */
    public function __construct(iterable $results = [])
    {
        $this->entries = [];
        foreach ($results as $result) {
            $this->entries[$result->getName()] = $result;
        }
    }

    /**
     * This method returns the aggregation result for the given aggregation name.
     *
     * @throws OutOfBoundsException
     */
    public function get(string $name): AggregationResult
    {
        if ($this->has($name)) {
            return $this->entries[$name];
        }

        throw new OutOfBoundsException(
            sprintf("Collection does not contain element with identifier '%s'", $name)
        );
    }

    /**
     * This method returns true if the aggregation result for the given aggregation name exists.
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->entries);
    }

    /**
     * Return first element of collection.
     *
     * @throws OutOfBoundsException
     */
    public function first(): AggregationResult
    {
        if (($result = reset($this->entries)) !== false) {
            return $result;
        }

        throw new OutOfBoundsException('Collection is empty');
    }

    /**
     * Return last element of collection.
     *
     * @throws OutOfBoundsException
     */
    public function last(): AggregationResult
    {
        if (($result = end($this->entries)) !== false) {
            return $result;
        }

        throw new OutOfBoundsException('Collection is empty');
    }

    /**
     * Checks whether the collection is empty (contains no elements).
     *
     * @return bool TRUE if the collection is empty, FALSE otherwise.
     */
    public function isEmpty(): bool
    {
        return empty($this->entries);
    }

    /**
     * Gets a native PHP array representation of the collection.
     *
     * @return FieldDefinition[]
     */
    public function toArray(): array
    {
        return $this->entries;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->entries);
    }

    public function count(): int
    {
        return count($this->entries);
    }
}
