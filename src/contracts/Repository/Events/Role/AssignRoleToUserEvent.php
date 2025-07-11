<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Role;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\User;

final class AssignRoleToUserEvent extends AfterEvent
{
    private Role $role;

    private User $user;

    private ?RoleLimitation $roleLimitation;

    public function __construct(
        Role $role,
        User $user,
        ?RoleLimitation $roleLimitation = null
    ) {
        $this->role = $role;
        $this->user = $user;
        $this->roleLimitation = $roleLimitation;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getRoleLimitation(): ?RoleLimitation
    {
        return $this->roleLimitation;
    }
}
