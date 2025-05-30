<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use Ibexa\Contracts\Core\Exception\OutOfBoundsException;

/**
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Collection\AbstractInMemoryCollection<TValue>
 *
 * @template-implements \Ibexa\Contracts\Core\Collection\ListInterface<TValue>
 */
class ArrayList extends AbstractInMemoryCollection implements ListInterface
{
    /**
     * @phpstan-param TValue[] $items
     */
    public function __construct(array $items = [])
    {
        parent::__construct(array_values($items));
    }

    public function first(): mixed
    {
        if (($result = reset($this->items)) !== false) {
            return $result;
        }

        throw new OutOfBoundsException('Collection is empty');
    }

    public function last(): mixed
    {
        if (($result = end($this->items)) !== false) {
            return $result;
        }

        throw new OutOfBoundsException('Collection is empty');
    }

    /**
     * @phpstan-param TValue $value
     */
    public function contains($value): bool
    {
        return in_array($value, $this->items, true);
    }

    /**
     * @phpstan-param TValue[] $items
     *
     * @phpstan-return \Ibexa\Contracts\Core\Collection\ArrayList<TValue>
     */
    protected function createFrom(array $items): self
    {
        return new self($items);
    }
}
