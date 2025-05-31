<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Bookmark;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IteratorAggregate;
use Traversable;

/**
 * List of bookmarked locations.
 */
class BookmarkList extends ValueObject implements IteratorAggregate
{
    /**
     * The total number of bookmarks.
     */
    public int $totalCount = 0;

    /**
     * List of bookmarked locations.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\Location[]
     */
    public array $items = [];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
