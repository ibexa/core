<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Trash;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Traversable;

class SearchResult extends ValueObject implements \IteratorAggregate
{
    public function __construct(array $properties = [])
    {
        if (isset($properties['totalCount'])) {
            $this->count = $properties['totalCount'];
        }

        parent::__construct($properties);
    }

    /**
     * The total number of Trash items.
     *
     * @phpstan-var int<0, max>
     */
    public int $totalCount = 0;

    /**
     * The total number of Trash items.
     *
     * @deprecated Property is here purely for BC with 5.x/6.x.
     *
     * @var int
     */
    public $count = 0;

    /**
     * The Trash items found for the query.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\TrashItem[]
     */
    public array $items = [];

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
