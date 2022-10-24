<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Events\User;

use Ibexa\Contracts\Core\Repository\Event\BeforeEvent;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct;
use UnexpectedValueException;

final class BeforeCreateUserGroupEvent extends BeforeEvent
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct */
    private $userGroupCreateStruct;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup */
    private $parentGroup;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup|null */
    private $userGroup;

    public function __construct(UserGroupCreateStruct $userGroupCreateStruct, UserGroup $parentGroup)
    {
        $this->userGroupCreateStruct = $userGroupCreateStruct;
        $this->parentGroup = $parentGroup;
    }

    public function getUserGroupCreateStruct(): UserGroupCreateStruct
    {
        return $this->userGroupCreateStruct;
    }

    public function getParentGroup(): UserGroup
    {
        return $this->parentGroup;
    }

    public function getUserGroup(): UserGroup
    {
        if (!$this->hasUserGroup()) {
            throw new UnexpectedValueException(sprintf('Return value is not set or not of type %s. Check hasUserGroup() or set it using setUserGroup() before you call the getter.', UserGroup::class));
        }

        return $this->userGroup;
    }

    public function setUserGroup(?UserGroup $userGroup): void
    {
        $this->userGroup = $userGroup;
    }

    public function hasUserGroup(): bool
    {
        return $this->userGroup instanceof UserGroup;
    }
}

class_alias(BeforeCreateUserGroupEvent::class, 'eZ\Publish\API\Repository\Events\User\BeforeCreateUserGroupEvent');