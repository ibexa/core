<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use ArrayIterator;
use Ibexa\Contracts\Core\Limitation\Type as SPIType;
use Ibexa\Contracts\Core\Persistence\User as SPIUser;
use Ibexa\Contracts\Core\Persistence\User\Role as SPIRole;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft;
use Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\Role;
use Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\RoleDraft;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Repository\Mapper\RoleDomainMapper;
use Ibexa\Core\Repository\Permission\LimitationService;
use Ibexa\Core\Repository\RoleService;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;

/**
 * @covers \Ibexa\Contracts\Core\Repository\RoleService
 * @covers \Ibexa\Core\Repository\Permission\LimitationService::validateLimitations
 * @covers \Ibexa\Core\Repository\Permission\LimitationService::validateLimitation
 */
class RoleTest extends BaseServiceMockTest
{
    /**
     * Test for the createRole() method.
     */
    public function testCreateRoleThrowsLimitationValidationException()
    {
        $this->expectException(LimitationValidationException::class);

        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationMock->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('mockIdentifier'));

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([42]));

        $settings = [
            'policyMap' => ['mockModule' => ['mockFunction' => ['mockIdentifier' => true]]],
            'limitationTypes' => ['mockIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(['loadRoleByIdentifier'], $settings);

        /** @var \Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct $roleCreateStructMock */
        $roleCreateStructMock = $this->createMock(RoleCreateStruct::class);
        $policyCreateStructMock = $this->createMock(PolicyCreateStruct::class);

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct $policyCreateStructMock */
        $policyCreateStructMock->module = 'mockModule';
        $policyCreateStructMock->function = 'mockFunction';
        $roleCreateStructMock->identifier = 'mockIdentifier';
        $roleServiceMock->expects(self::once())
            ->method('loadRoleByIdentifier')
            ->with(self::equalTo('mockIdentifier'))
            ->will(self::throwException(new NotFoundException('Role', 'mockIdentifier')));

        /* @var \PHPUnit\Framework\MockObject\MockObject $roleCreateStructMock */
        $roleCreateStructMock->expects(self::once())
            ->method('getPolicies')
            ->will(self::returnValue([$policyCreateStructMock]));

        /* @var \PHPUnit\Framework\MockObject\MockObject $policyCreateStructMock */
        $policyCreateStructMock->expects(self::once())
            ->method('getLimitations')
            ->will(self::returnValue([$limitationMock]));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('create'),
                self::equalTo($roleCreateStructMock)
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\RoleCreateStruct $roleCreateStructMock */
        $roleServiceMock->createRole($roleCreateStructMock);
    }

    /**
     * Test for the addPolicy() method.
     */
    public function testAddPolicyThrowsLimitationValidationException()
    {
        $this->expectException(LimitationValidationException::class);

        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([42]));

        $limitationMock->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('mockIdentifier'));

        $settings = [
            'policyMap' => ['mockModule' => ['mockFunction' => ['mockIdentifier' => true]]],
            'limitationTypes' => ['mockIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(['loadRoleDraft'], $settings);

        $roleDraftMock = $this->createMock(RoleDraft::class);
        $policyCreateStructMock = $this->createMock(PolicyCreateStruct::class);

        $roleDraftMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct $policyCreateStructMock */
        $policyCreateStructMock->module = 'mockModule';
        $policyCreateStructMock->function = 'mockFunction';

        $roleServiceMock->expects(self::once())
            ->method('loadRoleDraft')
            ->with(self::equalTo(42))
            ->will(self::returnValue($roleDraftMock));

        /* @var \PHPUnit\Framework\MockObject\MockObject $policyCreateStructMock */
        $policyCreateStructMock->expects(self::once())
            ->method('getLimitations')
            ->will(self::returnValue([$limitationMock]));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('update'),
                self::equalTo($roleDraftMock)
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleDraftMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyCreateStruct $policyCreateStructMock */
        $roleServiceMock->addPolicyByRoleDraft($roleDraftMock, $policyCreateStructMock);
    }

    /**
     * Test for the updatePolicyByRoleDraft() method.
     */
    public function testUpdatePolicyThrowsLimitationValidationException()
    {
        $this->expectException(LimitationValidationException::class);

        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([42]));

        $limitationMock->expects(self::any())
            ->method('getIdentifier')
            ->will(self::returnValue('mockIdentifier'));

        $settings = [
            'policyMap' => ['mockModule' => ['mockFunction' => ['mockIdentifier' => true]]],
            'limitationTypes' => ['mockIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(['loadRole'], $settings);

        $roleDraftMock = $this->createMock(RoleDraft::class);
        $policyDraftMock = $this->createMock(PolicyDraft::class);
        $policyUpdateStructMock = $this->createMock(PolicyUpdateStruct::class);

        $policyDraftMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnCallback(
                    static function ($propertyName): ?string {
                        switch ($propertyName) {
                            case 'module':
                                return 'mockModule';
                            case 'function':
                                return 'mockFunction';
                        }

                        return null;
                    }
                )
            );

        /* @var \PHPUnit\Framework\MockObject\MockObject $policyCreateStructMock */
        $policyUpdateStructMock->expects(self::once())
            ->method('getLimitations')
            ->will(self::returnValue([$limitationMock]));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('update'),
                self::equalTo($roleDraftMock)
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Policy $policyDraftMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyUpdateStruct $policyUpdateStructMock */
        $roleServiceMock->updatePolicyByRoleDraft(
            $roleDraftMock,
            $policyDraftMock,
            $policyUpdateStructMock
        );
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUserThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $roleServiceMock = $this->getPartlyMockedRoleService();
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        $userMock = $this->createMock(User::class);

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(false));

        $roleServiceMock->assignRoleToUser($roleMock, $userMock, null);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUserThrowsLimitationValidationException()
    {
        $this->expectException(LimitationValidationException::class);

        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([42]));

        $limitationMock->expects(self::once())
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $settings = [
            'limitationTypes' => ['testIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(null, $settings);

        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        $userMock = $this->createMock(User::class);

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUser($roleMock, $userMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUserThrowsBadStateException()
    {
        $this->expectException(BadStateException::class);

        $roleServiceMock = $this->getPartlyMockedRoleService();
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        $userMock = $this->createMock(User::class);
        $limitationMock = $this->createMock(RoleLimitation::class);

        $limitationMock->expects(self::once())
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUser($roleMock, $userMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUser()
    {
        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([]));

        $limitationMock->expects(self::exactly(2))
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $settings = [
            'limitationTypes' => ['testIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues'], $settings);

        $repository = $this->getRepositoryMock();
        $roleMock = $this->createMock(Role::class);
        $userMock = $this->createMock(User::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $userMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userHandlerMock->expects(self::once())
            ->method('load')
            ->with(self::equalTo(24))
            ->will(self::returnValue(new SPIUser(['id' => 24])));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), ['testIdentifier' => []])
            ->will(self::returnValue(['testIdentifier' => []]));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(['testIdentifier' => []])
            );
        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUser($roleMock, $userMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUserWithNullLimitation()
    {
        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues']);
        $roleMock = $this->createMock(Role::class);
        $userMock = $this->createMock(User::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $userMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userHandlerMock->expects(self::once())
            ->method('load')
            ->with(self::equalTo(24))
            ->will(self::returnValue(new SPIUser(['id' => 24])));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), null)
            ->will(self::returnValue(null));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(null)
            );
        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        $roleServiceMock->assignRoleToUser($roleMock, $userMock, null);
    }

    /**
     * Test for the assignRoleToUser() method.
     */
    public function testAssignRoleToUserWithRollback()
    {
        $this->expectException(\Exception::class);

        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues']);
        $roleMock = $this->createMock(Role::class);
        $userMock = $this->createMock(User::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $userMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userHandlerMock->expects(self::once())
            ->method('load')
            ->with(self::equalTo(24))
            ->will(self::returnValue(new SPIUser(['id' => 24])));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), null)
            ->will(self::returnValue(null));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(null)
            )->will(self::throwException(new \Exception()));
        $repository->expects(self::once())->method('rollback');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\User $userMock */
        $roleServiceMock->assignRoleToUser($roleMock, $userMock, null);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleToUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService();
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        $userGroupMock = $this->createMock(UserGroup::class);

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(false));

        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, null);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleToUserGroupThrowsLimitationValidationException()
    {
        $this->expectException(LimitationValidationException::class);

        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([42]));

        $limitationMock->expects(self::once())
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $settings = [
            'limitationTypes' => ['testIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(null, $settings);

        $repository = $this->getRepositoryMock();
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        $userGroupMock = $this->createMock(UserGroup::class);

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleGroupToUserThrowsBadStateException()
    {
        $this->expectException(BadStateException::class);

        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService();
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        $roleMock = $this->createMock(Role::class);
        /** @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        $userGroupMock = $this->createMock(UserGroup::class);
        $limitationMock = $this->createMock(RoleLimitation::class);

        $limitationMock->expects(self::once())
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleToUserGroup()
    {
        $limitationMock = $this->createMock(RoleLimitation::class);
        $limitationTypeMock = $this->createMock(SPIType::class);

        $limitationTypeMock->expects(self::once())
            ->method('acceptValue')
            ->with(self::equalTo($limitationMock));
        $limitationTypeMock->expects(self::once())
            ->method('validate')
            ->with(self::equalTo($limitationMock))
            ->will(self::returnValue([]));

        $limitationMock->expects(self::exactly(2))
            ->method('getIdentifier')
            ->will(self::returnValue('testIdentifier'));

        $settings = [
            'limitationTypes' => ['testIdentifier' => $limitationTypeMock],
        ];

        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues'], $settings);

        $repository = $this->getRepositoryMock();
        $roleMock = $this->createMock(Role::class);
        $userGroupMock = $this->createMock(UserGroup::class);
        $userServiceMock = $this->createMock(UserService::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $repository->expects(self::once())
            ->method('getUserService')
            ->will(self::returnValue($userServiceMock));
        $userGroupMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userServiceMock->expects(self::once())
            ->method('loadUserGroup')
            ->with(self::equalTo(24))
            ->will(self::returnValue($userGroupMock));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), ['testIdentifier' => []])
            ->will(self::returnValue(['testIdentifier' => []]));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(['testIdentifier' => []])
            );
        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation $limitationMock */
        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, $limitationMock);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleToUserGroupWithNullLimitation()
    {
        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues']);
        $roleMock = $this->createMock(Role::class);
        $userGroupMock = $this->createMock(UserGroup::class);
        $userServiceMock = $this->createMock(UserService::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $repository->expects(self::once())
            ->method('getUserService')
            ->will(self::returnValue($userServiceMock));
        $userGroupMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userServiceMock->expects(self::once())
            ->method('loadUserGroup')
            ->with(self::equalTo(24))
            ->will(self::returnValue($userGroupMock));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), null)
            ->will(self::returnValue(null));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(null)
            );
        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, null);
    }

    /**
     * Test for the assignRoleToUserGroup() method.
     */
    public function testAssignRoleToUserGroupWithRollback()
    {
        $this->expectException(\Exception::class);

        $repository = $this->getRepositoryMock();
        $roleServiceMock = $this->getPartlyMockedRoleService(['checkAssignmentAndFilterLimitationValues']);
        $roleMock = $this->createMock(Role::class);
        $userGroupMock = $this->createMock(UserGroup::class);
        $userServiceMock = $this->createMock(UserService::class);
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $repository->expects(self::once())
            ->method('getUserService')
            ->will(self::returnValue($userServiceMock));
        $userGroupMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(24));

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('assign'),
                self::equalTo($userGroupMock),
                self::equalTo([$roleMock])
            )->will(self::returnValue(true));

        $roleMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->will(self::returnValue(42));

        $userHandlerMock->expects(self::once())
            ->method('loadRole')
            ->with(self::equalTo(42))
            ->will(self::returnValue(new SPIRole(['id' => 42])));

        $userServiceMock->expects(self::once())
            ->method('loadUserGroup')
            ->with(self::equalTo(24))
            ->will(self::returnValue($userGroupMock));

        $roleServiceMock->expects(self::once())
            ->method('checkAssignmentAndFilterLimitationValues')
            ->with(24, self::isInstanceOf(SPIRole::class), null)
            ->will(self::returnValue(null));

        $repository->expects(self::once())->method('beginTransaction');
        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');
        $userHandlerMock->expects(self::once())
            ->method('assignRole')
            ->with(
                self::equalTo(24),
                self::equalTo(42),
                self::equalTo(null)
            )->will(self::throwException(new \Exception()));
        $repository->expects(self::once())->method('rollback');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Role $roleMock */
        /* @var \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroupMock */
        $roleServiceMock->assignRoleToUserGroup($roleMock, $userGroupMock, null);
    }

    public function testRemovePolicyByRoleDraftThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $roleDraftMock = $this->createMock(RoleDraft::class);
        $roleDomainMapper = $this->createMock(RoleDomainMapper::class);
        $roleDomainMapper
            ->method('buildDomainRoleObject')
            ->willReturn($roleDraftMock);

        $roleServiceMock = $this->getPartlyMockedRoleService(null, [], $roleDomainMapper);
        $policyDraftMock = $this->createMock(PolicyDraft::class);

        $policyDraftMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['roleId', 17],
                    ]
                )
            );

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('update'),
                self::equalTo($roleDraftMock)
            )->will(self::returnValue(false));

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Policy $policyMock */
        $roleServiceMock->removePolicyByRoleDraft($roleDraftMock, $policyDraftMock);
    }

    /**
     * Test for the removePolicyByRoleDraft() method.
     */
    public function testRemovePolicyByRoleDraftWithRollback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Handler threw an exception');

        $repository = $this->getRepositoryMock();
        $roleDraftMock = $this->createMock(RoleDraft::class);
        $roleDraftMock->expects(self::any())
            ->method('__get')
            ->with('id')
            ->willReturn(17);

        $roleDomainMapper = $this->createMock(RoleDomainMapper::class);
        $roleDomainMapper
            ->method('buildDomainRoleObject')
            ->willReturn($roleDraftMock);
        $roleServiceMock = $this->getPartlyMockedRoleService(null, [], $roleDomainMapper);

        $policyDraftMock = $this->createMock(PolicyDraft::class);
        $policyDraftMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['id', 42],
                        ['roleId', 17],
                    ]
                )
            );

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('update'),
                self::equalTo($roleDraftMock)
            )->will(self::returnValue(true));

        $repository->expects(self::once())->method('beginTransaction');

        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $userHandlerMock->expects(self::once())
            ->method('deletePolicy')
            ->with(
                self::equalTo(42)
            )->will(self::throwException(new \Exception('Handler threw an exception')));

        $repository->expects(self::once())->method('rollback');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\Policy $policyDraftMock */
        $roleServiceMock->removePolicyByRoleDraft($roleDraftMock, $policyDraftMock);
    }

    public function testRemovePolicyByRoleDraft()
    {
        $repository = $this->getRepositoryMock();
        $roleDraftMock = $this->createMock(RoleDraft::class);
        $roleDraftMock
            ->expects(self::any())
            ->method('__get')
            ->with('id')
            ->willReturn(17);

        $roleDomainMapper = $this->createMock(RoleDomainMapper::class);
        $roleDomainMapper
            ->method('buildDomainRoleObject')
            ->willReturn($roleDraftMock);

        $roleServiceMock = $this->getPartlyMockedRoleService(['loadRoleDraft'], [], $roleDomainMapper);

        $policyDraftMock = $this->createMock(PolicyDraft::class);
        $policyDraftMock->expects(self::any())
            ->method('__get')
            ->will(
                self::returnValueMap(
                    [
                        ['id', 42],
                        ['roleId', 17],
                    ]
                )
            );

        $permissionResolverMock = $this->getPermissionResolverMock();
        $permissionResolverMock->expects(self::once())
            ->method('canUser')
            ->with(
                self::equalTo('role'),
                self::equalTo('update'),
                self::equalTo($roleDraftMock)
            )->will(self::returnValue(true));

        $repository->expects(self::once())->method('beginTransaction');

        $userHandlerMock = $this->getPersistenceMockHandler('User\\Handler');

        $userHandlerMock->expects(self::once())
            ->method('deletePolicy')
            ->with(
                self::equalTo(42)
            );

        $roleServiceMock->expects(self::once())
            ->method('loadRoleDraft')
            ->with(self::equalTo(17))
            ->will(self::returnValue($roleDraftMock));

        $repository->expects(self::once())->method('commit');

        /* @var \Ibexa\Contracts\Core\Repository\Values\User\PolicyDraft $policyDraftMock */
        $roleServiceMock->removePolicyByRoleDraft($roleDraftMock, $policyDraftMock);
    }

    /** @var \Ibexa\Core\Repository\RoleService */
    protected $partlyMockedRoleService;

    /**
     * Returns the role service to test with $methods mocked.
     *
     * Injected Repository comes from {@see getRepositoryMock()} and persistence handler from {@see getPersistenceMock()}
     *
     * @param string[] $methods
     * @param array $settings
     * @param \Ibexa\Core\Repository\Mapper\RoleDomainMapper|null $roleDomainMapper
     *
     * @return \Ibexa\Core\Repository\RoleService|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getPartlyMockedRoleService(
        array $methods = null,
        array $settings = [],
        ?RoleDomainMapper $roleDomainMapper = null
    ) {
        if (!isset($this->partlyMockedRoleService) || !empty($settings) || $roleDomainMapper) {
            $limitationService = new LimitationService(
                new ArrayIterator($settings['limitationTypes'] ?? [])
            );
            if ($roleDomainMapper === null) {
                $roleDomainMapper = $this->getMockBuilder(RoleDomainMapper::class)
                    ->setMethods([])
                    ->setConstructorArgs([$limitationService])
                    ->getMock();
            }

            $this->partlyMockedRoleService = $this->getMockBuilder(RoleService::class)
                ->setMethods($methods)
                ->setConstructorArgs(
                    [
                        $this->getRepositoryMock(),
                        $this->getPersistenceMockHandler('User\\Handler'),
                        $limitationService,
                        $roleDomainMapper,
                        $settings,
                    ]
                )
                ->getMock();
        }

        return $this->partlyMockedRoleService;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Repository|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getRepositoryMock(): Repository
    {
        $repositoryMock = parent::getRepositoryMock();
        $repositoryMock
            ->expects(self::any())
            ->method('getPermissionResolver')
            ->willReturn($this->getPermissionResolverMock());

        return $repositoryMock;
    }
}
