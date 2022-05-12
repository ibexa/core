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
 * @template-extends \Ibexa\Contracts\Core\Repository\Collection\MapInterface<TKey,TValue>
 */
interface MutableMapInterface extends MapInterface
{
    /**
     * @param TKey $key
     * @param TValue $value
     */
    public function set($key, $value): void;

    /**
     * @param TKey $key
     */
    public function unset($key): void;

    public function clear(): void;
}
