<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Collection;

/**
 * @template TKey
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Repository\Collection\ArrayMap<TKey,TValue>
 * @template-implements \Ibexa\Contracts\Core\Repository\Collection\MutableMapInterface<TKey,TValue>
 */
class MutableArrayMap extends ArrayMap implements MutableMapInterface
{
    public function set($key, $value): void
    {
        $this->items[$key] = $value;
    }

    public function unset($key): void
    {
        unset($this->items[$key]);
    }

    public function clear(): void
    {
        $this->items = [];
    }

    protected function createFrom(array $items): MutableArrayMap
    {
        return new MutableArrayMap($items);
    }
}
