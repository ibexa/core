<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Trash;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Traversable;

class SearchResult extends ValueObject implements \IteratorAggregate
{
    /**
     * The total number of Trash items.
     *
     * @phpstan-var int<0, max>
     */
    public int $totalCount = 0;

    /**
     * The Trash items found for the query.
     *
     * @var TrashItem[]
     */
    public array $items = [];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
