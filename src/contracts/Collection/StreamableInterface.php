<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use Closure;

/**
 * @template-covariant TValue
 */
interface StreamableInterface
{
    /**
     * Returns all the elements of this collection that satisfy the predicate.
     * The order of the elements is preserved.
     *
     * @phpstan-param Closure(TValue, array-key=): bool $predicate
     *
     * @phpstan-return static<TValue>
     */
    public function filter(Closure $predicate): self;

    /**
     * Applies the given function to each element in the collection and returns
     * a new collection with the elements returned by the function.
     *
     * @phpstan-param Closure(TValue): mixed $function
     *
     * @phpstan-return static<TValue>
     */
    public function map(Closure $function): self;

    /**
     * Tests whether the given predicate holds for all elements of this collection.
     *
     * @phpstan-param Closure(TValue, array-key=): bool $predicate
     */
    public function forAll(Closure $predicate): bool;

    /**
     * Tests the existence of an element that satisfies the given predicate.
     *
     * @phpstan-param Closure(TValue, array-key=): bool $predicate
     */
    public function exists(Closure $predicate): bool;
}
