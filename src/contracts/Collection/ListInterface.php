<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Collection;

use Ibexa\Contracts\Core\Repository\Exceptions\OutOfBoundsException;

/**
 * @template TValue
 *
 * @template-extends \Ibexa\Contracts\Core\Collection\CollectionInterface<TValue>
 */
interface ListInterface extends CollectionInterface
{
    /**
     * Return first element of collection.
     *
     * @return TValue
     *
     * @throws OutOfBoundsException if collection is empty
     */
    public function first(): mixed;

    /**
     * Return last element of collection.
     *
     * @return TValue
     *
     * @throws OutOfBoundsException if collection is empty
     */
    public function last(): mixed;

    /**
     * @param TValue $value
     */
    public function contains($value): bool;
}
