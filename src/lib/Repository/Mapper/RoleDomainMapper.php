<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository\Mapper;

use Ibexa\Contracts\Core\Persistence\User\Policy as PersistencePolicy;
use Ibexa\Contracts\Core\Persistence\User\Role as PersistenceRole;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment as PersistenceRoleAssignment;
use Ibexa\Contracts\Core\Persistence\User\RoleCopyStruct as PersistenceRoleCopyStruct;
use Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct as PersistenceRoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Policy as APIPolicy;
use Ibexa\Contracts\Core\Repository\Values\User\Role as APIRole;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCopyStruct as APIRoleCopyStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct as APIRoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\Values\User\Policy;
use Ibexa\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Core\Repository\Values\User\RoleDraft;
use Ibexa\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Core\Repository\Values\User\UserRoleAssignment;

/**
 * Internal service to map Role objects between API and Persistence values.
 *
 * @internal Meant for internal use by Repository.
 */
class RoleDomainMapper
{
    protected LimitationService $limitationService;

    /**
     * @param \Ibexa\Core\Repository\Permission\LimitationService $limitationService
     */
    public function __construct(LimitationService $limitationService)
    {
        $this->limitationService = $limitationService;
    }

    /**
     * Maps provided Persistence Role value object to API Role value object.
     */
    public function buildDomainRoleObject(PersistenceRole $role): APIRole
    {
        $rolePolicies = [];
        foreach ($role->policies as $spiPolicy) {
            $rolePolicies[] = $this->buildDomainPolicyObject($spiPolicy);
        }

        return new Role(
            [
                'id' => $role->id,
                'identifier' => $role->identifier,
                'status' => $role->status,
                'policies' => $rolePolicies,
            ]
        );
    }

    /**
     * Builds a RoleDraft domain object from value object returned by persistence
     * Decorates Role.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\Role $spiRole
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\RoleDraft
     */
    public function buildDomainRoleDraftObject(PersistenceRole $spiRole): RoleDraft
    {
        return new RoleDraft(
            [
                'innerRole' => $this->buildDomainRoleObject($spiRole),
            ]
        );
    }

    /**
     * Maps provided Persistence Policy value object to API Policy value object.
     */
    public function buildDomainPolicyObject(PersistencePolicy $spiPolicy): APIPolicy
    {
        $policyLimitations = [];
        if ($spiPolicy->module !== '*' && $spiPolicy->function !== '*' && $spiPolicy->limitations !== '*') {
            foreach ($spiPolicy->limitations as $identifier => $values) {
                $policyLimitations[] = $this->limitationService->getLimitationType($identifier)->buildValue($values);
            }
        }

        $policy = new Policy(
            [
                'id' => $spiPolicy->id,
                'roleId' => $spiPolicy->roleId,
                'module' => $spiPolicy->module,
                'function' => $spiPolicy->function,
                'limitations' => $policyLimitations,
            ]
        );

        // Original ID is set on a persistence policy object, which means that it's a draft.
        if ($spiPolicy->originalId) {
            $policy = new PolicyDraft(['innerPolicy' => $policy, 'originalId' => $spiPolicy->originalId]);
        }

        return $policy;
    }

    /**
     * Builds the API UserRoleAssignment object from a provided Persistence RoleAssignment object.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleAssignment $spiRoleAssignment
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Role $role
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserRoleAssignment
     */
    public function buildDomainUserRoleAssignmentObject(PersistenceRoleAssignment $spiRoleAssignment, User $user, APIRole $role): UserRoleAssignment
    {
        $limitation = null;
        if (!empty($spiRoleAssignment->limitationIdentifier)) {
            $limitation = $this
                ->limitationService
                ->getLimitationType($spiRoleAssignment->limitationIdentifier)
                ->buildValue($spiRoleAssignment->values);
        }

        return new UserRoleAssignment(
            [
                'id' => $spiRoleAssignment->id,
                'limitation' => $limitation,
                'role' => $role,
                'user' => $user,
            ]
        );
    }

    /**
     * Builds the API UserGroupRoleAssignment object from provided Persistence RoleAssignment object.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleAssignment $spiRoleAssignment
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Role $role
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment
     */
    public function buildDomainUserGroupRoleAssignmentObject(PersistenceRoleAssignment $spiRoleAssignment, UserGroup $userGroup, APIRole $role): UserGroupRoleAssignment
    {
        $limitation = null;
        if (!empty($spiRoleAssignment->limitationIdentifier)) {
            $limitation = $this
                ->limitationService
                ->getLimitationType($spiRoleAssignment->limitationIdentifier)
                ->buildValue($spiRoleAssignment->values);
        }

        return new UserGroupRoleAssignment(
            [
                'id' => $spiRoleAssignment->id,
                'limitation' => $limitation,
                'role' => $role,
                'userGroup' => $userGroup,
            ]
        );
    }

    /**
     * Creates Persistence Role create struct from provided API role create struct.
     */
    public function buildPersistenceRoleCreateStruct(APIRoleCreateStruct $roleCreateStruct): PersistenceRoleCreateStruct
    {
        $policiesToCreate = $this->fillRoleStructWithPolicies($roleCreateStruct);

        return new PersistenceRoleCreateStruct(
            [
                'identifier' => $roleCreateStruct->identifier,
                'policies' => $policiesToCreate,
            ]
        );
    }

    /**
     * Creates Persistence Role copy struct from provided API role copy struct.
     */
    public function buildPersistenceRoleCopyStruct(APIRoleCopyStruct $roleCopyStruct, int $clonedId, int $status): PersistenceRoleCopyStruct
    {
        $policiesToCopy = $this->fillRoleStructWithPolicies($roleCopyStruct);

        return new PersistenceRoleCopyStruct(
            [
                'clonedId' => $clonedId,
                'newIdentifier' => $roleCopyStruct->newIdentifier,
                'status' => $status,
                'policies' => $policiesToCopy,
            ]
        );
    }

    protected function fillRoleStructWithPolicies(APIRoleCreateStruct $struct): array
    {
        $policies = [];
        foreach ($struct->getPolicies() as $policyStruct) {
            $policies[] = $this->buildPersistencePolicyObject(
                $policyStruct->module,
                $policyStruct->function,
                $policyStruct->getLimitations()
            );
        }

        return $policies;
    }

    /**
     * Creates a Persistence Policy value object from the provided module, function and limitations.
     *
     * @param string $module
     * @param string $function
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation[] $limitations
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy
     */
    public function buildPersistencePolicyObject($module, $function, array $limitations): PersistencePolicy
    {
        $limitationsToCreate = '*';
        if ($module !== '*' && $function !== '*' && !empty($limitations)) {
            $limitationsToCreate = [];
            foreach ($limitations as $limitation) {
                $limitationsToCreate[$limitation->getIdentifier()] = $limitation->limitationValues;
            }
        }

        return new PersistencePolicy(
            [
                'module' => $module,
                'function' => $function,
                'limitations' => $limitationsToCreate,
            ]
        );
    }
}
