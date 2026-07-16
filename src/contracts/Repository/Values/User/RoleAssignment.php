<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User;

use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This value object represents an assignment od a user or user group to a role including a limitation.
 *
 * @property-read RoleLimitation|null $limitation the limitation of this role assignment
 * @property-read Role $role the role which is assigned to the user or user group
 */
abstract class RoleAssignment extends ValueObject
{
    /**
     * The unique id of the role assignment.
     *
     * @var int
     */
    protected int $id;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Returns the limitation of the role assignment.
     *
     * @return RoleLimitation|null
     */
    abstract public function getRoleLimitation(): ?RoleLimitation;

    /**
     * Returns the role to which the user or user group is assigned to.
     *
     * @return Role
     */
    abstract public function getRole(): Role;
}
