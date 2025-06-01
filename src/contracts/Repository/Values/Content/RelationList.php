<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IteratorAggregate;
use Traversable;

/**
 * List of relations.
 */
class RelationList extends ValueObject implements IteratorAggregate
{
    public int $totalCount = 0;

    /**
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface[]
     */
    public array $items = [];

    /**
     * @return \ArrayIterator<\Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
