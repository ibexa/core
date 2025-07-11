<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Role;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use UnexpectedValueException;

final class BeforeCreateRoleEvent extends BeforeEvent
{
    private RoleCreateStruct $roleCreateStruct;

    private ?RoleDraft $roleDraft = null;

    public function __construct(RoleCreateStruct $roleCreateStruct)
    {
        $this->roleCreateStruct = $roleCreateStruct;
    }

    public function getRoleCreateStruct(): RoleCreateStruct
    {
        return $this->roleCreateStruct;
    }

    public function getRoleDraft(): RoleDraft
    {
        if (!$this->hasRoleDraft()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasRoleDraft() or set it using setRoleDraft() before you call the getter.', RoleDraft::class));
        }

        return $this->roleDraft;
    }

    public function setRoleDraft(?RoleDraft $roleDraft): void
    {
        $this->roleDraft = $roleDraft;
    }

    public function hasRoleDraft(): bool
    {
        return $this->roleDraft instanceof RoleDraft;
    }
}
