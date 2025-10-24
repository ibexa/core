<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Limitation\Type;
use Ibexa\Contracts\Core\Persistence\User\Handler;
use Ibexa\Contracts\Core\Persistence\User\Role as SPIRole;
use Ibexa\Contracts\Core\Persistence\User\RoleUpdateStruct as SPIRoleUpdateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException as APINotFoundException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\RoleService as RoleServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Policy;
use Ibexa\Contracts\Core\Repository\Values\User\Policy as APIPolicy;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct as APIPolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct as APIPolicyUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Role as APIRole;
use Ibexa\Contracts\Core\Repository\Values\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCopyStruct as APIRoleCopyStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct as APIRoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft as APIRoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\RoleUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupRoleAssignment;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\LimitationValidationException;
use Ibexa\Core\Base\Exceptions\NotFound\LimitationNotFoundException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Core\Repository\Values\User\Role;
use Ibexa\Core\Repository\Values\User\RoleCopyStruct;
use Ibexa\Core\Repository\Values\User\RoleCreateStruct;

/**
 * This service provides methods for managing Roles and Policies.
 *
 * @phpstan-import-type TPolicyMap from \Ibexa\Contracts\Core\Repository\RoleService
 */
class RoleService implements RoleServiceInterface
{
    /** @var Repository */
    protected $repository;

    /** @var Handler */
    protected $userHandler;

    /** @var LimitationService */
    protected $limitationService;

    /** @var RoleDomainMapper */
    protected $roleDomainMapper;

    /** @phpstan-var array{policyMap: TPolicyMap}|array{} */
    protected array $settings;

    /** @var PermissionResolver */
    private $permissionResolver;

    /**
     * Setups service with reference to repository object that created it & corresponding handler.
     *
     * @phpstan-param array{policyMap: TPolicyMap}|array{} $settings
     */
    public function __construct(
        RepositoryInterface $repository,
        Handler $userHandler,
        LimitationService $limitationService,
        RoleDomainMapper $roleDomainMapper,
        array $settings = []
    ) {
        $this->repository = $repository;
        $this->userHandler = $userHandler;
        $this->limitationService = $limitationService;
        $this->roleDomainMapper = $roleDomainMapper;
        $this->settings = $settings;
        $this->permissionResolver = $repository->getPermissionResolver();
    }

