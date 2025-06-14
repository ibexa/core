<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\User;

use Ibexa\Contracts\Core\Persistence\User;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleAssignment;
use Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct;

/**
 * mapper for User related objects.
 */
class Mapper
{
    /**
     * Map user data into user object.
     *
     * @param array $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\User
     */
    public function mapUser(array $data)
    {
        $user = new User();
        $user->id = (int)$data['contentobject_id'];
        $user->login = $data['login'];
        $user->email = $data['email'];
        $user->passwordHash = $data['password_hash'];
        $user->hashAlgorithm = (int)$data['password_hash_type'];
        $user->passwordUpdatedAt = $data['password_updated_at'] !== null ? (int)$data['password_updated_at'] : null;
        $user->isEnabled = (bool)$data['is_enabled'];
        $user->maxLogin = $data['max_login'];

        return $user;
    }

    /**
     * Map data for a set of user data.
     *
     * @param array $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\User[]
     */
    public function mapUsers(array $data)
    {
        $users = [];
        foreach ($data as $row) {
            $users[] = $this->mapUser($row);
        }

        return $users;
    }

    /**
     * Map policy data to an array of policies.
     *
     * @param array $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Policy[]
     */
    public function mapPolicies(array $data): array
    {
        /** @var \Ibexa\Contracts\Core\Persistence\User\Policy[] */
        $policies = [];
        foreach ($data as $row) {
            $policyId = $row['ibexa_policy_id'];
            if (!isset($policies[$policyId]) && ($policyId !== null)) {
                $originalId = null;
                if ($row['ibexa_policy_original_id']) {
                    $originalId = (int)$row['ibexa_policy_original_id'];
                } elseif ($row['ibexa_role_version']) {
                    $originalId = (int)$policyId;
                }

                $policies[$policyId] = new Policy(
                    [
                        'id' => (int)$policyId,
                        'roleId' => (int)$row['ibexa_role_id'],
                        'originalId' => $originalId,
                        'module' => $row['ibexa_policy_module_name'],
                        'function' => $row['ibexa_policy_function_name'],
                        'limitations' => '*', // limitations must be '*' if not a non empty array of limitations
                    ]
                );
            }

            if (!$row['ibexa_policy_limitation_identifier']) {
                continue;
            } elseif ($policies[$policyId]->limitations === '*') {
                $policies[$policyId]->limitations = [];
            }

            if (!isset($policies[$policyId]->limitations[$row['ibexa_policy_limitation_identifier']])) {
                $policies[$policyId]->limitations[$row['ibexa_policy_limitation_identifier']] = [$row['ibexa_policy_limitation_value_value']];
            } elseif (!in_array($row['ibexa_policy_limitation_value_value'], $policies[$policyId]->limitations[$row['ibexa_policy_limitation_identifier']])) {
                $policies[$policyId]->limitations[$row['ibexa_policy_limitation_identifier']][] = $row['ibexa_policy_limitation_value_value'];
            }
        }

        return array_values($policies);
    }

    /**
     * Map role data to a role.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function mapRole(array $data)
    {
        $role = new Role();

        foreach ($data as $row) {
            if (empty($role->id)) {
                $role->id = (int)$row['ibexa_role_id'];
                $role->identifier = $row['ibexa_role_name'];
                $role->status = $row['ibexa_role_version'] != 0 ? Role::STATUS_DRAFT : Role::STATUS_DEFINED;
                $role->originalId = $row['ibexa_role_version'] ? (int)$row['ibexa_role_version'] : Role::STATUS_DEFINED;
                // skip name and description as they don't exist in legacy
            }
        }

        $role->policies = $this->mapPolicies($data);

        return $role;
    }

    /**
     * Map data for a set of roles.
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role[]
     */
    public function mapRoles(array $data)
    {
        $roleData = [];
        foreach ($data as $row) {
            $roleData[$row['ibexa_role_id']][] = $row;
        }

        $roles = [];
        foreach ($roleData as $data) {
            $roles[] = $this->mapRole($data);
        }

        return $roles;
    }

    /**
     * Map data for a set of role assignments.
     *
     * @param array $data
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleAssignment[]
     */
    public function mapRoleAssignments(array $data)
    {
        $roleAssignmentData = [];
        foreach ($data as $row) {
            $id = (int)$row['id'];
            $roleId = (int)$row['role_id'];
            $contentId = (int)$row['contentobject_id'];
            // if user already have full access to a role, continue
            if (isset($roleAssignmentData[$roleId][$contentId])
              && $roleAssignmentData[$roleId][$contentId] instanceof RoleAssignment) {
                continue;
            }

            $limitIdentifier = $row['limit_identifier'];
            if (!empty($limitIdentifier)) {
                $roleAssignmentData[$roleId][$contentId][$limitIdentifier][$id] = new RoleAssignment(
                    [
                        'id' => $id,
                        'roleId' => $roleId,
                        'contentId' => $contentId,
                        'limitationIdentifier' => $limitIdentifier,
                        'values' => [$row['limit_value']],
                    ]
                );
            } else {
                $roleAssignmentData[$roleId][$contentId] = new RoleAssignment(
                    [
                        'id' => $id,
                        'roleId' => $roleId,
                        'contentId' => $contentId,
                    ]
                );
            }
        }

        $roleAssignments = [];
        array_walk_recursive(
            $roleAssignmentData,
            static function ($roleAssignment) use (&$roleAssignments) {
                $roleAssignments[] = $roleAssignment;
            }
        );

        return $roleAssignments;
    }

    /**
     * Creates a create struct from an existing $role.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\Role $role
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct
     */
    public function createCreateStructFromRole(Role $role)
    {
        $createStruct = new RoleCreateStruct();

        $createStruct->identifier = $role->identifier;
        $createStruct->policies = $role->policies;

        return $createStruct;
    }

    /**
     * Maps properties from $struct to $role.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User\RoleCreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\User\Role
     */
    public function createRoleFromCreateStruct(RoleCreateStruct $createStruct)
    {
        $role = new Role();

        $role->identifier = $createStruct->identifier;
        $role->policies = $createStruct->policies;
        $role->status = Role::STATUS_DRAFT;

        return $role;
    }

    /**
     * Maps properties from $struct to $role.
     */
    public function createRoleFromCopyStruct(User\RoleCopyStruct $copyStruct): Role
    {
        $role = new Role();

        $role->identifier = $copyStruct->newIdentifier;
        $role->policies = $copyStruct->policies;
        $role->status = Role::STATUS_DEFINED;

        return $role;
    }
}
