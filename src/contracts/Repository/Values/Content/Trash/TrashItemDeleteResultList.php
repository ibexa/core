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

class TrashItemDeleteResultList extends ValueObject implements \IteratorAggregate
{
    /** @var TrashItemDeleteResult[] */
    public array $items = [];

    /**
     * @return ArrayIterator<TrashItemDeleteResult>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