    /**
     * Creates a new RoleDraft.
     *
     * @since 6.0
     *
     * @param APIRoleCreateStruct $roleCreateStruct
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the name of the role already exists or if limitation of the same type
     *         is repeated in the policy create struct or if limitation is not allowed on module/function
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a RoleDraft
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException if a policy limitation in the $roleCreateStruct is not valid
     */
    public function createRole(APIRoleCreateStruct $roleCreateStruct): APIRoleDraft
    {
        if (!is_string($roleCreateStruct->identifier) || empty($roleCreateStruct->identifier)) {
            throw new InvalidArgumentValue('identifier', $roleCreateStruct->identifier, 'RoleCreateStruct');
        }

        if (!$this->permissionResolver->canUser('role', 'create', $roleCreateStruct)) {
            throw new UnauthorizedException('role', 'create');
        }

        try {
            $existingRole = $this->loadRoleByIdentifier($roleCreateStruct->identifier);

            throw new InvalidArgumentException(
                '$roleCreateStruct',
                "A Role '{$existingRole->id}' with identifier '{$roleCreateStruct->identifier}' " .
                'already exists'
            );
        } catch (APINotFoundException $e) {
            // Do nothing
        }

        $limitationValidationErrors = $this->validateRoleCreateStruct($roleCreateStruct);
        if (!empty($limitationValidationErrors)) {
            throw new LimitationValidationException($limitationValidationErrors);
        }

        $spiRoleCreateStruct = $this->roleDomainMapper->buildPersistenceRoleCreateStruct($roleCreateStruct);

        $this->repository->beginTransaction();
        try {
            $spiRole = $this->userHandler->createRole($spiRoleCreateStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->roleDomainMapper->buildDomainRoleDraftObject($spiRole);
    }

    /**
     * Creates a new RoleDraft for an existing Role.
     *
     * @since 6.0
     *
     * @param APIRole $role
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the Role already has a RoleDraft that will need to be removed first
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a RoleDraft
     */
    public function createRoleDraft(APIRole $role): APIRoleDraft
    {
        if (!$this->permissionResolver->canUser('role', 'create', $role)) {
            throw new UnauthorizedException('role', 'create');
        }

        try {
            $this->userHandler->loadRole($role->id, Role::STATUS_DRAFT);

            // Throw exception, so platformui et al can do conflict management. Follow-up: EZP-24719
            throw new InvalidArgumentException(
                '$role',
                "Cannot create a draft for Role '{$role->identifier}' because another draft exists"
            );
        } catch (APINotFoundException $e) {
            $this->repository->beginTransaction();
            try {
                $spiRole = $this->userHandler->createRoleDraft($role->id);
                $this->repository->commit();
            } catch (Exception $e) {
                $this->repository->rollback();
                throw $e;
            }
        }

        return $this->roleDomainMapper->buildDomainRoleDraftObject($spiRole);
    }

    public function copyRole(
        APIRole $role,
        APIRoleCopyStruct $roleCopyStruct
    ): APIRole {
        if (!is_string($roleCopyStruct->newIdentifier) || empty($roleCopyStruct->newIdentifier)) {
            throw new InvalidArgumentValue('newIdentifier', $roleCopyStruct->newIdentifier, 'RoleCopyStruct');
        }

        if (!$this->permissionResolver->canUser('role', 'create', $roleCopyStruct)) {
            throw new UnauthorizedException('role', 'create');
        }

        try {
            $existingRole = $this->loadRoleByIdentifier($roleCopyStruct->newIdentifier);

            throw new InvalidArgumentException(
                '$roleCopyStruct',
                "Role '{$existingRole->id}' with the specified identifier '{$roleCopyStruct->newIdentifier}' " .
                'already exists'
            );
        } catch (APINotFoundException $e) {
            // Do nothing
        }

        foreach ($role->getPolicies() as $policy) {
            $policyCreateStruct = new PolicyCreateStruct([
                'module' => $policy->module,
                'function' => $policy->function,
            ]);
            foreach ($policy->getLimitations() as $limitation) {
                $policyCreateStruct->addLimitation($limitation);
            }
            $roleCopyStruct->addPolicy($policyCreateStruct);
        }

        $limitationValidationErrors = $this->validateRoleCreateStruct($roleCopyStruct);
        if (!empty($limitationValidationErrors)) {
            throw new LimitationValidationException($limitationValidationErrors);
        }

        $spiRoleCopyStruct = $this->roleDomainMapper->buildPersistenceRoleCopyStruct(
            $roleCopyStruct,
            $role->id,
            $role->getStatus()
        );

        $this->repository->beginTransaction();
        try {
            $spiRole = $this->userHandler->copyRole($spiRoleCopyStruct);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadRole($spiRole->id);
    }

    /**
     * Loads a RoleDraft for the given id.
     *
     * @since 6.0
     *
     * @param int $id
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException if a RoleDraft with the given id was not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a RoleDraft
     */
    public function loadRoleDraft(int $id): APIRoleDraft
    {
        $spiRole = $this->userHandler->loadRole($id, Role::STATUS_DRAFT);

        $role = $this->roleDomainMapper->buildDomainRoleDraftObject($spiRole);

        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        return $role;
    }

    /**
     * Loads a RoleDraft by the ID of the role it was created from.
     *
     * @param int $roleId ID of the role the draft was created from.
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException if a RoleDraft with the given id was not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read this role
     */
    public function loadRoleDraftByRoleId(int $roleId): APIRoleDraft
    {
        $spiRole = $this->userHandler->loadRoleDraftByRoleId($roleId);

        $role = $this->roleDomainMapper->buildDomainRoleDraftObject($spiRole);

        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        return $role;
    }

    /**
     * Updates the properties of a RoleDraft.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     * @param RoleUpdateStruct $roleUpdateStruct
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the identifier of the RoleDraft already exists
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to update a RoleDraft
     */
    public function updateRoleDraft(
        APIRoleDraft $roleDraft,
        RoleUpdateStruct $roleUpdateStruct
    ): APIRoleDraft {
        if ($roleUpdateStruct->identifier !== null && !is_string($roleUpdateStruct->identifier)) {
            throw new InvalidArgumentValue('identifier', $roleUpdateStruct->identifier, 'RoleUpdateStruct');
        }

        $loadedRoleDraft = $this->loadRoleDraft($roleDraft->id);

        if (!$this->permissionResolver->canUser('role', 'update', $loadedRoleDraft)) {
            throw new UnauthorizedException('role', 'update');
        }

        if ($roleUpdateStruct->identifier !== null) {
            try {
                /* Throw exception if:
                 * - A published role with the same identifier exists, AND
                 * - The ID of the published role does not match the original ID of the draft
                */
                $existingSPIRole = $this->userHandler->loadRoleByIdentifier($roleUpdateStruct->identifier);
                $SPIRoleDraft = $this->userHandler->loadRole($loadedRoleDraft->id, Role::STATUS_DRAFT);
                if ($existingSPIRole->id != $SPIRoleDraft->originalId) {
                    throw new InvalidArgumentException(
                        '$roleUpdateStruct',
                        "A Role '{$existingSPIRole->id}' with identifier '{$roleUpdateStruct->identifier}' " .
                        'already exists'
                    );
                }
            } catch (APINotFoundException $e) {
                // Do nothing
            }
        }

        $this->repository->beginTransaction();
        try {
            $this->userHandler->updateRole(
                new SPIRoleUpdateStruct(
                    [
                        'id' => $loadedRoleDraft->id,
                        'identifier' => $roleUpdateStruct->identifier ?: $loadedRoleDraft->identifier,
                    ]
                )
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadRoleDraft($loadedRoleDraft->id);
    }

    /**
     * Adds a new policy to the RoleDraft.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     * @param APIPolicyCreateStruct $policyCreateStruct
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if limitation of the same type is repeated in policy create
     *                                                                        struct or if limitation is not allowed on module/function
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException if a limitation in the $policyCreateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to add a policy
     */
    public function addPolicyByRoleDraft(
        APIRoleDraft $roleDraft,
        APIPolicyCreateStruct $policyCreateStruct
    ): APIRoleDraft {
        if (!is_string($policyCreateStruct->module) || empty($policyCreateStruct->module)) {
            throw new InvalidArgumentValue('module', $policyCreateStruct->module, 'PolicyCreateStruct');
        }

        if (!is_string($policyCreateStruct->function) || empty($policyCreateStruct->function)) {
            throw new InvalidArgumentValue('function', $policyCreateStruct->function, 'PolicyCreateStruct');
        }

        if ($policyCreateStruct->module === '*' && $policyCreateStruct->function !== '*') {
            throw new InvalidArgumentValue('module', $policyCreateStruct->module, 'PolicyCreateStruct');
        }

        if (!$this->permissionResolver->canUser('role', 'update', $roleDraft)) {
            throw new UnauthorizedException('role', 'update');
        }

        $loadedRoleDraft = $this->loadRoleDraft($roleDraft->id);

        $limitations = $policyCreateStruct->getLimitations();
        $limitationValidationErrors = $this->validatePolicy(
            $policyCreateStruct->module,
            $policyCreateStruct->function,
            $limitations
        );
        if (!empty($limitationValidationErrors)) {
            throw new LimitationValidationException($limitationValidationErrors);
        }

        $spiPolicy = $this->roleDomainMapper->buildPersistencePolicyObject(
            $policyCreateStruct->module,
            $policyCreateStruct->function,
            $limitations
        );

        $this->repository->beginTransaction();
        try {
            $this->userHandler->addPolicyByRoleDraft($loadedRoleDraft->id, $spiPolicy);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadRoleDraft($loadedRoleDraft->id);
    }

    /**
     * Removes a policy from a RoleDraft.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     * @param PolicyDraft $policyDraft the policy to remove from the RoleDraft
     *
     * @return RoleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if policy does not belong to the given RoleDraft
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to remove a policy
     */
    public function removePolicyByRoleDraft(
        APIRoleDraft $roleDraft,
        PolicyDraft $policyDraft
    ): APIRoleDraft {
        if (!$this->permissionResolver->canUser('role', 'update', $roleDraft)) {
            throw new UnauthorizedException('role', 'update');
        }

        if ($policyDraft->roleId != $roleDraft->id) {
            throw new InvalidArgumentException('$policy', 'The Policy does not belong to the given Role');
        }

        $this->internalDeletePolicy($policyDraft);

        return $this->loadRoleDraft($roleDraft->id);
    }

    /**
     * Updates the limitations of a policy. The module and function cannot be changed and
     * the limitations are replaced by the ones in $roleUpdateStruct.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     * @param PolicyDraft $policy
     * @param APIPolicyUpdateStruct $policyUpdateStruct
     *
     * @return PolicyDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if limitation of the same type is repeated in policy update
     *                                                                        struct or if limitation is not allowed on module/function
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException if a limitation in the $policyUpdateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to update a policy
     */
    public function updatePolicyByRoleDraft(
        APIRoleDraft $roleDraft,
        PolicyDraft $policy,
        APIPolicyUpdateStruct $policyUpdateStruct
    ): PolicyDraft {
        if (!is_string($policy->module)) {
            throw new InvalidArgumentValue('module', $policy->module, 'Policy');
        }

        if (!is_string($policy->function)) {
            throw new InvalidArgumentValue('function', $policy->function, 'Policy');
        }

        if (!$this->permissionResolver->canUser('role', 'update', $roleDraft)) {
            throw new UnauthorizedException('role', 'update');
        }

        if ($policy->roleId !== $roleDraft->id) {
            throw new InvalidArgumentException('$policy', 'does not belong to the provided role draft');
        }

        $limitations = $policyUpdateStruct->getLimitations();
        $limitationValidationErrors = $this->validatePolicy(
            $policy->module,
            $policy->function,
            $limitations
        );
        if (!empty($limitationValidationErrors)) {
            throw new LimitationValidationException($limitationValidationErrors);
        }

        $spiPolicy = $this->roleDomainMapper->buildPersistencePolicyObject(
            $policy->module,
            $policy->function,
            $limitations
        );
        $spiPolicy->id = $policy->id;
        $spiPolicy->roleId = $policy->roleId;
        $spiPolicy->originalId = $policy->originalId;

        $this->repository->beginTransaction();
        try {
            $this->userHandler->updatePolicy($spiPolicy);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->roleDomainMapper->buildDomainPolicyObject($spiPolicy);
    }

    /**
     * Deletes the given RoleDraft.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to delete this RoleDraft
     */
    public function deleteRoleDraft(APIRoleDraft $roleDraft): void
    {
        $loadedRoleDraft = $this->loadRoleDraft($roleDraft->id);

        $this->repository->beginTransaction();
        try {
            $this->userHandler->deleteRole($loadedRoleDraft->id, Role::STATUS_DRAFT);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Publishes a given RoleDraft.
     *
     * @since 6.0
     *
     * @param RoleDraft $roleDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException if the role draft cannot be loaded
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to publish this RoleDraft
     */
    public function publishRoleDraft(APIRoleDraft $roleDraft): void
    {
        if (!$this->permissionResolver->canUser('role', 'update', $roleDraft)) {
            throw new UnauthorizedException('role', 'update');
        }

        try {
            $loadedRoleDraft = $this->loadRoleDraft($roleDraft->id);
        } catch (APINotFoundException $e) {
            throw new BadStateException(
                '$roleDraft',
                'The Role does not have a draft.',
                $e
            );
        }

        $this->repository->beginTransaction();
        try {
            $this->userHandler->publishRoleDraft($loadedRoleDraft->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Loads a role for the given id.
     *
     * @param int $id
     *
     * @return Role
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException if a role with the given id was not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read this role
     */
    public function loadRole(int $id): APIRole
    {
        $spiRole = $this->userHandler->loadRole($id);

        $role = $this->roleDomainMapper->buildDomainRoleObject($spiRole);

        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        return $role;
    }

    /**
     * Loads a role for the given identifier.
     *
     * @param string $identifier
     *
     * @return APIRole
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException if a role with the given name was not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read this role
     */
    public function loadRoleByIdentifier(string $identifier): APIRole
    {
        $spiRole = $this->userHandler->loadRoleByIdentifier($identifier);

        $role = $this->roleDomainMapper->buildDomainRoleObject($spiRole);

        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        return $role;
    }

    /**
     * Loads all roles, excluding the ones the current user is not allowed to read.
     *
     * @return APIRole[]
     */
    public function loadRoles(): iterable
    {
        $roles = array_map(
            function ($spiRole) {
                return $this->roleDomainMapper->buildDomainRoleObject($spiRole);
            },
            $this->userHandler->loadRoles()
        );

        return array_values(
            array_filter(
                $roles,
                function ($role) {
                    return $this->permissionResolver->canUser('role', 'read', $role);
                }
            )
        );
    }

    /**
     * Deletes the given role.
     *
     * @param APIRole $role
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to delete this role
     */
    public function deleteRole(APIRole $role): void
    {
        if (!$this->permissionResolver->canUser('role', 'delete', $role)) {
            throw new UnauthorizedException('role', 'delete');
        }

        $loadedRole = $this->loadRole($role->id);

        $this->repository->beginTransaction();
        try {
            $this->userHandler->deleteRole($loadedRole->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Assigns a role to the given user group.
     *
     * @param APIRole $role
     * @param UserGroup $userGroup
     * @param RoleLimitation|null $roleLimitation an optional role limitation (which is either a subtree limitation or section limitation)
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If assignment already exists
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to assign a role
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException if $roleLimitation is not valid
     */
    public function assignRoleToUserGroup(
        APIRole $role,
        UserGroup $userGroup,
        ?RoleLimitation $roleLimitation = null
    ): void {
        if ($this->permissionResolver->canUser('role', 'assign', $userGroup, [$role]) !== true) {
            throw new UnauthorizedException('role', 'assign');
        }

        if ($roleLimitation === null) {
            $limitation = null;
        } else {
            $limitationValidationErrors = $this->limitationService->validateLimitation($roleLimitation);
            if (!empty($limitationValidationErrors)) {
                throw new LimitationValidationException($limitationValidationErrors);
            }

            $limitation = [$roleLimitation->getIdentifier() => $roleLimitation->limitationValues];
        }

        // Check if objects exists
        $spiRole = $this->userHandler->loadRole($role->id);
        $loadedUserGroup = $this->repository->getUserService()->loadUserGroup($userGroup->id);

        $limitation = $this->checkAssignmentAndFilterLimitationValues($loadedUserGroup->id, $spiRole, $limitation);

        $this->repository->beginTransaction();
        try {
            $this->userHandler->assignRole(
                $loadedUserGroup->id,
                $spiRole->id,
                $limitation
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Assigns a role to the given user.
     *
     * @param APIRole $role
     * @param User $user
     * @param RoleLimitation|null $roleLimitation an optional role limitation (which is either a subtree limitation or section limitation)
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If assignment already exists
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException if $roleLimitation is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to assign a role
     */
    public function assignRoleToUser(
        APIRole $role,
        User $user,
        ?RoleLimitation $roleLimitation = null
    ): void {
        if ($this->permissionResolver->canUser('role', 'assign', $user, [$role]) !== true) {
            throw new UnauthorizedException('role', 'assign');
        }

        if ($roleLimitation === null) {
            $limitation = null;
        } else {
            $limitationValidationErrors = $this->limitationService->validateLimitation($roleLimitation);
            if (!empty($limitationValidationErrors)) {
                throw new LimitationValidationException($limitationValidationErrors);
            }

            $limitation = [$roleLimitation->getIdentifier() => $roleLimitation->limitationValues];
        }

        // Check if objects exists
        $spiRole = $this->userHandler->loadRole($role->id);
        $spiUser = $this->userHandler->load($user->id);

        $limitation = $this->checkAssignmentAndFilterLimitationValues($spiUser->id, $spiRole, $limitation);

        $this->repository->beginTransaction();
        try {
            $this->userHandler->assignRole(
                $spiUser->id,
                $spiRole->id,
                $limitation
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Removes the given role assignment.
     *
     * @param RoleAssignment $roleAssignment
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to remove a role assignment
     */
    public function removeRoleAssignment(RoleAssignment $roleAssignment): void
    {
        if ($this->permissionResolver->canUser('role', 'assign', $roleAssignment) !== true) {
            throw new UnauthorizedException('role', 'assign');
        }

        $spiRoleAssignment = $this->userHandler->loadRoleAssignment($roleAssignment->id);

        $this->repository->beginTransaction();
        try {
            $this->userHandler->removeRoleAssignment($spiRoleAssignment->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Loads a role assignment for the given id.
     *
     * @param int $roleAssignmentId
     *
     * @return RoleAssignment
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException If the role assignment was not found
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read this role
     */
    public function loadRoleAssignment(int $roleAssignmentId): RoleAssignment
    {
        $spiRoleAssignment = $this->userHandler->loadRoleAssignment($roleAssignmentId);
        $userService = $this->repository->getUserService();
        $role = $this->loadRole($spiRoleAssignment->roleId);

        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        $roleAssignment = null;

        // First check if the Role is assigned to a User
        // If no User is found, see if it belongs to a UserGroup
        try {
            $user = $userService->loadUser($spiRoleAssignment->contentId);
            $roleAssignment = $this->roleDomainMapper->buildDomainUserRoleAssignmentObject(
                $spiRoleAssignment,
                $user,
                $role
            );
        } catch (APINotFoundException $e) {
            try {
                $userGroup = $userService->loadUserGroup($spiRoleAssignment->contentId);
                $roleAssignment = $this->roleDomainMapper->buildDomainUserGroupRoleAssignmentObject(
                    $spiRoleAssignment,
                    $userGroup,
                    $role
                );
            } catch (APINotFoundException $e) {
                // Do nothing
            }
        }

        return $roleAssignment;
    }

    /**
     * @return RoleAssignment[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read a role
     */
    public function getRoleAssignments(APIRole $role): iterable
    {
        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        $persistenceRoleAssignments = $this->userHandler->loadRoleAssignmentsByRoleId($role->id);

        return $this->buildRoleAssignmentsFromPersistence($role, $persistenceRoleAssignments);
    }

    public function loadRoleAssignments(
        APIRole $role,
        int $offset = 0,
        ?int $limit = null
    ): iterable {
        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        $persistenceRoleAssignments = $this->userHandler->loadRoleAssignmentsByRoleIdWithOffsetAndLimit(
            $role->id,
            $offset,
            $limit
        );

        return $this->buildRoleAssignmentsFromPersistence($role, $persistenceRoleAssignments);
    }

    /**
     * @param array<int, \Ibexa\Contracts\Core\Persistence\User\RoleAssignment> $persistenceRoleAssignments
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function buildRoleAssignmentsFromPersistence(
        APIRole $role,
        array $persistenceRoleAssignments
    ): array {
        $userService = $this->repository->getUserService();

        $roleAssignments = [];
        foreach ($persistenceRoleAssignments as $persistenceRoleAssignment) {
            // First check if the Role is assigned to a User
            // If no User is found, see if it belongs to a UserGroup
            try {
                $user = $userService->loadUser($persistenceRoleAssignment->contentId);
                $roleAssignments[] = $this->roleDomainMapper->buildDomainUserRoleAssignmentObject(
                    $persistenceRoleAssignment,
                    $user,
                    $role
                );
            } catch (APINotFoundException $e) {
                try {
                    $userGroup = $userService->loadUserGroup($persistenceRoleAssignment->contentId);
                    $roleAssignments[] = $this->roleDomainMapper->buildDomainUserGroupRoleAssignmentObject(
                        $persistenceRoleAssignment,
                        $userGroup,
                        $role
                    );
                } catch (APINotFoundException $e) {
                    // Do nothing
                }
            }
        }

        return $roleAssignments;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read a role
     */
    public function countRoleAssignments(APIRole $role): int
    {
        if (!$this->permissionResolver->canUser('role', 'read', $role)) {
            throw new UnauthorizedException('role', 'read');
        }

        // Skipping building domain user role assignment object as done in `buildRoleAssignmentsFromPersistence`
        // due to inner joining user content which is sufficient in this case
        return $this->userHandler->countRoleAssignments($role->id);
    }

    /**
     * @see \Ibexa\Contracts\Core\Repository\RoleService::getRoleAssignmentsForUser()
     *
     * @param User $user
     * @param bool $inherited
     *
     * @return iterable
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function getRoleAssignmentsForUser(
        User $user,
        bool $inherited = false
    ): iterable {
        $roleAssignments = [];
        $spiRoleAssignments = $this->userHandler->loadRoleAssignmentsByGroupId($user->id, $inherited);
        foreach ($spiRoleAssignments as $spiRoleAssignment) {
            $role = $this->loadRole($spiRoleAssignment->roleId);

            if (!$this->permissionResolver->canUser('role', 'read', $role)) {
                continue;
            }

            if (!$inherited || $spiRoleAssignment->contentId == $user->id) {
                $roleAssignments[] = $this->roleDomainMapper->buildDomainUserRoleAssignmentObject(
                    $spiRoleAssignment,
                    $user,
                    $role
                );
            } else {
                $userGroup = $this->repository->getUserService()->loadUserGroup($spiRoleAssignment->contentId);
                $roleAssignments[] = $this->roleDomainMapper->buildDomainUserGroupRoleAssignmentObject(
                    $spiRoleAssignment,
                    $userGroup,
                    $role
                );
            }
        }

        return $roleAssignments;
    }

    /**
     * Returns the roles assigned to the given user group, excluding the ones the current user is not allowed to read.
     *
     * @param UserGroup $userGroup
     *
     * @return UserGroupRoleAssignment[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function getRoleAssignmentsForUserGroup(UserGroup $userGroup): iterable
    {
        $roleAssignments = [];
        $spiRoleAssignments = $this->userHandler->loadRoleAssignmentsByGroupId($userGroup->id);
        foreach ($spiRoleAssignments as $spiRoleAssignment) {
            $role = $this->loadRole($spiRoleAssignment->roleId);

            if ($this->permissionResolver->canUser('role', 'read', $role)) {
                $roleAssignments[] = $this->roleDomainMapper->buildDomainUserGroupRoleAssignmentObject(
                    $spiRoleAssignment,
                    $userGroup,
                    $role
                );
            }
        }

        return $roleAssignments;
    }

    /**
     * Instantiates a role create class.
     *
     * @param string $name
     *
     * @return APIRoleCreateStruct
     */
    public function newRoleCreateStruct(string $name): APIRoleCreateStruct
    {
        return new RoleCreateStruct(
            [
                'identifier' => $name,
                'policies' => [],
            ]
        );
    }

    public function newRoleCopyStruct(string $name): APIRoleCopyStruct
    {
        return new RoleCopyStruct(
            [
                'newIdentifier' => $name,
                'policies' => [],
            ]
        );
    }

    /**
     * Instantiates a policy create class.
     *
     * @param string $module
     * @param string $function
     *
     * @return APIPolicyCreateStruct
     */
    public function newPolicyCreateStruct(
        string $module,
        string $function
    ): APIPolicyCreateStruct {
        return new PolicyCreateStruct(
            [
                'module' => $module,
                'function' => $function,
                'limitations' => [],
            ]
        );
    }

    /**
     * Instantiates a policy update class.
     *
     * @return APIPolicyUpdateStruct
     */
    public function newPolicyUpdateStruct(): APIPolicyUpdateStruct
    {
        return new PolicyUpdateStruct(
            [
                'limitations' => [],
            ]
        );
    }

    /**
     * Instantiates a policy update class.
     *
     * @return RoleUpdateStruct
     */
    public function newRoleUpdateStruct(): RoleUpdateStruct
    {
        return new RoleUpdateStruct();
    }

    /**
     * Returns the LimitationType registered with the given identifier.
     *
     * Returns the correct implementation of API Limitation value object
     * based on provided identifier
     *
     * @param string $identifier
     *
     * @return Type
     *
     * @throws \RuntimeException if there is no LimitationType with $identifier
     */
    public function getLimitationType(string $identifier): Type
    {
        return $this->limitationService->getLimitationType($identifier);
    }

    /**
     * Returns the LimitationType's assigned to a given module/function.
     *
     * Typically used for:
     *  - Internal validation limitation value use on Policies
     *  - Role admin gui for editing policy limitations incl list limitation options via valueSchema()
     *
     * @param string $module Legacy name of "controller", it's a unique identifier like "content"
     * @param string $function Legacy name of a controller "action", it's a unique within the controller like "read"
     *
     * @return Type[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If module/function to limitation type mapping
     *                                                                 refers to a non existing identifier.
     */
    public function getLimitationTypesByModuleFunction(
        string $module,
        string $function
    ): iterable {
        if (empty($this->settings['policyMap'][$module][$function])) {
            return [];
        }

        $types = [];
        try {
            foreach (array_keys($this->settings['policyMap'][$module][$function]) as $identifier) {
                $types[$identifier] = $this->limitationService->getLimitationType($identifier);
            }
        } catch (LimitationNotFoundException $e) {
            throw new BadStateException(
                "{$module}/{$function}",
                "policyMap configuration is referring to a non-existent identifier: {$identifier}",
                $e
            );
        }

        return $types;
    }

    /**
     * Validates Policies and Limitations in Role create struct.
     *
     * @uses ::validatePolicy()
     *
     * @param APIRoleCreateStruct $roleCreateStruct
     *
     * @return ValidationError[][][]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function validateRoleCreateStruct(APIRoleCreateStruct $roleCreateStruct): iterable
    {
        $allErrors = [];
        foreach ($roleCreateStruct->getPolicies() as $key => $policyCreateStruct) {
            $errors = $this->validatePolicy(
                $policyCreateStruct->module,
                $policyCreateStruct->function,
                $policyCreateStruct->getLimitations()
            );

            if (!empty($errors)) {
                $allErrors[$key] = $errors;
            }
        }

        return $allErrors;
    }

    /**
     * Validates Policy context: Limitations on a module and function.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If the same limitation is repeated or if
     *                                                                   limitation is not allowed on module/function
     *
     * @param string $module
     * @param string $function
     * @param Limitation[] $limitations
     *
     * @return ValidationError[][]
     */
    protected function validatePolicy(
        string $module,
        string $function,
        array $limitations
    ): iterable {
        if ($module !== '*' && $function !== '*' && !empty($limitations)) {
            $limitationSet = [];
            foreach ($limitations as $limitation) {
                if (isset($limitationSet[$limitation->getIdentifier()])) {
                    throw new InvalidArgumentException(
                        'limitations',
                        "{$limitation->getIdentifier()}' was found multiple times among Limitations"
                    );
                }

                if (!isset($this->settings['policyMap'][$module][$function][$limitation->getIdentifier()])) {
                    throw new InvalidArgumentException(
                        'policy',
                        "Limitation '{$limitation->getIdentifier()}' is not applicable on '{$module}/{$function}'"
                    );
                }

                $limitationSet[$limitation->getIdentifier()] = true;
            }
        }

        return $this->limitationService->validateLimitations($limitations);
    }

    /**
     * Validate that assignments not already exists and filter validations against existing.
     *
     * @param int $contentId
     * @param SPIRole $spiRole
     * @param array|null $limitation
     *
     * @return array[]|null Filtered version of $limitation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If assignment already exists
     */
    protected function checkAssignmentAndFilterLimitationValues(
        int $contentId,
        SPIRole $spiRole,
        ?array $limitation = null
    ): ?array {
        $spiRoleAssignments = $this->userHandler->loadRoleAssignmentsByGroupId($contentId);
        foreach ($spiRoleAssignments as $spiAssignment) {
            // Ignore assignments to other roles
            if ($spiAssignment->roleId !== $spiRole->id) {
                continue;
            }

            // Throw if Role is already assigned without limitations
            if ($spiAssignment->limitationIdentifier === null) {
                throw new InvalidArgumentException(
                    '$role',
                    "Role '{$spiRole->id}' already assigned without Limitations"
                );
            }

            // Ignore if we are going to assign without limitations
            if ($limitation === null) {
                continue;
            }

            // Ignore if not assigned with same limitation identifier
            if (!isset($limitation[$spiAssignment->limitationIdentifier])) {
                continue;
            }

            // Throw if Role is already assigned with all the same limitations
            $newValues = array_diff($limitation[$spiAssignment->limitationIdentifier], $spiAssignment->values);
            if (empty($newValues)) {
                throw new InvalidArgumentException(
                    '$role',
                    "Role '{$spiRole->id}' is already assigned with the same '{$spiAssignment->limitationIdentifier}' value"
                );
            }

            // Continue using the filtered list of limitations
            $limitation[$spiAssignment->limitationIdentifier] = $newValues;
        }

        return $limitation;
    }

    /**
     * Deletes a policy.
     *
     * Used by {@link removePolicy()} and {@link deletePolicy()}
     *
     * @param Policy $policy
     *
     * @throws Exception
     */
    protected function internalDeletePolicy(APIPolicy $policy): void
    {
        $this->repository->beginTransaction();
        try {
            $this->userHandler->deletePolicy($policy->id, $policy->roleId);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }
}
