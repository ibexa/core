<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\User;

use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Persistence\User\Handler as BaseUserHandler;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleCopyStruct;
use Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct;
use Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Core\Base\Exceptions\NotFoundException as NotFound;
use Ibexa\Core\Persistence\Legacy\Exception\RoleNotFound;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway as RoleGateway;
use Ibexa\Core\Persistence\Legacy\User\Role\LimitationConverter;
use LogicException;

/**
 * Storage Engine handler for user module.
 */
class Handler implements BaseUserHandler
{
    /**
     * Gateway for storing user data.
     *
     * @var \Ibexa\Core\Persistence\Legacy\User\Gateway
     */
    protected $userGateway;

    /**
     * Gateway for storing role data.
     *
     * @var \Ibexa\Core\Persistence\Legacy\User\Role\Gateway
     */
    protected $roleGateway;

    /**
     * Mapper for user related objects.
     *
     * @var \Ibexa\Core\Persistence\Legacy\User\Mapper
     */
    protected $mapper;

    /** @var \Ibexa\Core\Persistence\Legacy\User\Role\LimitationConverter */
    protected $limitationConverter;

    /**
     * Construct from userGateway.
     *
     * @param \Ibexa\Core\Persistence\Legacy\User\Gateway $userGateway
     * @param \Ibexa\Core\Persistence\Legacy\User\Role\Gateway $roleGateway
     * @param \Ibexa\Core\Persistence\Legacy\User\Mapper $mapper
     * @param \Ibexa\Core\Persistence\Legacy\User\Role\LimitationConverter $limitationConverter
     */
    public function __construct(Gateway $userGateway, RoleGateway $roleGateway, Mapper $mapper, LimitationConverter $limitationConverter)
    {
        $this->userGateway = $userGateway;
        $this->roleGateway = $roleGateway;
        $this->mapper = $mapper;
        $this->limitationConverter = $limitationConverter;
    }

    /**
     * Create a user.
     *
     * The User struct used to create the user will contain an ID which is used
     * to reference the user.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $user
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function create(User $user)
    {
        throw new NotImplementedException('This method should not be called, creation is done via content handler.');
    }

    /**
     * Loads user with user ID.
     *
     * @param mixed $userId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function load($userId)
    {
        $data = $this->userGateway->load($userId);

        if (empty($data)) {
            throw new NotFound('user', $userId);
        }

        return $this->mapper->mapUser(reset($data));
    }

    /**
     * Loads user with user login.
     *
     * @param string $login
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadByLogin($login)
    {
        $data = $this->userGateway->loadByLogin($login);

        if (empty($data)) {
            throw new NotFound('user', $login);
        } elseif (count($data) > 1) {
            throw new LogicException("Found more then one user with login '{$login}'");
        }

        return $this->mapper->mapUser($data[0]);
    }

    /**
     * Loads user(s) with user email.
     *
     * As earlier Ibexa versions supported several users having same email (ini config),
     * this function may return several users.
     *
     * @param string $email
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadByEmail(string $email): User
    {
        $data = $this->userGateway->loadByEmail($email);

        if (empty($data)) {
            throw new NotFound('user', $email);
        } elseif (count($data) > 1) {
            throw new LogicException("Found more then one user with login '{$email}'");
        }

        return $this->mapper->mapUser($data[0]);
    }

    /**
     * Loads user(s) with user email.
     *
     * As earlier Ibexa versions supported several users having same email (ini config),
     * this function may return several users.
     *
     * @param string $email
     *
     * @return \Ibexa\Contracts\Core\Persistence\User[]
     */
    public function loadUsersByEmail(string $email): array
    {
        $data = $this->userGateway->loadByEmail($email);

        if (empty($data)) {
            return [];
        }

        return $this->mapper->mapUsers($data);
    }

