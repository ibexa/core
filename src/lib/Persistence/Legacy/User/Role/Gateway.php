<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\User\Role;

use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct;

/**
 * User Role Gateway.
 *
 * @internal For internal use by Persistence Handlers.
 */
abstract class Gateway
{
    public const string ROLE_TABLE = 'ezrole';
    public const string POLICY_TABLE = 'ezpolicy';
    public const string POLICY_LIMITATION_TABLE = 'ezpolicy_limitation';
    public const string POLICY_LIMITATION_VALUE_TABLE = 'ezpolicy_limitation_value';
    public const string USER_ROLE_TABLE = 'ezuser_role';
    public const string ROLE_SEQ = 'ezrole_id_seq';
    public const string POLICY_SEQ = 'ezpolicy_id_seq';
    public const string POLICY_LIMITATION_SEQ = 'ezpolicy_limitation_id_seq';

    /**
     * Create a new role.
     */
    abstract public function createRole(Role $role): Role;

    /**
     * Copy an existing role.
     */
    abstract public function copyRole(Role $role): Role;

    /**
     * Load a specified role by $roleId.
     *
     * @phpstan-param int $status int<Role::STATUS_*>
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRole(int $roleId, int $status = Role::STATUS_DEFINED): array;

    /**
     * Load a specified role by $identifier.
     *
     * @phpstan-param int $status int<Role::STATUS_*>
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleByIdentifier(
        string $identifier,
        int $status = Role::STATUS_DEFINED
    ): array;

    /**
     * Load a role draft by the original role ID.
     *
     * @param int $roleId ID of the role the draft was created from.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleDraftByRoleId(int $roleId): array;

    /**
     * Load all roles.
     *
     * @phpstan-param int $status int<Role::STATUS_*>
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoles(int $status = Role::STATUS_DEFINED): array;

    /**
     * Load all roles associated with the given Content items.
     *
     * @param int[] $contentIds
     *
     * @phpstan-param int $status int<Role::STATUS_*>
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRolesForContentObjects(
        array $contentIds,
        int $status = Role::STATUS_DEFINED
    ): array;

    /**
     * Load a role assignment for specified assignment ID.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleAssignment(int $roleAssignmentId): array;

    /**
     * Load role assignment for specified User Group Content ID.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleAssignmentsByGroupId(
        int $groupId,
        bool $inherited = false
    ): array;

    /**
     * Load a Role assignments for given Role ID.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleAssignmentsByRoleId(int $roleId): array;

    /**
     * Load a Role assignments for given Role ID with provided $offset and $limit arguments.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadRoleAssignmentsByRoleIdWithOffsetAndLimit(
        int $roleId,
        int $offset,
        ?int $limit
    ): array;

    /**
     * Count Role's assignments taking into consideration related and existing user and user group objects.
     */
    abstract public function countRoleAssignments(int $roleId): int;

    /**
     * Return User Policies data associated with User.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadPoliciesByUserId(int $userId): array;

    /**
     * Update role (draft).
     *
     * Will not throw anything if location id is invalid.
     */
    abstract public function updateRole(RoleUpdateStruct $role): void;

    /**
     * Delete the specified role (draft).
     * If it's not a draft, the role assignments will also be deleted.
     *
     * @phpstan-param int $status int<Role::STATUS_*>
     */
    abstract public function deleteRole(int $roleId, int $status = Role::STATUS_DEFINED): void;

    /**
     * Publish the specified role draft.
     * If the draft was created from an existing role, published version will take the original role ID.
     *
     * @param int|null $originalRoleId ID of role the draft was created from. Will be null
     *                                 if the role draft was completely new.
     */
    abstract public function publishRoleDraft(int $roleDraftId, ?int $originalRoleId = null): void;

    /**
     * Add a Policy to Role.
     */
    abstract public function addPolicy(int $roleId, Policy $policy): Policy;

    /**
     * Add Limitations to an existing Policy.
     *
     * @param array<string, string[]> $limitations a map of Limitation identifiers to their raw values
     */
    abstract public function addPolicyLimitations(int $policyId, array $limitations): void;

    /**
     * Remove a Policy from Role.
     */
    abstract public function removePolicy(int $policyId): void;

    /**
     * Remove a Policy from Role.
     */
    abstract public function removePolicyLimitations(int $policyId): void;
}
