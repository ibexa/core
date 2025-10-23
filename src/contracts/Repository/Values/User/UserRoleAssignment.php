<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

/**
 * This class represents a user to role assignment.
 *
 * @property-read User $user calls getUser()
 */
abstract class UserRoleAssignment extends RoleAssignment
{
    /**
     * Returns the user to which the role is assigned to.
     *
     * @return User
     */
    abstract public function getUser(): User;
}