    /**
     * Loads user with user hash.
     *
     * @param string $hash
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadUserByToken($hash)
    {
        $data = $this->userGateway->loadUserByToken($hash);

        if (empty($data)) {
            throw new NotFound('user', $hash);
        }

        return $this->mapper->mapUser(reset($data));
    }

    /**
     * Update the user information specified by the user struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $user
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function update(User $user)
    {
        throw new NotImplementedException('This method should not be called, update is done via content handler.');
    }

    public function updatePassword(User $user): void
    {
        $this->userGateway->updateUserPassword($user);
    }

    /**
     * Update the user token information specified by the userToken struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct $userTokenUpdateStruct
     */
    public function updateUserToken(UserTokenUpdateStruct $userTokenUpdateStruct)
    {
        $this->userGateway->updateUserToken($userTokenUpdateStruct);
    }

    /**
     * Expires user account key with user hash.
     *
     * @param string $hash
     */
    public function expireUserToken($hash)
    {
        $this->userGateway->expireUserToken($hash);
    }

    /**
     * Delete user with the given ID.
     *
     * @param mixed $userId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function delete($userId)
    {
        throw new NotImplementedException('This method should not be called, delete is done via content handler.');
    }

    /**
     * Create new role draft.
     *
     * Sets status to Role::STATUS_DRAFT on the new returned draft.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function createRole(RoleCreateStruct $createStruct)
    {
        return $this->internalCreateRole($createStruct);
    }

    /**
     * Creates a draft of existing defined role.
     *
     * Sets status to Role::STATUS_DRAFT on the new returned draft.
     *
     * @param mixed $roleId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role with defined status is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function createRoleDraft($roleId)
    {
        $createStruct = $this->mapper->createCreateStructFromRole(
            $this->loadRole($roleId)
        );

        return $this->internalCreateRole($createStruct, $roleId);
    }

    /**
     * Internal method for creating Role.
     *
     * Used by self::createRole() and self::createRoleDraft()
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct $createStruct
     * @param mixed|null $roleId Used by self::createRoleDraft() to retain Role id in the draft
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    protected function internalCreateRole(RoleCreateStruct $createStruct, $roleId = null)
    {
        $createStruct = clone $createStruct;
        $role = $this->mapper->createRoleFromCreateStruct(
            $createStruct
        );
        $role->id = $roleId;
        $role->status = Role::STATUS_DRAFT;

        $this->roleGateway->createRole($role);

        foreach ($role->policies as $policy) {
            $this->addPolicyByRoleDraft($role->id, $policy);
        }

        return $role;
    }

    public function copyRole(RoleCopyStruct $copyStruct): Role
    {
        $role = $this->mapper->createRoleFromCopyStruct(
            $copyStruct
        );

        $this->roleGateway->copyRole($role);

        foreach ($role->policies as $policy) {
            $this->addPolicy($role->id, $policy);
        }

        return $role;
    }

    /**
     * Loads a specified role (draft) by $roleId and $status.
     *
     * @param mixed $roleId
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role with given status does not exist
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRole($roleId, $status = Role::STATUS_DEFINED)
    {
        $data = $this->roleGateway->loadRole($roleId, $status);

        if (empty($data)) {
            throw new RoleNotFound((string)$roleId, $status);
        }

        $role = $this->mapper->mapRole($data);
        foreach ($role->policies as $policy) {
            $this->limitationConverter->toSPI($policy);
        }

        return $role;
    }

    /**
     * Loads a specified role (draft) by $identifier and $status.
     *
     * @param string $identifier
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRoleByIdentifier($identifier, $status = Role::STATUS_DEFINED)
    {
        $data = $this->roleGateway->loadRoleByIdentifier($identifier, $status);

        if (empty($data)) {
            throw new RoleNotFound($identifier, $status);
        }

        $role = $this->mapper->mapRole($data);
        foreach ($role->policies as $policy) {
            $this->limitationConverter->toSPI($policy);
        }

        return $role;
    }

    /**
     * Loads a role draft by the original role ID.
     *
     * @param mixed $roleId ID of the role the draft was created from.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRoleDraftByRoleId($roleId)
    {
        $data = $this->roleGateway->loadRoleDraftByRoleId($roleId);

        if (empty($data)) {
            throw new RoleNotFound((string)$roleId, Role::STATUS_DRAFT);
        }

        $role = $this->mapper->mapRole($data);
        foreach ($role->policies as $policy) {
            $this->limitationConverter->toSPI($policy);
        }

        return $role;
    }

    /**
     * Loads all roles.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role[]
     */
    public function loadRoles()
    {
        $data = $this->roleGateway->loadRoles();

        $roles = $this->mapper->mapRoles($data);
        foreach ($roles as $role) {
            foreach ($role->policies as $policy) {
                $this->limitationConverter->toSPI($policy);
            }
        }

        return $roles;
    }

