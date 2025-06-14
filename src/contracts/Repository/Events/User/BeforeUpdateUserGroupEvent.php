<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct;
use UnexpectedValueException;

final class BeforeUpdateUserGroupEvent extends BeforeEvent
{
    private UserGroup $userGroup;

    private UserGroupUpdateStruct $userGroupUpdateStruct;

    private ?UserGroup $updatedUserGroup = null;

    public function __construct(UserGroup $userGroup, UserGroupUpdateStruct $userGroupUpdateStruct)
    {
        $this->userGroup = $userGroup;
        $this->userGroupUpdateStruct = $userGroupUpdateStruct;
    }

    public function getUserGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function getUserGroupUpdateStruct(): UserGroupUpdateStruct
    {
        return $this->userGroupUpdateStruct;
    }

    public function getUpdatedUserGroup(): UserGroup
    {
        if (!$this->hasUpdatedUserGroup()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUpdatedUserGroup() or set it using setUpdatedUserGroup() before you call the getter.', UserGroup::class));
        }

        return $this->updatedUserGroup;
    }

    public function setUpdatedUserGroup(?UserGroup $updatedUserGroup): void
    {
        $this->updatedUserGroup = $updatedUserGroup;
    }

    /**
     * @phpstan-assert-if-true !null $this->updatedUserGroup
     */
    public function hasUpdatedUserGroup(): bool
    {
        return $this->updatedUserGroup instanceof UserGroup;
    }
}
