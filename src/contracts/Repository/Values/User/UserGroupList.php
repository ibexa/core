<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Collections\TotalCountAwareInterface;
use IteratorAggregate;

/**
 * A page of user groups returned by {@see \Ibexa\Contracts\Core\Repository\UserService::findUserGroups()}.
 *
 * @implements \IteratorAggregate<int, \Ibexa\Contracts\Core\Repository\Values\User\UserGroup>
 */
final readonly class UserGroupList implements IteratorAggregate, TotalCountAwareInterface
{
    /**
     * @phpstan-param int<0, max> $totalCount
     *
     * @param list<\Ibexa\Contracts\Core\Repository\Values\User\UserGroup> $userGroups
     */
    public function __construct(
        private int $totalCount,
        private array $userGroups
    ) {
    }

    /**
     * Returns the total number of user groups matching the query (and readable by the current user), regardless of offset and limit.
     *
     * @phpstan-return int<0, max>
     */
    public function getTotalCount(): int
    {
        return $this->totalCount;
    }

    /**
     * @return list<\Ibexa\Contracts\Core\Repository\Values\User\UserGroup>
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @return \ArrayIterator<int, \Ibexa\Contracts\Core\Repository\Values\User\UserGroup>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->userGroups);
    }
}
