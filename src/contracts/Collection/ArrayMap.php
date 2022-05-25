<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use Ibexa\Contracts\Core\Exception\OutOfBoundsException;

/**
 * @template TKey
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Collection\AbstractInMemoryCollection<TValue>
 * @template-implements \Ibexa\Contracts\Core\Collection\MapInterface<TKey, TValue>
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
     * @return \Ibexa\Contracts\Core\Collection\ArrayMap<TKey,TValue>
     */
    protected function createFrom(array $items): self
    {
        return new self($items);
    }
}
