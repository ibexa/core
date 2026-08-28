<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Features\Context;

use Behat\Behat\Context\Context;
use Behat\Step\Given;
use Behat\Step\Then;
use Ibexa\Contracts\Core\Repository\Exceptions as ApiExceptions;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Webmozart\Assert\Assert as Assertion;

/**
 * Sentences for Roles.
 */
class RoleContext implements Context
{
    /** @var \Ibexa\Contracts\Core\Repository\roleService */
    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Make sure a Role with name $name exists in parent group.
     *
     * @param string $name Role identifier
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Role
     */
    public function ensureRoleExists($name)
    {
        try {
            $role = $this->roleService->loadRoleByIdentifier($name);
        } catch (ApiExceptions\NotFoundException $e) {
            $roleCreateStruct = $this->roleService->newRoleCreateStruct($name);
            $roleDraft = $this->roleService->createRole($roleCreateStruct);
            $this->roleService->publishRoleDraft($roleDraft);
            $role = $this->roleService->loadRole($roleDraft->id);
        }

        return $role;
    }

    /**
     * Fetches the role with identifier.
     *
     * @param string $identifier Role identifier
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Role
     */
    public function getRole($identifier)
    {
        $role = null;
        try {
            $role = $this->roleService->loadRoleByIdentifier($identifier);
        } catch (ApiExceptions\NotFoundException $e) {
            // Role not found, do nothing, returns null
        }

        return $role;
    }

    /**
     * Ensures a role exists with name ':name', creating a new one if necessary.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Role
     */
    #[Given('a/an :name role exists')]
    public function iHaveRole(string $name): Role
    {
        return $this->ensureRoleExists($name);
    }

    /**
     * Verifies that a role with $name exists.
     */
    #[Then('I see that a/an :name role exists')]
    public function iSeeRole(string $name): void
    {
        $role = $this->getRole($name);
        Assertion::notNull(
            $role,
            "Couldn't find Role with name $name"
        );
    }

    #[Given(':name do not have any assigned policies')]
    public function noAssginedPolicies(string $name): void
    {
        $role = $this->getRole($name);
        Assertion::notNull(
            $role,
            "Couldn't find Role with name $name"
        );
        $policies = $role->getPolicies();
        Assertion::isEmpty($policies, "Role $name has policies associated");
    }

    #[Given(':name do not have any assigned Users and groups')]
    public function noAssigneGroups(string $name): void
    {
        $role = $this->getRole($name);
        Assertion::notNull(
            $role,
            "Couldn't find Role with name $name"
        );
        $roleAssigments = $this->roleService->getRoleAssignments($role);
        Assertion::isEmpty($roleAssigments, "Role $name has Users or groups associated");
    }

    /**
     * Verifies that a role with $name exists.
     */
    #[Then('I see that a/an :name role does not exists')]
    public function iDontSeeRole(string $name): void
    {
        $role = $this->getRole($name);
        Assertion::null(
            $role,
            "Found Role with name $name"
        );
    }
}
