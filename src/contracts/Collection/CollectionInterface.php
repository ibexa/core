<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use Countable;
use Iterator;
use IteratorAggregate;

/**
 * @template-covariant TValue
 *
 * @template-extends \IteratorAggregate<TValue>
 */
interface CollectionInterface extends Countable, IteratorAggregate
{
    public function isEmpty(): bool;

    /**
     * @phpstan-return TValue[]
     */
    public function toArray(): array;

    /**
     * @return \Iterator
     */
    public function getIterator(): Iterator;
}
