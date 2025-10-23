<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use IteratorAggregate;
use Traversable;

/**
 * @implements \IteratorAggregate<int, \Ibexa\Contracts\Core\Repository\Values\Notification\Notification>
 */
class NotificationList extends ValueObject implements IteratorAggregate
{
    /** @phpstan-var int<0, max> */
    public int $totalCount = 0;

    /** @var Notification[] */
    public array $items = [];

    /**
     * {@inheritdoc}
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