    /**
     * Update role (draft).
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct $role
     */
    public function updateRole(RoleUpdateStruct $role)
    {
        $this->roleGateway->updateRole($role);
    }

    /**
     * Delete the specified role (draft).
     *
     * @param mixed $roleId
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     */
    public function deleteRole($roleId, $status = Role::STATUS_DEFINED)
    {
        $role = $this->loadRole($roleId, $status);

        foreach ($role->policies as $policy) {
            $this->roleGateway->removePolicy($policy->id);
        }

        $this->roleGateway->deleteRole($role->id, $status);
    }

    /**
     * Publish the specified role draft.
     *
     * @param mixed $roleDraftId
     */
    public function publishRoleDraft($roleDraftId)
    {
        $roleDraft = $this->loadRole($roleDraftId, Role::STATUS_DRAFT);

        try {
            $originalRoleId = $roleDraft->originalId;
            $role = $this->loadRole($originalRoleId);
            $roleAssignments = $this->loadRoleAssignmentsByRoleId($role->id);
            $this->deleteRole($role->id);

            foreach ($roleAssignments as $roleAssignment) {
                if (empty($roleAssignment->limitationIdentifier)) {
                    $this->assignRole($roleAssignment->contentId, $originalRoleId);
                } else {
                    $this->assignRole(
                        $roleAssignment->contentId,
                        $originalRoleId,
                        [$roleAssignment->limitationIdentifier => $roleAssignment->values]
                    );
                }
            }
            $this->roleGateway->publishRoleDraft($roleDraft->id, $role->id);
        } catch (NotFound $e) {
            // If no published role is found, only publishing is needed, without specifying original role ID as there is none.
            $this->roleGateway->publishRoleDraft($roleDraft->id);
        }
    }

    /**
     * Adds a policy to a role draft.
     *
     * @param mixed $roleId
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy
     */
    public function addPolicyByRoleDraft($roleId, Policy $policy)
    {
        $legacyPolicy = clone $policy;
        $legacyPolicy->originalId = $policy->id;
        $this->limitationConverter->toLegacy($legacyPolicy);

        $this->roleGateway->addPolicy($roleId, $legacyPolicy);
        $policy->id = $legacyPolicy->id;
        $policy->originalId = $legacyPolicy->originalId;
        $policy->roleId = $legacyPolicy->roleId;

        return $policy;
    }

    /**
     * Adds a policy to a role.
     *
     * @param mixed $roleId
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy
     */
    public function addPolicy($roleId, Policy $policy)
    {
        $legacyPolicy = clone $policy;
        $this->limitationConverter->toLegacy($legacyPolicy);

        $this->roleGateway->addPolicy($roleId, $legacyPolicy);
        $policy->id = $legacyPolicy->id;
        $policy->roleId = $legacyPolicy->roleId;

        return $policy;
    }

    /**
     * Update a policy.
     *
     * Replaces limitations values with new values.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     */
    public function updatePolicy(Policy $policy)
    {
        $policy = clone $policy;
        $this->limitationConverter->toLegacy($policy);

        $this->roleGateway->removePolicyLimitations($policy->id);
        $this->roleGateway->addPolicyLimitations($policy->id, $policy->limitations === '*' ? [] : $policy->limitations);
    }

