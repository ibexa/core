<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use ArrayIterator;
use Closure;
use Iterator;

/**
 * @template-covariant TValue
 *
 * @template-implements \Ibexa\Contracts\Core\Collection\CollectionInterface<TValue>
 * @template-implements \Ibexa\Contracts\Core\Collection\StreamableInterface<TValue>
 */
abstract class AbstractInMemoryCollection implements CollectionInterface, StreamableInterface
{
    /** @phpstan-var TValue[] */
    protected array $items;

    /**
     * @phpstan-param TValue[] $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function toArray(): array
    {
        return $this->items;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @phpstan-return static<TValue>
     */
    public function filter(Closure $predicate): self
    {
        return $this->createFrom(array_filter($this->items, $predicate, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @phpstan-return static<TValue>
     */
    public function map(Closure $function): self
    {
        return $this->createFrom(array_map($function, $this->items));
    }

    public function forAll(Closure $predicate): bool
    {
        foreach ($this->items as $i => $item) {
            if (!$predicate($item, $i)) {
                return false;
            }
        }

        return true;
    }

    public function exists(Closure $predicate): bool
    {
        foreach ($this->items as $i => $item) {
            if ($predicate($item, $i)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @template TValueFrom
     *
     * @param array<TValueFrom> $items
     *
     * @phpstan-return static<TValueFrom>
     */
    abstract protected function createFrom(array $items): self;
}
