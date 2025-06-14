<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Contracts\Core\Persistence\User;

use Ibexa\Contracts\Core\Persistence\User;

/**
 * Storage Engine handler for user module.
 */
interface Handler
{
    /**
     * Create a user.
     *
     * The User struct used to create the user will contain an ID which is used
     * to reference the user.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $user
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function create(User $user);

    /**
     * Loads user with user ID.
     *
     * @param mixed $userId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function load($userId);

    /**
     * Loads user with user login.
     *
     * Note: This method loads user by $login case in-sensitive on certain storage engines!
     *
     * @param string $login
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadByLogin($login);

    /**
     * Loads user with user email.
     *
     * Note: This method loads user by $email case in-sensitive on certain storage engines!
     *
     * @param string $email
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadByEmail(string $email): User;

    /**
     * Loads user(s) with user email.
     *
     * As earlier Ibexa versions supported several users having same email (ini config),
     * this function may return several users.
     *
     * Note: This method loads user by $email case in-sensitive on certain storage engines!
     *
     * @param string $email
     *
     * @return \Ibexa\Contracts\Core\Persistence\User[]
     */
    public function loadUsersByEmail(string $email): array;

    /**
     * Loads user with user hash.
     *
     * @param string $hash
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If user is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function loadUserByToken($hash);

    /**
     * Update the user information specified by the user struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $user
     */
    public function update(User $user);

    public function updatePassword(User $user): void;

    /**
     * Update the user information specified by the user token struct.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct $userTokenUpdateStruct
     */
    public function updateUserToken(UserTokenUpdateStruct $userTokenUpdateStruct);

    /**
     * Expires user token with user hash.
     *
     * @param string $hash
     */
    public function expireUserToken($hash);

    /**
     * Delete user with the given ID.
     *
     * @param mixed $userId
     *
     * @todo Throw on missing user?
     */
    public function delete($userId);

    /**
     * Create new role.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function createRole(RoleCreateStruct $createStruct);

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
    public function createRoleDraft($roleId);

    /**
     * Copies an existing role.
     */
    public function copyRole(RoleCopyStruct $copyStruct): Role;

    /**
     * Loads a specified role (draft) by $roleId.
     *
     * @param mixed $roleId
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRole($roleId, $status = Role::STATUS_DEFINED);

    /**
     * Loads a specified role (draft) by $identifier.
     *
     * @param string $identifier
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRoleByIdentifier($identifier, $status = Role::STATUS_DEFINED);

    /**
     * Loads a role draft by the original role ID.
     *
     * @param mixed $roleId ID of the role the draft was created from.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function loadRoleDraftByRoleId($roleId);

    /**
     * Loads all roles.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role[]
     */
    public function loadRoles();

    /**
     * Loads role assignment for specified assignment ID.
     *
     * @param mixed $roleAssignmentId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException If role assignment is not found
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment
     */
    public function loadRoleAssignment($roleAssignmentId);

    /**
     * Loads roles assignments Role.
     *
     * Role Assignments with same roleId and limitationIdentifier will be merged together into one.
     *
     * @param mixed $roleId
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function loadRoleAssignmentsByRoleId($roleId);

    /**
     * Loads Role's assignments based on provided $offset and $limit arguments.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function loadRoleAssignmentsByRoleIdWithOffsetAndLimit(int $roleId, int $offset, ?int $limit): array;

    /**
     * Counts Role's assignments taking into consideration related and existing user and user group objects.
     */
    public function countRoleAssignments(int $roleId): int;

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
    public function loadRoleAssignmentsByGroupId($groupId, $inherit = false);

    /**
     * Update role (draft).
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct $role
     */
    public function updateRole(RoleUpdateStruct $role);

    /**
     * Delete the specified role (draft).
     *
     * @param mixed $roleId
     * @param int $status One of Role::STATUS_DEFINED|Role::STATUS_DRAFT
     */
    public function deleteRole($roleId, $status = Role::STATUS_DEFINED);

    /**
     * Publish the specified role draft.
     *
     * @param mixed $roleDraftId
     */
    public function publishRoleDraft($roleDraftId);

    /**
     * Adds a policy to a role draft.
     *
     * @param mixed $roleId
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy
     *
     * @todo Throw on invalid Role Id?
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If $policy->limitation is empty (null, empty string/array..)
     */
    public function addPolicyByRoleDraft($roleId, Policy $policy);

    /**
     * Adds a policy to a role.
     *
     * @param mixed $roleId
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy
     *
     * @todo Throw on invalid Role Id?
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If $policy->limitation is empty (null, empty string/array..)
     */
    public function addPolicy($roleId, Policy $policy);

    /**
     * Update a policy.
     *
     * Replaces limitations values with new values.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\Policy $policy
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If $policy->limitation is empty (null, empty string/array..)
     */
    public function updatePolicy(Policy $policy);

    /**
     * Removes a policy from a role.
     *
     * @param mixed $policyId
     * @param mixed $roleId
     *
     * @todo Throw exception on missing role / policy?
     */
    public function deletePolicy($policyId, $roleId);

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
    public function assignRole($contentId, $roleId, array $limitation = null);

    /**
     * Un-assign a role.
     *
     * @param mixed $contentId The user or user group Id to un-assign the role from.
     * @param mixed $roleId
     */
    public function unassignRole($contentId, $roleId);

    /**
     * Un-assign a role, by assignment ID.
     *
     * @param mixed $roleAssignmentId The assignment ID.
     */
    public function removeRoleAssignment($roleAssignmentId);
}
