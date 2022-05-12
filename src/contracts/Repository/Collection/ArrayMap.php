<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Collection;

use Ibexa\Contracts\Core\Repository\Exceptions\OutOfBoundsException;

/**
 * @template TKey
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Repository\Collection\AbstractInMemoryCollection<TValue>
 * @template-implements \Ibexa\Contracts\Core\Repository\Collection\MapInterface<TKey, TValue>
 */
class ArrayMap extends AbstractInMemoryCollection implements MapInterface
{
    public function get($key)
    {
        if (!$this->has($key)) {
            throw new OutOfBoundsException(sprintf("Collection does not contain element with key '%s'", $key));
        }

        return $this->items[$key];
    }

    public function has($key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * @param TValue[] $items
     *
     * @return \Ibexa\Contracts\Core\Repository\Collection\ArrayMap<TKey,TValue>
     */
    protected function createFrom(array $items): self
    {
        return new self($items);
    }
}
