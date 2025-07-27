<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;

final class AssignUserToUserGroupEvent extends AfterEvent
{
    private User $user;

    private UserGroup $userGroup;

    public function __construct(
        User $user,
        UserGroup $userGroup
    ) {
        $this->user = $user;
        $this->userGroup = $userGroup;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }
}
