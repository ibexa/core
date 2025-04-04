<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\Decorator\RoleServiceDecorator;
use Ibexa\Contracts\Core\Repository\RoleService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleAssignment;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\RoleUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RoleServiceDecoratorTest extends TestCase
{
    private const ROLE_ID = 1;
    private const ROLE_ASSIGNMENT_ID = 1;

    protected function createDecorator(MockObject $service): RoleService
    {
        return new class($service) extends RoleServiceDecorator {
        };
    }

    protected function createServiceMock(): MockObject
    {
        return $this->createMock(RoleService::class);
    }

    public function testCreateRoleDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(RoleCreateStruct::class)];

        $serviceMock->expects(self::once())->method('createRole')->with(...$parameters);

        $decoratedService->createRole(...$parameters);
    }

    public function testCreateRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Role::class)];

        $serviceMock->expects(self::once())->method('createRoleDraft')->with(...$parameters);

        $decoratedService->createRoleDraft(...$parameters);
    }

    public function testLoadRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::ROLE_ID];

        $serviceMock->expects(self::once())->method('loadRoleDraft')->with(...$parameters);

        $decoratedService->loadRoleDraft(...$parameters);
    }

    public function testLoadRoleDraftByRoleIdDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::ROLE_ID];

        $serviceMock->expects(self::once())->method('loadRoleDraftByRoleId')->with(...$parameters);

        $decoratedService->loadRoleDraftByRoleId(...$parameters);
    }

    public function testUpdateRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(RoleUpdateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('updateRoleDraft')->with(...$parameters);

        $decoratedService->updateRoleDraft(...$parameters);
    }

    public function testAddPolicyByRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyCreateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('addPolicyByRoleDraft')->with(...$parameters);

        $decoratedService->addPolicyByRoleDraft(...$parameters);
    }

    public function testRemovePolicyByRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
        ];

        $serviceMock->expects(self::once())->method('removePolicyByRoleDraft')->with(...$parameters);

        $decoratedService->removePolicyByRoleDraft(...$parameters);
    }

    public function testUpdatePolicyByRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
            $this->createMock(PolicyUpdateStruct::class),
        ];

        $serviceMock->expects(self::once())->method('updatePolicyByRoleDraft')->with(...$parameters);

        $decoratedService->updatePolicyByRoleDraft(...$parameters);
    }

    public function testDeleteRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(RoleDraft::class)];

        $serviceMock->expects(self::once())->method('deleteRoleDraft')->with(...$parameters);

        $decoratedService->deleteRoleDraft(...$parameters);
    }

    public function testPublishRoleDraftDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(RoleDraft::class)];

        $serviceMock->expects(self::once())->method('publishRoleDraft')->with(...$parameters);

        $decoratedService->publishRoleDraft(...$parameters);
    }

    public function testLoadRoleDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::ROLE_ID];

        $serviceMock->expects(self::once())->method('loadRole')->with(...$parameters);

        $decoratedService->loadRole(...$parameters);
    }

    public function testLoadRoleByIdentifierDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['random_value_5ced05ce14b742.13672543'];

        $serviceMock->expects(self::once())->method('loadRoleByIdentifier')->with(...$parameters);

        $decoratedService->loadRoleByIdentifier(...$parameters);
    }

    public function testLoadRolesDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('loadRoles')->with(...$parameters);

        $decoratedService->loadRoles(...$parameters);
    }

    public function testDeleteRoleDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Role::class)];

        $serviceMock->expects(self::once())->method('deleteRole')->with(...$parameters);

        $decoratedService->deleteRole(...$parameters);
    }

    public function testAssignRoleToUserGroupDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(UserGroup::class),
            $this->createMock(RoleLimitation::class),
        ];

        $serviceMock->expects(self::once())->method('assignRoleToUserGroup')->with(...$parameters);

        $decoratedService->assignRoleToUserGroup(...$parameters);
    }

    public function testAssignRoleToUserDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(User::class),
            $this->createMock(RoleLimitation::class),
        ];

        $serviceMock->expects(self::once())->method('assignRoleToUser')->with(...$parameters);

        $decoratedService->assignRoleToUser(...$parameters);
    }

    public function testLoadRoleAssignmentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [self::ROLE_ASSIGNMENT_ID];

        $serviceMock->expects(self::once())->method('loadRoleAssignment')->with(...$parameters);

        $decoratedService->loadRoleAssignment(...$parameters);
    }

    public function testGetRoleAssignmentsDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(Role::class)];

        $serviceMock->expects(self::once())->method('getRoleAssignments')->with(...$parameters);

        $decoratedService->getRoleAssignments(...$parameters);
    }

    public function testGetRoleAssignmentsForUserDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            $this->createMock(User::class),
            true,
        ];

        $serviceMock->expects(self::once())->method('getRoleAssignmentsForUser')->with(...$parameters);

        $decoratedService->getRoleAssignmentsForUser(...$parameters);
    }

    public function testGetRoleAssignmentsForUserGroupDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(UserGroup::class)];

        $serviceMock->expects(self::once())->method('getRoleAssignmentsForUserGroup')->with(...$parameters);

        $decoratedService->getRoleAssignmentsForUserGroup(...$parameters);
    }

    public function testRemoveRoleAssignmentDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [$this->createMock(RoleAssignment::class)];

        $serviceMock->expects(self::once())->method('removeRoleAssignment')->with(...$parameters);

        $decoratedService->removeRoleAssignment(...$parameters);
    }

    public function testNewRoleCreateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['random_value_5ced05ce14d674.30093215'];

        $serviceMock->expects(self::once())->method('newRoleCreateStruct')->with(...$parameters);

        $decoratedService->newRoleCreateStruct(...$parameters);
    }

    public function testNewPolicyCreateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            'random_value_5ced05ce14d6a8.88877327',
            'random_value_5ced05ce14d6b6.22048821',
        ];

        $serviceMock->expects(self::once())->method('newPolicyCreateStruct')->with(...$parameters);

        $decoratedService->newPolicyCreateStruct(...$parameters);
    }

    public function testNewPolicyUpdateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('newPolicyUpdateStruct')->with(...$parameters);

        $decoratedService->newPolicyUpdateStruct(...$parameters);
    }

    public function testNewRoleUpdateStructDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [];

        $serviceMock->expects(self::once())->method('newRoleUpdateStruct')->with(...$parameters);

        $decoratedService->newRoleUpdateStruct(...$parameters);
    }

    public function testGetLimitationTypeDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = ['random_value_5ced05ce14d714.69905914'];

        $serviceMock->expects(self::once())->method('getLimitationType')->with(...$parameters);

        $decoratedService->getLimitationType(...$parameters);
    }

    public function testGetLimitationTypesByModuleFunctionDecorator(): void
    {
        $serviceMock = $this->createServiceMock();
        $decoratedService = $this->createDecorator($serviceMock);

        $parameters = [
            'random_value_5ced05ce14d732.11207294',
            'random_value_5ced05ce14d743.90303575',
        ];

        $serviceMock->expects(self::once())->method('getLimitationTypesByModuleFunction')->with(...$parameters);

        $decoratedService->getLimitationTypesByModuleFunction(...$parameters);
    }
}
