<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Collections\TotalCountAwareInterface;
use IteratorAggregate;

/**
 * A filtered Content items list iterator.
 */
final class ContentList implements IteratorAggregate, TotalCountAwareInterface
{
    /** @var int */
    private int $totalCount;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content[] */
    private array $contentItems;

    /**
     * @internal for internal use by Repository
     *
     * @param array<\Ibexa\Contracts\Core\Repository\Values\Content\Content> $contentItems
     */
    public function __construct(int $totalCount, array $contentItems)
    {
        $this->totalCount = $totalCount;
        $this->contentItems = $contentItems;
    }

    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return \ArrayIterator<int, \Ibexa\Contracts\Core\Repository\Values\Content\Content>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->contentItems);
    }
}

class_alias(ContentList::class, 'eZ\Publish\API\Repository\Values\Content\ContentList');
