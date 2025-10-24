<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Pool;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;

/**
 * @template T of object
 */
interface PoolInterface
{
    public function has(string $alias): bool;

    /**
     * @throws InvalidArgumentException
     *
     * @phpstan-return T
     */
    public function get(string $alias);

    /**
     * @phpstan-return iterable<string,T>
     */
    public function getEntries(): iterable;

    public function setExceptionArgumentName(string $exceptionArgumentName): void;

    public function setExceptionMessageTemplate(string $exceptionMessageTemplate): void;
}
