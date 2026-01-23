<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IteratorAggregate;
use Traversable;

/**
 * @implements \IteratorAggregate<int, \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType>
 */
final class SearchResult extends ValueObject implements IteratorAggregate
{
    protected int $totalCount = 0;

    /** @var array<int, ContentType> */
    protected array $items = [];

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return array<int, ContentType>
     */
    public function getContentTypes(): array
    {
        return $this->items;
    }

    /**
     * @return Traversable<int, ContentType>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
