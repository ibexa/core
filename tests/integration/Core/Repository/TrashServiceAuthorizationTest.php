<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LanguageLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ObjectStateLimitation;
use Ibexa\Core\Repository\TrashService as CoveredTrashService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the TrashService using in memory storage.
 */
#[CoversClass(CoveredTrashService::class)]
#[CoversMethod(CoveredTrashService::class, 'loadTrashItem')]
#[CoversMethod(CoveredTrashService::class, 'trash')]
#[CoversMethod(CoveredTrashService::class, 'recover')]
#[CoversMethod(CoveredTrashService::class, 'emptyTrash')]
#[CoversMethod(CoveredTrashService::class, 'deleteTrashItem')]
#[Group('integration')]
#[Group('authorization')]
class TrashServiceAuthorizationTest extends BaseTrashServiceTestCase
{
    /**
     * Test for the loadTrashItem() method.
     */
    #[DependsExternal(TrashServiceTest::class, 'testLoadTrashItem')]
    #[DependsExternal(UserServiceTest::class, 'testLoadUser')]
    public function testLoadTrashItemThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user
        $trashItem = $this->createTrashItem();

        // Load user service
        $userService = $repository->getUserService();

        // Set "Anonymous" as current user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an "UnauthorizedException"
        $trashService->loadTrashItem($trashItem->id);
        /* END: Use Case */
    }

    /**
     * Test for the trash() method without proper permissions.
     */
    public function testTrashThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('The User does not have the \'remove\' \'content\' permission');

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();

        // Load "Media" page location to be trashed
        $mediaLocation = $locationService->loadLocationByRemoteId(
            '75c715a51699d2d309a924eca6a95145'
        );

        // switch user context before testing TrashService::trash method
        $repository->getPermissionResolver()->setCurrentUserReference(
            $this->createUserWithPolicies('trash_test_user', [])
        );
        $trashService->trash($mediaLocation);
    }

    /**
     * Test for the trash() method without proper permissions.
     */
    public function testTrashThrowsUnauthorizedExceptionWithLanguageLimitation(): void
    {
        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();

        // Load "Media" page location to be trashed
        $mediaLocation = $locationService->loadLocationByRemoteId(
            '75c715a51699d2d309a924eca6a95145'
        );

        $limitations = [
            new LanguageLimitation(['limitationValues' => ['ger-DE']]),
        ];

        $user = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'remove', 'limitations' => $limitations],
            ]
        );

        $repository->getPermissionResolver()->setCurrentUserReference($user);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('The User does not have the \'remove\' \'content\' permission');

        $trashService->trash($mediaLocation);
    }

    /**
     * Test for the trash() method with proper minimal permission set.
     */
    #[Depends('testTrashThrowsUnauthorizedException')]
    public function testTrashRequiresContentRemovePolicy()
    {
        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();

        // Load "Media" page location to be trashed
        $mediaLocation = $locationService->loadLocationByRemoteId(
            '75c715a51699d2d309a924eca6a95145'
        );

        $repository->getPermissionResolver()->setCurrentUserReference(
            $this->createUserWithPolicies(
                'trash_test_user',
                [
                    ['module' => 'content', 'function' => 'remove'],
                ]
            )
        );
        $trashService->trash($mediaLocation);
    }

    /**
     * Test for the recover() method.
     */
    #[DependsExternal(TrashServiceTest::class, 'testRecover')]
    #[DependsExternal(UserServiceTest::class, 'testLoadUser')]
    public function testRecoverThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user
        $trashItem = $this->createTrashItem();

        // Load user service
        $userService = $repository->getUserService();

        // Set "Anonymous" as current user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an "UnauthorizedException"
        $trashService->recover($trashItem);
        /* END: Use Case */
    }

    /**
     * Test for the recover() method.
     */
    #[DependsExternal(TrashServiceTest::class, 'testRecover')]
    #[DependsExternal(UserServiceTest::class, 'testLoadUser')]
    public function testRecoverThrowsUnauthorizedExceptionWithNewParentLocationParameter()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();
        $permissionResolver = $repository->getPermissionResolver();

        $homeLocationId = $this->generateId('location', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user
        // $homeLocationId is the ID of the "Home" location in an Ibexa
        // demo installation

        $trashItem = $this->createTrashItem();

        // Get the new parent location
        $newParentLocation = $locationService->loadLocation($homeLocationId);

        // Load user service
        $userService = $repository->getUserService();

        // Set "Anonymous" as current user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an "UnauthorizedException"
        $trashService->recover($trashItem, $newParentLocation);
        /* END: Use Case */
    }

    /**
     * Test for the emptyTrash() method.
     */
    #[DependsExternal(TrashServiceTest::class, 'testEmptyTrash')]
    #[DependsExternal(UserServiceTest::class, 'testLoadUser')]
    public function testEmptyTrashThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user
        $this->createTrashItem();

        // Load user service
        $userService = $repository->getUserService();

        // Set "Anonymous" as current user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an "UnauthorizedException"
        $trashService->emptyTrash();
        /* END: Use Case */
    }

    /**
     * Test for the deleteTrashItem() method.
     */
    #[DependsExternal(TrashServiceTest::class, 'testDeleteTrashItem')]
    #[DependsExternal(UserServiceTest::class, 'testLoadUser')]
    public function testDeleteTrashItemThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $trashService = $repository->getTrashService();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user
        $trashItem = $this->createTrashItem();

        // Load user service
        $userService = $repository->getUserService();

        // Set "Anonymous" as current user
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an "UnauthorizedException"
        $trashService->deleteTrashItem($trashItem);
        /* END: Use Case */
    }

    public function testTrashRequiresPremissionsToRemoveAllSubitems()
    {
        $this->createRoleWithPolicies('Publisher', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'create'],
            ['module' => 'content', 'function' => 'publish'],
            ['module' => 'state', 'function' => 'assign'],
            ['module' => 'content', 'function' => 'remove', 'limitations' => [
                new ObjectStateLimitation(['limitationValues' => [
                    $this->generateId('objectstate', 2),
                ]]),
            ]],
        ]);
        $publisherUser = $this->createCustomUserWithLogin(
            'publisher',
            'publisher@example.com',
            'Publishers',
            'Publisher'
        );
        /** @var \Ibexa\Core\Repository\Repository $repository */
        $repository = $this->getRepository();
        $repository->getPermissionResolver()->setCurrentUserReference($publisherUser);
        $trashService = $repository->getTrashService();
        $locationService = $repository->getLocationService();
        $objectStateService = $repository->getObjectStateService();
        $parentContent = $this->createFolder(['eng-US' => 'Parent Folder'], 2);
        $objectStateService->setContentState(
            $parentContent->contentInfo,
            $objectStateService->loadObjectStateGroup(2),
            $objectStateService->loadObjectState(2)
        );
        $parentLocation = [...$locationService->loadLocations($parentContent->contentInfo)][0];
        $this->createFolder(['eng-US' => 'Child Folder'], $parentLocation->id);

        $this->refreshSearch($repository);
        $this->expectException(UnauthorizedException::class);
        $trashService->trash($parentLocation);
    }
}
