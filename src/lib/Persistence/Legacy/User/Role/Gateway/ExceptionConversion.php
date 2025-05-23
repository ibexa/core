<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\User\Role\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\User\Policy;
use Ibexa\Contracts\Core\Persistence\User\Role;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\User\Role\Gateway;
use PDOException;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\User\Role\Gateway
     */
    private $innerGateway;

    /**
     * Creates a new exception conversion gateway around $innerGateway.
     *
     * @param \Ibexa\Core\Persistence\Legacy\User\Role\Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function createRole(Role $role): Role
    {
        try {
            return $this->innerGateway->createRole($role);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function copyRole(Role $role): Role
    {
        try {
            return $this->innerGateway->copyRole($role);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRole(int $roleId, int $status = Role::STATUS_DEFINED): array
    {
        try {
            return $this->innerGateway->loadRole($roleId, $status);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleByIdentifier(
        string $identifier,
        int $status = Role::STATUS_DEFINED
    ): array {
        try {
            return $this->innerGateway->loadRoleByIdentifier($identifier, $status);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleDraftByRoleId(int $roleId): array
    {
        try {
            return $this->innerGateway->loadRoleDraftByRoleId($roleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoles(int $status = Role::STATUS_DEFINED): array
    {
        try {
            return $this->innerGateway->loadRoles();
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRolesForContentObjects(
        array $contentIds,
        int $status = Role::STATUS_DEFINED
    ): array {
        try {
            return $this->innerGateway->loadRolesForContentObjects($contentIds);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleAssignment(int $roleAssignmentId): array
    {
        try {
            return $this->innerGateway->loadRoleAssignment($roleAssignmentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleAssignmentsByGroupId(int $groupId, bool $inherited = false): array
    {
        try {
            return $this->innerGateway->loadRoleAssignmentsByGroupId($groupId, $inherited);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleAssignmentsByRoleId(int $roleId): array
    {
        try {
            return $this->innerGateway->loadRoleAssignmentsByRoleId($roleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadRoleAssignmentsByRoleIdWithOffsetAndLimit(int $roleId, int $offset, ?int $limit): array
    {
        try {
            return $this->innerGateway->loadRoleAssignmentsByRoleIdWithOffsetAndLimit($roleId, $offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countRoleAssignments(int $roleId): int
    {
        try {
            return $this->innerGateway->countRoleAssignments($roleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadPoliciesByUserId(int $userId): array
    {
        try {
            return $this->innerGateway->loadPoliciesByUserId($userId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateRole(RoleUpdateStruct $role): void
    {
        try {
            $this->innerGateway->updateRole($role);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteRole(int $roleId, int $status = Role::STATUS_DEFINED): void
    {
        try {
            $this->innerGateway->deleteRole($roleId, $status);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function publishRoleDraft(int $roleDraftId, ?int $originalRoleId = null): void
    {
        try {
            $this->innerGateway->publishRoleDraft($roleDraftId, $originalRoleId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function addPolicy(int $roleId, Policy $policy): Policy
    {
        try {
            return $this->innerGateway->addPolicy($roleId, $policy);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function addPolicyLimitations(int $policyId, array $limitations): void
    {
        try {
            $this->innerGateway->addPolicyLimitations($policyId, $limitations);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function removePolicy(int $policyId): void
    {
        try {
            $this->innerGateway->removePolicy($policyId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function removePolicyLimitations(int $policyId): void
    {
        try {
            $this->innerGateway->removePolicyLimitations($policyId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
