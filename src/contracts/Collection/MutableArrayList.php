<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

/**
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Collection\ArrayList<TValue>
 * @template-implements \Ibexa\Contracts\Core\Collection\MutableListInterface<TValue>
 */
class MutableArrayList extends ArrayList implements MutableListInterface
{
    public function append($value): void
    {
        $this->items[] = $value;
    }

    public function prepend($value): void
    {
        array_unshift($this->items, $value);
    }

    public function remove($value): void
    {
        $idx = array_search($value, $this->items, true);
        if ($idx !== false) {
            array_splice($this->items, $idx, 1);
        }
    }

    public function clear(): void
    {
        $this->items = [];
    }

    protected function createFrom(array $items): MutableArrayList
    {
        return new MutableArrayList($items);
    }
}
