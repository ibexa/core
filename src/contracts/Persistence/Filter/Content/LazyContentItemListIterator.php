<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Filter\Content;

use Ibexa\Contracts\Core\Persistence\Filter\LazyListIterator;
use ReturnTypeWillChange;

/**
 * SPI Persistence Content Item list iterator.
 *
 * @internal for internal use by Repository Filtering
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\ContentItem
 */
class LazyContentItemListIterator extends LazyListIterator
{
    /**
     * @return \Ibexa\Contracts\Core\Persistence\Content\ContentItem[]
     *
     * @throws \Exception
     */
    #[ReturnTypeWillChange]
    public function getIterator(): iterable
    {
        yield from parent::getIterator();
    }
}
