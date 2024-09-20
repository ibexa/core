<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Pool;

/**
 * @template T of object
 */
interface PoolInterface
{
    public function has(string $alias): bool;

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return T
     */
    public function get(string $alias);

    /**
     * @return iterable<string,T>
     */
    public function getEntries(): iterable;

    public function setExceptionArgumentName(string $exceptionArgumentName): void;

    public function setExceptionMessageTemplate(string $exceptionMessageTemplate): void;
}
