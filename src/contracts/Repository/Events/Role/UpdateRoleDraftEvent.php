<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\Role;

use Ibexa\Contracts\Core\Repository\Event\AfterEvent;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\RoleUpdateStruct;

final class UpdateRoleDraftEvent extends AfterEvent
{
    private RoleDraft $roleDraft;

    private RoleUpdateStruct $roleUpdateStruct;

    private RoleDraft $updatedRoleDraft;

    public function __construct(
        RoleDraft $updatedRoleDraft,
        RoleDraft $roleDraft,
        RoleUpdateStruct $roleUpdateStruct
    ) {
        $this->roleDraft = $roleDraft;
        $this->roleUpdateStruct = $roleUpdateStruct;
        $this->updatedRoleDraft = $updatedRoleDraft;
    }

    public function getRoleDraft(): RoleDraft
    {
        return $this->roleDraft;
    }

    public function getRoleUpdateStruct(): RoleUpdateStruct
    {
        return $this->roleUpdateStruct;
    }

    public function getUpdatedRoleDraft(): RoleDraft
    {
        return $this->updatedRoleDraft;
    }
}
