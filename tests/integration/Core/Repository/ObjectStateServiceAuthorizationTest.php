<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Core\Repository\ObjectStateService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the ObjectStateService using in memory storage.
 */
#[CoversClass(ObjectStateService::class)]
#[Group('integration')]
#[Group('authorization')]
class ObjectStateServiceAuthorizationTest extends BaseTestCase
{
    /**
     * Test for the createObjectStateGroup() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testCreateObjectStateGroup')]
    public function testCreateObjectStateGroupThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $objectStateService = $repository->getObjectStateService();

        $objectStateGroupCreate = $objectStateService->newObjectStateGroupCreateStruct(
            'publishing'
        );
        $objectStateGroupCreate->defaultLanguageCode = 'eng-GB';
        $objectStateGroupCreate->names = [
            'eng-GB' => 'Publishing',
            'eng-US' => 'Sindelfingen',
        ];
        $objectStateGroupCreate->descriptions = [
            'eng-GB' => 'Put something online',
            'eng-US' => 'Put something ton Sindelfingen.',
        ];

        // Throws unauthorized exception, since the anonymous user must not
        // create object state groups
        $createdObjectStateGroup = $objectStateService->createObjectStateGroup(
            $objectStateGroupCreate
        );
        /* END: Use Case */
    }

    /**
     * Test for the updateObjectStateGroup() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testUpdateObjectStateGroup')]
    public function testUpdateObjectStateGroupThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateGroupId = $this->generateId('objectstategroup', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $objectStateGroupId contains the ID of the standard object state
        // group ibexa_lock.
        $objectStateService = $repository->getObjectStateService();

        $loadedObjectStateGroup = $objectStateService->loadObjectStateGroup(
            $objectStateGroupId
        );

        $groupUpdateStruct = $objectStateService->newObjectStateGroupUpdateStruct();
        $groupUpdateStruct->identifier = 'sindelfingen';
        $groupUpdateStruct->defaultLanguageCode = 'ger-DE';
        $groupUpdateStruct->names = [
            'ger-DE' => 'Sindelfingen',
        ];
        $groupUpdateStruct->descriptions = [
            'ger-DE' => 'Sindelfingen ist nicht nur eine Stadt',
        ];

        // Throws unauthorized exception, since the anonymous user must not
        // update object state groups
        $updatedObjectStateGroup = $objectStateService->updateObjectStateGroup(
            $loadedObjectStateGroup,
            $groupUpdateStruct
        );
        /* END: Use Case */
    }

    /**
     * Test for the deleteObjectStateGroup() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testDeleteObjectStateGroup')]
    public function testDeleteObjectStateGroupThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateGroupId = $this->generateId('objectstategroup', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $objectStateGroupId contains the ID of the standard object state
        // group ibexa_lock.
        $objectStateService = $repository->getObjectStateService();

        $loadedObjectStateGroup = $objectStateService->loadObjectStateGroup(
            $objectStateGroupId
        );

        // Throws unauthorized exception, since the anonymous user must not
        // delete object state groups
        $objectStateService->deleteObjectStateGroup($loadedObjectStateGroup);
        /* END: Use Case */
    }

    /**
     * Test for the createObjectState() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testCreateObjectState')]
    public function testCreateObjectStateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateGroupId = $this->generateId('objectstategroup', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $objectStateGroupId contains the ID of the standard object state
        // group ibexa_lock.
        $objectStateService = $repository->getObjectStateService();

        $loadedObjectStateGroup = $objectStateService->loadObjectStateGroup(
            $objectStateGroupId
        );

        $objectStateCreateStruct = $objectStateService->newObjectStateCreateStruct(
            'locked_and_unlocked'
        );
        $objectStateCreateStruct->priority = 23;
        $objectStateCreateStruct->defaultLanguageCode = 'eng-GB';
        $objectStateCreateStruct->names = [
            'eng-GB' => 'Locked and Unlocked',
        ];
        $objectStateCreateStruct->descriptions = [
            'eng-GB' => 'A state between locked and unlocked.',
        ];

        // Throws unauthorized exception, since the anonymous user must not
        // create object states
        $createdObjectState = $objectStateService->createObjectState(
            $loadedObjectStateGroup,
            $objectStateCreateStruct
        );
        /* END: Use Case */
    }

    /**
     * Test for the updateObjectState() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testUpdateObjectState')]
    public function testUpdateObjectStateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateId = $this->generateId('objectstate', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $objectStateId contains the ID of the "locked" state
        $objectStateService = $repository->getObjectStateService();

        $loadedObjectState = $objectStateService->loadObjectState(
            $objectStateId
        );

        $updateStateStruct = $objectStateService->newObjectStateUpdateStruct();
        $updateStateStruct->identifier = 'somehow_locked';
        $updateStateStruct->defaultLanguageCode = 'ger-DE';
        $updateStateStruct->names = [
            'eng-GB' => 'Somehow locked',
            'ger-DE' => 'Irgendwie gelockt',
        ];
        $updateStateStruct->descriptions = [
            'eng-GB' => 'The object is somehow locked',
            'ger-DE' => 'Sindelfingen',
        ];

        // Throws unauthorized exception, since the anonymous user must not
        // update object states
        $updatedObjectState = $objectStateService->updateObjectState(
            $loadedObjectState,
            $updateStateStruct
        );
        /* END: Use Case */
    }

    /**
     * Test for the setPriorityOfObjectState() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testSetPriorityOfObjectState')]
    public function testSetPriorityOfObjectStateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $objectStateId = $this->generateId('objectstate', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $objectStateId contains the ID of the "locked" state
        $objectStateService = $repository->getObjectStateService();

        $initiallyLoadedObjectState = $objectStateService->loadObjectState(
            $objectStateId
        );

        // Throws unauthorized exception, since the anonymous user must not
        // set priorities for object states
        $objectStateService->setPriorityOfObjectState(
            $initiallyLoadedObjectState,
            23
        );
        /* END: Use Case */
    }

    /**
     * Test for the deleteObjectState() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testDeleteObjectState')]
    public function testDeleteObjectStateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $notLockedObjectStateId = $this->generateId('objectstate', 1);
        $lockedObjectStateId = $this->generateId('objectstate', 2);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $notLockedObjectStateId is the ID of the state "not_locked"
        $objectStateService = $repository->getObjectStateService();

        $notLockedObjectState = $objectStateService->loadObjectState($notLockedObjectStateId);

        // Throws unauthorized exception, since the anonymous user must not
        // delete object states
        $objectStateService->deleteObjectState($notLockedObjectState);
        /* END: Use Case */
    }

    /**
     * Test for the setContentState() method.
     */
    #[DependsExternal(ObjectStateServiceTest::class, 'testSetContentState')]
    public function testSetContentStateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $anonymousUserId = $this->generateId('user', 10);
        $ezLockObjectStateGroupId = $this->generateId('objectstategroup', 2);
        $lockedObjectStateId = $this->generateId('objectstate', 2);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // Set anonymous user
        $userService = $repository->getUserService();
        $permissionResolver->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // $anonymousUserId is the content ID of "Anonymous User"
        // $ezLockObjectStateGroupId contains the ID of the "ibexa_lock" object
        // state group
        // $lockedObjectStateId is the ID of the state "locked"
        $contentService = $repository->getContentService();
        $objectStateService = $repository->getObjectStateService();

        $contentInfo = $contentService->loadContentInfo($anonymousUserId);

        $ezLockObjectStateGroup = $objectStateService->loadObjectStateGroup(
            $ezLockObjectStateGroupId
        );
        $lockedObjectState = $objectStateService->loadObjectState($lockedObjectStateId);

        // Throws unauthorized exception, since the anonymous user must not
        // set object state
        $objectStateService->setContentState(
            $contentInfo,
            $ezLockObjectStateGroup,
            $lockedObjectState
        );
        /* END: Use Case */
    }
}
