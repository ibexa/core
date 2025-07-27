<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

/**
 * @template TKey
 *
 * @template-covariant TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Collection\CollectionInterface<TValue>
 */
interface MapInterface extends CollectionInterface
{
    /**
     * Returns value associated with given key.
     *
     * @param TKey $key
     *
     * @return TValue
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\OutOfBoundsException if map does not contain element with given key
     */
    public function get($key): mixed;

    /**
     * Returns true if the given key is defined within the map.
     *
     * @param TKey $key
     */
    public function has($key): bool;
}
