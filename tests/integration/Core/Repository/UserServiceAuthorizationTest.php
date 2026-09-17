<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Core\Repository\UserService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the UserService using in memory storage.
 */
#[CoversClass(UserService::class)]
#[CoversMethod(UserService::class, 'loadUserGroup')]
#[CoversMethod(UserService::class, 'loadUserGroupByRemoteId')]
#[CoversMethod(UserService::class, 'loadSubUserGroups')]
#[CoversMethod(UserService::class, 'createUserGroup')]
#[CoversMethod(UserService::class, 'deleteUserGroup')]
#[CoversMethod(UserService::class, 'moveUserGroup')]
#[CoversMethod(UserService::class, 'updateUserGroup')]
#[CoversMethod(UserService::class, 'createUser')]
#[CoversMethod(UserService::class, 'deleteUser')]
#[CoversMethod(UserService::class, 'updateUser')]
#[CoversMethod(UserService::class, 'updateUserPassword')]
#[CoversMethod(UserService::class, 'assignUserToUserGroup')]
#[CoversMethod(UserService::class, 'unAssignUserFromUserGroup')]
#[CoversMethod(UserService::class, 'loadUserGroupsOfUser')]
#[CoversMethod(UserService::class, 'loadUsersOfUserGroup')]
#[Group('integration')]
#[Group('authorization')]
class UserServiceAuthorizationTest extends BaseTestCase
{
    /**
     * Test for the loadUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testLoadUserGroup')]
    public function testLoadUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->loadUserGroup($userGroup->id);
        /* END: Use Case */
    }

    /**
     * Test for the loadUserGroupByRemoteId() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testLoadUserGroupByRemoteId')]
    public function testLoadUserGroupByRemoteIdThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->loadUserGroupByRemoteId($userGroup->contentInfo->remoteId);
    }

    /**
     * Test for the loadSubUserGroups() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testLoadSubUserGroups')]
    public function testLoadSubUserGroupsThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->loadSubUserGroups($userGroup);
        /* END: Use Case */
    }

    /**
     * Test for the createUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testCreateUserGroup')]
    public function testCreateUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $editorsGroupId = $this->generateId('group', 13);

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the parent group
        $parentUserGroup = $userService->loadUserGroup($editorsGroupId);

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // Instantiate a new group create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreate->setField('name', 'Example Group');

        // This call will fail with an "UnauthorizedException"
        $userService->createUserGroup($userGroupCreate, $parentUserGroup);
        /* END: Use Case */
    }

    /**
     * Test for the deleteUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testDeleteUserGroup')]
    public function testDeleteUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->deleteUserGroup($userGroup);
        /* END: Use Case */
    }

    /**
     * Test for the moveUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testMoveUserGroup')]
    public function testMoveUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $memberGroupId = $this->generateId('group', 11);
        /* BEGIN: Use Case */
        // $memberGroupId is the ID of the "Members" group in an Ibexa
        // demo installation
        //
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Load new parent user group
        $newParentUserGroup = $userService->loadUserGroup($memberGroupId);

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->moveUserGroup($userGroup, $newParentUserGroup);
        /* END: Use Case */
    }

    /**
     * Test for the updateUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testUpdateUserGroup')]
    public function testUpdateUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // Load content service
        $contentService = $repository->getContentService();

        // Instantiate a content update struct
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'New group name');

        $userGroupUpdateStruct = $userService->newUserGroupUpdateStruct();
        $userGroupUpdateStruct->contentUpdateStruct = $contentUpdateStruct;

        // This call will fail with an "UnauthorizedException"
        $userService->updateUserGroup($userGroup, $userGroupUpdateStruct);
        /* END: Use Case */
    }

    /**
     * Test for the createUser() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testCreateUser')]
    public function testCreateUserThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $editorsGroupId = $this->generateId('group', 13);

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // Instantiate a user create struct
        $userCreateStruct = $userService->newUserCreateStruct(
            'test',
            'test@example.com',
            'password',
            'eng-GB'
        );

        $userCreateStruct->setField('first_name', 'Christian');
        $userCreateStruct->setField('last_name', 'Bacher');

        $parentUserGroup = $userService->loadUserGroup($editorsGroupId);

        // This call will fail with an "UnauthorizedException"
        $userService->createUser(
            $userCreateStruct,
            [$parentUserGroup]
        );
        /* END: Use Case */
    }

    /**
     * Test for the deleteUser() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testDeleteUser')]
    public function testDeleteUserThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->deleteUser($user);
        /* END: Use Case */
    }

    /**
     * Test for the updateUser() method.
     */
    public function testUpdateUserThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // Instantiate a user update struct
        $userUpdateStruct = $userService->newUserUpdateStruct();
        $userUpdateStruct->maxLogin = 42;

        // This call will fail with an "UnauthorizedException"
        $userService->updateUser($user, $userUpdateStruct);
        /* END: Use Case */
    }

    public function testUpdateUserPasswordThrowsUnauthorizedException(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $this->createRoleWithPolicies('CannotChangePassword', []);

        $user = $this->createCustomUserWithLogin(
            'without_role_password',
            'without_role_password@example.com',
            'Anons',
            'CannotChangePassword'
        );

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        $this->expectException(UnauthorizedException::class);
        $userService->updateUserPassword($user, 'new password');
    }

    /**
     * Test for the assignUserToUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testAssignUserToUserGroup')]
    public function testAssignUserToUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $administratorGroupId = $this->generateId('group', 12);
        /* BEGIN: Use Case */
        // $administratorGroupId is the ID of the "Administrator" group in an
        // Ibexa demo installation

        $user = $this->createUserVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($administratorGroupId)
        );
        /* END: Use Case */
    }

    /**
     * Test for the unAssignUssrFromUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testUnAssignUserFromUserGroup')]
    public function testUnAssignUserFromUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $editorsGroupId = $this->generateId('group', 13);
        $memberGroupId = $this->generateId('group', 11);

        /* BEGIN: Use Case */
        // $memberGroupId is the ID of the "Members" group in an Ibexa
        // demo installation

        $user = $this->createUserVersion1();

        // Assign group to newly created user
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($memberGroupId)
        );

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->unAssignUserFromUserGroup(
            $user,
            $userService->loadUserGroup($editorsGroupId)
        );
        /* END: Use Case */
    }

    /**
     * Test for the loadUserGroupsOfUser() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testLoadUserGroupsOfUser')]
    public function testLoadUserGroupsOfUserThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->loadUserGroupsOfUser($user);
        /* END: Use Case */
    }

    /**
     * Test for the loadUsersOfUserGroup() method.
     */
    #[DependsExternal(UserServiceTest::class, 'testLoadUsersOfUserGroup')]
    public function testLoadUsersOfUserGroupThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        $userGroup = $this->createUserGroupVersion1();

        // Now set the currently created "Editor" as current user
        $permissionResolver->setCurrentUserReference($user);

        // This call will fail with an "UnauthorizedException"
        $userService->loadUsersOfUserGroup($userGroup);
        /* END: Use Case */
    }

    /**
     * Create a user group fixture in a variable named <b>$userGroup</b>,.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    private function createUserGroupVersion1()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Inline */
        // $mainGroupId is the ID of the main "Users" group in an Ibexa
        // demo installation

        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreate->setField('name', 'Example Group');

        // Create the new user group
        $userGroup = $userService->createUserGroup(
            $userGroupCreate,
            $parentUserGroup
        );
        /* END: Inline */

        return $userGroup;
    }
}
