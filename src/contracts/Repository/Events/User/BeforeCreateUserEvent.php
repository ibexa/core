<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use UnexpectedValueException;

final class BeforeCreateUserEvent extends BeforeEvent
{
    private UserCreateStruct $userCreateStruct;

    /** @var UserGroup[] */
    private array $parentGroups;

    private ?User $user = null;

    /**
     * @param UserGroup[] $parentGroups
     */
    public function __construct(
        UserCreateStruct $userCreateStruct,
        array $parentGroups
    ) {
        $this->userCreateStruct = $userCreateStruct;
        $this->parentGroups = $parentGroups;
    }

    public function getUserCreateStruct(): UserCreateStruct
    {
        return $this->userCreateStruct;
    }

    /**
     * @return UserGroup[]
     */
    public function getParentGroups(): array
    {
        return $this->parentGroups;
    }

    public function getUser(): User
    {
        if (!$this->hasUser()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUser() or set it using setUser() before you call the getter.', User::class));
        }

        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    /**
     * @phpstan-assert-if-true !null $this->user
     */
    public function hasUser(): bool
    {
        return $this->user instanceof User;
    }
}
