<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IteratorAggregate;

/**
 * List of relations.
 */
class RelationList extends ValueObject implements IteratorAggregate
{
    public int $totalCount = 0;

    /**
     * @var RelationListItemInterface[]
     */
    public array $items = [];

    /**
     * @return \Iterator<int, RelationListItemInterface>
     */
    public function getIterator(): \Iterator
    {
        return new ArrayIterator($this->items);
    }
}
