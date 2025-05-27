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
 * @template-extends \Ibexa\Contracts\Core\Collection\ListInterface<TValue>
 */
interface MutableListInterface extends ListInterface
{
    /**
     * @param TValue $value
     */
    public function append(mixed $value): void;

    /**
     * @param TValue $value
     */
    public function prepend(mixed $value): void;

    /**
     * @param TValue $value
     */
    public function remove(mixed $value): void;

    public function clear(): void;
}
