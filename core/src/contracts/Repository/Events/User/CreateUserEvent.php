<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;

final class CreateUserEvent extends AfterEvent
{
    private UserCreateStruct $userCreateStruct;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[] */
    private array $parentGroups;

    private User $user;

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[] $parentGroups
     */
    public function __construct(
        User $user,
        UserCreateStruct $userCreateStruct,
        array $parentGroups
    ) {
        $this->userCreateStruct = $userCreateStruct;
        $this->parentGroups = $parentGroups;
        $this->user = $user;
    }

    public function getUserCreateStruct(): UserCreateStruct
    {
        return $this->userCreateStruct;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[]
     */
    public function getParentGroups(): array
    {
        return $this->parentGroups;
    }

    public function getUser(): User
    {
        return $this->user;
    }
}