    /**
     * Removes a policy from a role.
     *
     * @param mixed $policyId
     * @param mixed $roleId
     */
    public function deletePolicy($policyId, $roleId)
    {
        // Each policy can only be associated to exactly one role. Thus it is
        // sufficient to use the policyId for identification and just remove
        // the policy completely.
        $this->roleGateway->removePolicy($policyId);
    }

    /**
     * Assigns role to a user or user group with given limitations.
     *
     * The limitation array looks like:
     * <code>
     *  array(
     *      'Subtree' => array(
     *          '/1/2/',
     *          '/1/4/',
     *      ),
     *      'Foo' => array( 'Bar' ),
     *      …
     *  )
     * </code>
     *
     * Where the keys are the limitation identifiers, and the respective values
     * are an array of limitation values. The limitation parameter is optional.
     *
     * @param mixed $contentId The groupId or userId to assign the role to.
     * @param mixed $roleId
     * @param array $limitation
     */
    public function assignRole($contentId, $roleId, array $limitation = null)
    {
        $limitation = $limitation ?: ['' => ['']];
        $this->userGateway->assignRole($contentId, $roleId, $limitation);
    }

    /**
     * Un-assign a role.
     *
     * @param mixed $contentId The user or user group Id to un-assign the role from.
     * @param mixed $roleId
     */
    public function unassignRole($contentId, $roleId)
    {
        $this->userGateway->removeRole($contentId, $roleId);
    }

    /**
     * Un-assign a role by assignment ID.
     *
     * @param mixed $roleAssignmentId The assignment ID.
     */
    public function removeRoleAssignment($roleAssignmentId)
    {
        $this->userGateway->removeRoleAssignmentById($roleAssignmentId);
    }

    /**
     * Loads role assignment for specified assignment ID.
     *
     * @param mixed $roleAssignmentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role assignment is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment
     */
    public function loadRoleAssignment($roleAssignmentId)
    {
        $data = $this->roleGateway->loadRoleAssignment($roleAssignmentId);

        if (empty($data)) {
            throw new NotFound('roleAssignment', $roleAssignmentId);
        }

        return $this->mapper->mapRoleAssignments($data)[0];
    }

    /**
     * Loads roles assignments Role.
     *
     * Role Assignments with same roleId and limitationIdentifier will be merged together into one.
     *
     * @param mixed $roleId
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function loadRoleAssignmentsByRoleId($roleId)
    {
        $data = $this->roleGateway->loadRoleAssignmentsByRoleId($roleId);

        if (empty($data)) {
            return [];
        }

        return $this->mapper->mapRoleAssignments($data);
    }

    /**
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function loadRoleAssignmentsByRoleIdWithOffsetAndLimit(int $roleId, int $offset, ?int $limit): array
    {
        $data = $this->roleGateway->loadRoleAssignmentsByRoleIdWithOffsetAndLimit($roleId, $offset, $limit);

        if (empty($data)) {
            return [];
        }

        return $this->mapper->mapRoleAssignments($data);
    }

    public function countRoleAssignments(int $roleId): int
    {
        return $this->roleGateway->countRoleAssignments($roleId);
    }

    /**
     * Loads roles assignments to a user/group.
     *
     * Role Assignments with same roleId and limitationIdentifier will be merged together into one.
     *
     * @param mixed $groupId In legacy storage engine this is the content object id roles are assigned to in ibexa_user_role.
     *                      By the nature of legacy this can currently also be used to get by $userId.
     * @param bool $inherit If true also return inherited role assignments from user groups.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function loadRoleAssignmentsByGroupId($groupId, $inherit = false)
    {
        $data = $this->roleGateway->loadRoleAssignmentsByGroupId($groupId, $inherit);

        if (empty($data)) {
            return [];
        }

        return $this->mapper->mapRoleAssignments($data);
    }
}
