<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\User\Role\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway;

/**
 * User Role gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence User Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\User\Handler
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function createRole(Role $role): Role
    {
        // Role original ID is set when creating a draft from an existing role
        if ($role->status === Role::STATUS_DRAFT && $role->id) {
            $roleOriginalId = $role->id;
        } elseif ($role->status === Role::STATUS_DRAFT) {
            // Not using a constant here as this is legacy storage engine specific.
            // -1 means "Newly created role".
            $roleOriginalId = -1;
        } else {
            // Role::STATUS_DEFINED value is 0, which is the expected value for version column for this status.
            $roleOriginalId = Role::STATUS_DEFINED;
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::ROLE_TABLE)
            ->values(
                [
                    'is_new' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'name' => $query->createPositionalParameter(
                        $role->identifier,
                        ParameterType::STRING
                    ),
                    'value' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    // BC: "version" stores originalId when creating a draft from an existing role
                    'version' => $query->createPositionalParameter(
                        $roleOriginalId,
                        ParameterType::STRING
                    ),
                ]
            );
        $query->executeStatement();

        if (!isset($role->id) || (int)$role->id < 1 || $role->status === Role::STATUS_DRAFT) {
            $role->id = (int)$this->connection->lastInsertId(self::ROLE_SEQ);
        }

        $role->originalId = $roleOriginalId;

        return $role;
    }

    public function copyRole(Role $role): Role
    {
        $status = $role->status;

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::ROLE_TABLE)
            ->values(
                [
                    'is_new' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'name' => $query->createPositionalParameter(
                        $role->identifier,
                        ParameterType::STRING
                    ),
                    'value' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    // BC: "version" stores originalId when creating a draft from an existing role
                    'version' => $query->createPositionalParameter(
                        $status,
                        ParameterType::STRING
                    ),
                ]
            );
        $query->executeStatement();

        $role->id = (int)$this->connection->lastInsertId(self::ROLE_SEQ);

        return $role;
    }

    private function getLoadRoleQueryBuilder(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'r.id AS ibexa_role_id',
                'r.name AS ibexa_role_name',
                'r.version AS ibexa_role_version',
                'p.id AS ibexa_policy_id',
                'p.function_name AS ibexa_policy_function_name',
                'p.module_name AS ibexa_policy_module_name',
                'p.original_id AS ibexa_policy_original_id',
                'l.identifier AS ibexa_policy_limitation_identifier',
                'v.value AS ibexa_policy_limitation_value_value'
            )
            ->from(self::ROLE_TABLE, 'r')
            ->leftJoin('r', self::POLICY_TABLE, 'p', 'p.role_id = r.id')
            ->leftJoin('p', self::POLICY_LIMITATION_TABLE, 'l', 'l.policy_id = p.id')
            ->leftJoin('l', self::POLICY_LIMITATION_VALUE_TABLE, 'v', 'v.limitation_id = l.id');

        return $query;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function loadRole(int $roleId, int $status = Role::STATUS_DEFINED): array
    {
        $query = $this->getLoadRoleQueryBuilder();
        $query
            ->where(
                $query->expr()->eq(
                    'r.id',
                    $query->createPositionalParameter($roleId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $this->buildRoleDraftQueryConstraint($status, $query)
            )
            ->orderBy('p.id', 'ASC')
            ->addOrderBy('l.identifier', 'ASC')
            ->addOrderBy('v.value', 'ASC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function loadRoleByIdentifier(
        string $identifier,
        int $status = Role::STATUS_DEFINED
    ): array {
        $query = $this->getLoadRoleQueryBuilder();
        $query
            ->where(
                $query->expr()->eq(
                    'r.name',
                    $query->createPositionalParameter($identifier, ParameterType::STRING)
                )
            )
            ->andWhere(
                $this->buildRoleDraftQueryConstraint($status, $query)
            )
            ->orderBy('p.id', 'ASC')
            ->addOrderBy('l.identifier', 'ASC')
            ->addOrderBy('v.value', 'ASC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRoleDraftByRoleId(int $roleId): array
    {
        $query = $this->getLoadRoleQueryBuilder();
        // BC: "version" stores originalId when creating a draft from an existing role
        $query
            ->where(
                $query->expr()->eq(
                    'r.version',
                    $query->createPositionalParameter($roleId, ParameterType::STRING)
                )
            )
            ->orderBy('p.id', 'ASC');

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRoles(int $status = Role::STATUS_DEFINED): array
    {
        $query = $this->getLoadRoleQueryBuilder();
        $query->where(
            $this->buildRoleDraftQueryConstraint($status, $query)
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRolesForContentObjects(
        array $contentIds,
        int $status = Role::STATUS_DEFINED
    ): array {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'ur.contentobject_id AS ibexa_user_role_contentobject_id',
                'r.id AS ibexa_role_id',
                'r.name AS ibexa_role_name',
                'r.version AS ibexa_role_version',
                'p.id AS ibexa_policy_id',
                'p.function_name AS ibexa_policy_function_name',
                'p.module_name AS ibexa_policy_module_name',
                'p.original_id AS ibexa_policy_original_id',
                'l.identifier AS ibexa_policy_limitation_identifier',
                'v.value AS ibexa_policy_limitation_value_value'
            )
            ->from(self::USER_ROLE_TABLE, 'urs')
            ->leftJoin(
                'urs',
                self::ROLE_TABLE,
                'r',
                // BC: for drafts the "version" column contains the original role ID
                $expr->eq(
                    $status === Role::STATUS_DEFINED ? 'r.id' : 'r.version',
                    'urs.role_id'
                )
            )
            ->leftJoin('r', self::USER_ROLE_TABLE, 'ur', 'ur.role_id = r.id')
            ->leftJoin('r', self::POLICY_TABLE, 'p', 'p.role_id = r.id')
            ->leftJoin('p', self::POLICY_LIMITATION_TABLE, 'l', 'l.policy_id = p.id')
            ->leftJoin('l', self::POLICY_LIMITATION_VALUE_TABLE, 'v', 'v.limitation_id = l.id')
            ->where(
                $expr->in(
                    'urs.contentobject_id',
                    $query->createPositionalParameter($contentIds, Connection::PARAM_INT_ARRAY)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRoleAssignment(int $roleAssignmentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'id',
            'contentobject_id',
            'limit_identifier',
            'limit_value',
            'role_id'
        )->from(
            self::USER_ROLE_TABLE
        )->where(
            $query->expr()->eq(
                'id',
                $query->createPositionalParameter($roleAssignmentId, ParameterType::INTEGER)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRoleAssignmentsByGroupId(int $groupId, bool $inherited = false): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'id',
            'contentobject_id',
            'limit_identifier',
            'limit_value',
            'role_id'
        )->from(
            self::USER_ROLE_TABLE
        );

        if ($inherited) {
            $groupIds = $this->fetchUserGroups($groupId);
            $groupIds[] = $groupId;
            $query->where(
                $query->expr()->in(
                    'contentobject_id',
                    $query->createNamedParameter($groupIds, ArrayParameterType::INTEGER)
                )
            );
        } else {
            $query->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($groupId, ParameterType::INTEGER)
                )
            );
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadRoleAssignmentsByRoleId(int $roleId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'id',
            'contentobject_id',
            'limit_identifier',
            'limit_value',
            'role_id'
        )->from(
            self::USER_ROLE_TABLE
        )->where(
            $query->expr()->eq(
                'role_id',
                $query->createPositionalParameter($roleId, ParameterType::INTEGER)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadRoleAssignmentsByRoleIdWithOffsetAndLimit(int $roleId, int $offset, ?int $limit): array
    {
        $query = $this
            ->buildLoadRoleAssignmentsQuery(
                [
                    'user_role.id',
                    'user_role.contentobject_id',
                    'user_role.limit_identifier',
                    'user_role.limit_value',
                    'user_role.role_id',
                ],
                $roleId
            )
            ->setFirstResult($offset);

        if ($limit !== null) {
            $query->setMaxResults($limit);
        }

        return $query
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function countRoleAssignments(int $roleId): int
    {
        $query = $this->buildLoadRoleAssignmentsQuery(
            ['COUNT(user_role.id)'],
            $roleId
        );

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @param array<string> $columns
     */
    private function buildLoadRoleAssignmentsQuery(array $columns, int $roleId): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$columns)
            ->from(self::USER_ROLE_TABLE, 'user_role')
            ->innerJoin(
                'user_role',
                ContentGateway::CONTENT_ITEM_TABLE,
                'content_object',
                'user_role.contentobject_id = content_object.id'
            )->where(
                $query->expr()->eq(
                    'role_id',
                    $query->createPositionalParameter($roleId, ParameterType::INTEGER)
                )
            );

        return $query;
    }

    public function loadPoliciesByUserId(int $userId): array
    {
        $groupIds = $this->fetchUserGroups($userId);
        $groupIds[] = $userId;

        return $this->loadRolesForContentObjects($groupIds);
    }

    /**
     * Fetch all group IDs the user belongs to.
     *
     * This method will return Content ids of all ancestor Locations for the given $userId.
     * Note that not all of these might be used as user groups,
     * but we will need to check all of them.
     *
     * @param int $userId
     *
     * @return array
     */
    private function fetchUserGroups(int $userId): array
    {
        $nodeIDs = $this->getAncestorLocationIdsForUser($userId);

        if (empty($nodeIDs)) {
            return [];
        }

        $query = $this->connection->createQueryBuilder();
        $query
            ->select('c.id')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 't')
            ->innerJoin('t', ContentGateway::CONTENT_ITEM_TABLE, 'c', 'c.id = t.contentobject_id')
            ->where(
                $query->expr()->in(
                    't.node_id',
                    $nodeIDs
                )
            );

        return $query->executeQuery()->fetchFirstColumn();
    }

    public function updateRole(RoleUpdateStruct $role): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::ROLE_TABLE)
            ->set(
                'name',
                $query->createPositionalParameter($role->identifier, ParameterType::STRING)
            )
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($role->id, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    public function deleteRole(int $roleId, int $status = Role::STATUS_DEFINED): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete(self::ROLE_TABLE)
            ->where(
                $expr->eq(
                    'id',
                    $query->createPositionalParameter($roleId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $this->buildRoleDraftQueryConstraint($status, $query, self::ROLE_TABLE)
            );

        if ($status !== Role::STATUS_DRAFT) {
            $this->deleteRoleAssignments($roleId);
        }
        $query->executeStatement();
    }

    public function publishRoleDraft(int $roleDraftId, ?int $originalRoleId = null): void
    {
        $this->markRoleAsPublished($roleDraftId, $originalRoleId);
        $this->publishRolePolicies($roleDraftId, $originalRoleId);
    }

    public function addPolicy(int $roleId, Policy $policy): Policy
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::POLICY_TABLE)
            ->values(
                [
                    'function_name' => $query->createPositionalParameter(
                        $policy->function,
                        ParameterType::STRING
                    ),
                    'module_name' => $query->createPositionalParameter(
                        $policy->module,
                        ParameterType::STRING
                    ),
                    'original_id' => $query->createPositionalParameter(
                        $policy->originalId ?? 0,
                        ParameterType::INTEGER
                    ),
                    'role_id' => $query->createPositionalParameter($roleId, ParameterType::INTEGER),
                ]
            );
        $query->executeStatement();

        $policy->id = (int)$this->connection->lastInsertId(self::POLICY_SEQ);
        $policy->roleId = $roleId;

        // Handle the only valid non-array value "*" by not inserting
        // anything. Still has not been documented by Ibexa. So we
        // assume this is the right way to handle it.
        if (is_array($policy->limitations)) {
            $this->addPolicyLimitations($policy->id, $policy->limitations);
        }

        return $policy;
    }

    public function addPolicyLimitations(int $policyId, array $limitations): void
    {
        foreach ($limitations as $identifier => $values) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->insert(self::POLICY_LIMITATION_TABLE)
                ->values(
                    [
                        'identifier' => $query->createPositionalParameter(
                            $identifier,
                            ParameterType::STRING
                        ),
                        'policy_id' => $query->createPositionalParameter(
                            $policyId,
                            ParameterType::INTEGER
                        ),
                    ]
                );
            $query->executeStatement();

            $limitationId = (int)$this->connection->lastInsertId(self::POLICY_LIMITATION_SEQ);

            foreach ($values as $value) {
                $query = $this->connection->createQueryBuilder();
                $query
                    ->insert(self::POLICY_LIMITATION_VALUE_TABLE)
                    ->values(
                        [
                            'value' => $query->createPositionalParameter(
                                $value,
                                ParameterType::STRING
                            ),
                            'limitation_id' => $query->createPositionalParameter(
                                $limitationId,
                                ParameterType::INTEGER
                            ),
                        ]
                    );
                $query->executeStatement();
            }
        }
    }

    public function removePolicy(int $policyId): void
    {
        $this->removePolicyLimitations($policyId);

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::POLICY_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($policyId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    /**
     * @param int[] $limitationIds
     */
    private function deletePolicyLimitations(array $limitationIds): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::POLICY_LIMITATION_TABLE)
            ->where(
                $query->expr()->in(
                    'id',
                    $query->createPositionalParameter(
                        $limitationIds,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        $query->executeStatement();
    }

    /**
     * @param int[] $limitationValueIds
     */
    private function deletePolicyLimitationValues(array $limitationValueIds): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::POLICY_LIMITATION_VALUE_TABLE)
            ->where(
                $query->expr()->in(
                    'id',
                    $query->createPositionalParameter(
                        $limitationValueIds,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );
        $query->executeStatement();
    }

    private function loadPolicyLimitationValues(int $policyId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'l.id AS ibexa_policy_limitation_id',
                'v.id AS ibexa_policy_limitation_value_id'
            )
            ->from(self::POLICY_TABLE, 'p')
            ->leftJoin('p', self::POLICY_LIMITATION_TABLE, 'l', 'l.policy_id = p.id')
            ->leftJoin('l', self::POLICY_LIMITATION_VALUE_TABLE, 'v', 'v.limitation_id = l.id')
            ->where(
                $query->expr()->eq(
                    'p.id',
                    $query->createPositionalParameter($policyId, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function removePolicyLimitations(int $policyId): void
    {
        $limitationValues = $this->loadPolicyLimitationValues($policyId);

        $limitationIds = array_map(
            'intval',
            array_column($limitationValues, 'ibexa_policy_limitation_id')
        );
        $limitationValueIds = array_map(
            'intval',
            array_column($limitationValues, 'ibexa_policy_limitation_value_id')
        );

        if (!empty($limitationValueIds)) {
            $this->deletePolicyLimitationValues($limitationValueIds);
        }

        if (!empty($limitationIds)) {
            $this->deletePolicyLimitations($limitationIds);
        }
    }

    /**
     * Delete Role assignments to Users.
     */
    private function deleteRoleAssignments(int $roleId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::USER_ROLE_TABLE)
            ->where(
                $query->expr()->eq(
                    'role_id',
                    $query->createPositionalParameter($roleId, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    /**
     * Load all Ancestor Location IDs of the given User Location.
     *
     * @param int $userId
     *
     * @return int[]
     */
    private function getAncestorLocationIdsForUser(int $userId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('t.path_string')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 't')
            ->where(
                $query->expr()->eq(
                    't.contentobject_id',
                    $query->createPositionalParameter($userId, ParameterType::STRING)
                )
            );

        $paths = $query->executeQuery()->fetchFirstColumn();
        $nodeIds = array_unique(
            array_reduce(
                array_map(
                    static function ($pathString): array {
                        return array_filter(explode('/', $pathString));
                    },
                    $paths
                ),
                'array_merge_recursive',
                []
            )
        );

        return array_map('intval', $nodeIds);
    }

    private function buildRoleDraftQueryConstraint(
        int $status,
        QueryBuilder $query,
        string $columnAlias = 'r'
    ): string {
        if ($status === Role::STATUS_DEFINED) {
            $draftCondition = $query->expr()->eq(
                "{$columnAlias}.version",
                $query->createPositionalParameter($status, ParameterType::INTEGER)
            );
        } else {
            // version stores original Role ID when Role is a draft...
            $draftCondition = $query->expr()->neq(
                "{$columnAlias}.version",
                $query->createPositionalParameter(Role::STATUS_DEFINED, ParameterType::INTEGER)
            );
        }

        return $draftCondition;
    }

    private function markRoleAsPublished(int $roleDraftId, ?int $originalRoleId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::ROLE_TABLE)
            ->set(
                'version',
                $query->createPositionalParameter(Role::STATUS_DEFINED, ParameterType::INTEGER)
            );
        // Draft was created from an existing role, so published role must get the original ID.
        if ($originalRoleId !== null) {
            $query->set(
                'id',
                $query->createPositionalParameter($originalRoleId, ParameterType::INTEGER)
            );
        }

        $query->where(
            $query->expr()->eq(
                'id',
                $query->createPositionalParameter($roleDraftId, ParameterType::INTEGER)
            )
        );
        $query->executeStatement();
    }

    private function publishRolePolicies(int $roleDraftId, ?int $originalRoleId): void
    {
        $policyQuery = $this->connection->createQueryBuilder();
        $policyQuery
            ->update(self::POLICY_TABLE)
            ->set(
                'original_id',
                $policyQuery->createPositionalParameter(0, ParameterType::INTEGER)
            );
        // Draft was created from an existing role, so published policies must get the original role ID.
        if ($originalRoleId !== null) {
            $policyQuery->set(
                'role_id',
                $policyQuery->createPositionalParameter($originalRoleId, ParameterType::INTEGER)
            );
        }

        $policyQuery->where(
            $policyQuery->expr()->eq(
                'role_id',
                $policyQuery->createPositionalParameter($roleDraftId, ParameterType::INTEGER)
            )
        );
        $policyQuery->executeStatement();
    }
}
