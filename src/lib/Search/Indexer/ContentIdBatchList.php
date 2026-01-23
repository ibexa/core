<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Indexer;

use Ibexa\Bundle\Core\Command\ReindexCommand;
use IteratorAggregate;
use Traversable;

/**
 * @internal content id batch list for ReindexCommand
 *
 * @see ReindexCommand
 *
 * @implements \IteratorAggregate<int, array<int>>
 */
final class ContentIdBatchList implements IteratorAggregate
{
    /** @var iterable<int, array<int>> */
    private iterable $list;

    private int $totalCount;

    /**
     * @param iterable<int, array<int>> $list
     */
    public function __construct(
        iterable $list,
        int $totalCount
    ) {
        $this->list = $list;
        $this->totalCount = $totalCount;
    }

    /**
     * return \Traversable<int, array<int>>.
     */
    public function getIterator(): Traversable
    {
        yield from $this->list;
    }

    public function getCount(): int
    {
        return $this->totalCount;
    }
}
