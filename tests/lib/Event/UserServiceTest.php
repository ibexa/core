<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\User\AssignUserToUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeAssignUserToUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeCreateUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeCreateUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeDeleteUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeDeleteUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeMoveUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeUnAssignUserFromUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeUpdateUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeUpdateUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\BeforeUpdateUserTokenEvent;
use Ibexa\Contracts\Core\Repository\Events\User\CreateUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\CreateUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\DeleteUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\DeleteUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\MoveUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\UnAssignUserFromUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\UpdateUserEvent;
use Ibexa\Contracts\Core\Repository\Events\User\UpdateUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\User\UpdateUserTokenEvent;
use Ibexa\Contracts\Core\Repository\UserService as UserServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;
use Ibexa\Core\Event\UserService;

class UserServiceTest extends AbstractServiceTest
{
    public function testUpdateUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserGroupEvent::class,
            UpdateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
            $this->createMock(UserGroupUpdateStruct::class),
        ];

        $updatedUserGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserGroup')->willReturn($updatedUserGroup);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedUserGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserGroupEvent::class, 0],
            [UpdateUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateUserGroupResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserGroupEvent::class,
            UpdateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
            $this->createMock(UserGroupUpdateStruct::class),
        ];

        $updatedUserGroup = $this->createMock(UserGroup::class);
        $eventUpdatedUserGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserGroup')->willReturn($updatedUserGroup);

        $traceableEventDispatcher->addListener(BeforeUpdateUserGroupEvent::class, static function (BeforeUpdateUserGroupEvent $event) use ($eventUpdatedUserGroup): void {
            $event->setUpdatedUserGroup($eventUpdatedUserGroup);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedUserGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserGroupEvent::class, 10],
            [BeforeUpdateUserGroupEvent::class, 0],
            [UpdateUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserGroupEvent::class,
            UpdateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
            $this->createMock(UserGroupUpdateStruct::class),
        ];

        $updatedUserGroup = $this->createMock(UserGroup::class);
        $eventUpdatedUserGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserGroup')->willReturn($updatedUserGroup);

        $traceableEventDispatcher->addListener(BeforeUpdateUserGroupEvent::class, static function (BeforeUpdateUserGroupEvent $event) use ($eventUpdatedUserGroup): void {
            $event->setUpdatedUserGroup($eventUpdatedUserGroup);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedUserGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateUserGroupEvent::class, 0],
            [UpdateUserGroupEvent::class, 0],
        ]);
    }

    public function testUpdateUserEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserEvent::class,
            UpdateUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUser')->willReturn($updatedUser);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserEvent::class, 0],
            [UpdateUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateUserResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserEvent::class,
            UpdateUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $eventUpdatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUser')->willReturn($updatedUser);

        $traceableEventDispatcher->addListener(BeforeUpdateUserEvent::class, static function (BeforeUpdateUserEvent $event) use ($eventUpdatedUser): void {
            $event->setUpdatedUser($eventUpdatedUser);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserEvent::class, 10],
            [BeforeUpdateUserEvent::class, 0],
            [UpdateUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateUserStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserEvent::class,
            UpdateUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $eventUpdatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUser')->willReturn($updatedUser);

        $traceableEventDispatcher->addListener(BeforeUpdateUserEvent::class, static function (BeforeUpdateUserEvent $event) use ($eventUpdatedUser): void {
            $event->setUpdatedUser($eventUpdatedUser);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateUserEvent::class, 0],
            [UpdateUserEvent::class, 0],
        ]);
    }

    public function testUnAssignUserFromUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnAssignUserFromUserGroupEvent::class,
            UnAssignUserFromUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->unAssignUserFromUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUnAssignUserFromUserGroupEvent::class, 0],
            [UnAssignUserFromUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUnAssignUserFromUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnAssignUserFromUserGroupEvent::class,
            UnAssignUserFromUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeUnAssignUserFromUserGroupEvent::class, static function (BeforeUnAssignUserFromUserGroupEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->unAssignUserFromUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUnAssignUserFromUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUnAssignUserFromUserGroupEvent::class, 0],
            [UnAssignUserFromUserGroupEvent::class, 0],
        ]);
    }

    public function testDeleteUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserGroupEvent::class,
            DeleteUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
        ];

        $locations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUserGroup')->willReturn($locations);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($locations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserGroupEvent::class, 0],
            [DeleteUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnDeleteUserGroupResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserGroupEvent::class,
            DeleteUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUserGroup')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteUserGroupEvent::class, static function (BeforeDeleteUserGroupEvent $event) use ($eventLocations): void {
            $event->setLocations($eventLocations);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserGroupEvent::class, 10],
            [BeforeDeleteUserGroupEvent::class, 0],
            [DeleteUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserGroupEvent::class,
            DeleteUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUserGroup')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteUserGroupEvent::class, static function (BeforeDeleteUserGroupEvent $event) use ($eventLocations): void {
            $event->setLocations($eventLocations);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteUserGroupEvent::class, 0],
            [DeleteUserGroupEvent::class, 0],
        ]);
    }

    public function testAssignUserToUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignUserToUserGroupEvent::class,
            AssignUserToUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->assignUserToUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignUserToUserGroupEvent::class, 0],
            [AssignUserToUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignUserToUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignUserToUserGroupEvent::class,
            AssignUserToUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignUserToUserGroupEvent::class, static function (BeforeAssignUserToUserGroupEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->assignUserToUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignUserToUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignUserToUserGroupEvent::class, 0],
            [BeforeAssignUserToUserGroupEvent::class, 0],
        ]);
    }

    public function testDeleteUserEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserEvent::class,
            DeleteUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
        ];

        $locations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUser')->willReturn($locations);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($locations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserEvent::class, 0],
            [DeleteUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnDeleteUserResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserEvent::class,
            DeleteUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUser')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteUserEvent::class, static function (BeforeDeleteUserEvent $event) use ($eventLocations): void {
            $event->setLocations($eventLocations);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserEvent::class, 10],
            [BeforeDeleteUserEvent::class, 0],
            [DeleteUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteUserStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteUserEvent::class,
            DeleteUserEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('deleteUser')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteUserEvent::class, static function (BeforeDeleteUserEvent $event) use ($eventLocations): void {
            $event->setLocations($eventLocations);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteUserEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteUserEvent::class, 0],
            [DeleteUserEvent::class, 0],
        ]);
    }

    public function testMoveUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMoveUserGroupEvent::class,
            MoveUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->moveUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMoveUserGroupEvent::class, 0],
            [MoveUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testMoveUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMoveUserGroupEvent::class,
            MoveUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroup::class),
            $this->createMock(UserGroup::class),
        ];

        $innerServiceMock = $this->createMock(UserServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeMoveUserGroupEvent::class, static function (BeforeMoveUserGroupEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $service->moveUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMoveUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeMoveUserGroupEvent::class, 0],
            [MoveUserGroupEvent::class, 0],
        ]);
    }

    public function testCreateUserEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserEvent::class,
            CreateUserEvent::class
        );

        $parameters = [
            $this->createMock(UserCreateStruct::class),
            [],
        ];

        $user = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUser')->willReturn($user);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($user, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserEvent::class, 0],
            [CreateUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateUserResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserEvent::class,
            CreateUserEvent::class
        );

        $parameters = [
            $this->createMock(UserCreateStruct::class),
            [],
        ];

        $user = $this->createMock(User::class);
        $eventUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUser')->willReturn($user);

        $traceableEventDispatcher->addListener(BeforeCreateUserEvent::class, static function (BeforeCreateUserEvent $event) use ($eventUser): void {
            $event->setUser($eventUser);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUser, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserEvent::class, 10],
            [BeforeCreateUserEvent::class, 0],
            [CreateUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateUserStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserEvent::class,
            CreateUserEvent::class
        );

        $parameters = [
            $this->createMock(UserCreateStruct::class),
            [],
        ];

        $user = $this->createMock(User::class);
        $eventUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUser')->willReturn($user);

        $traceableEventDispatcher->addListener(BeforeCreateUserEvent::class, static function (BeforeCreateUserEvent $event) use ($eventUser): void {
            $event->setUser($eventUser);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUser, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateUserEvent::class, 0],
            [CreateUserEvent::class, 0],
        ]);
    }

    public function testCreateUserGroupEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserGroupEvent::class,
            CreateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroupCreateStruct::class),
            $this->createMock(UserGroup::class),
        ];

        $userGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUserGroup')->willReturn($userGroup);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($userGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserGroupEvent::class, 0],
            [CreateUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateUserGroupResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserGroupEvent::class,
            CreateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroupCreateStruct::class),
            $this->createMock(UserGroup::class),
        ];

        $userGroup = $this->createMock(UserGroup::class);
        $eventUserGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUserGroup')->willReturn($userGroup);

        $traceableEventDispatcher->addListener(BeforeCreateUserGroupEvent::class, static function (BeforeCreateUserGroupEvent $event) use ($eventUserGroup): void {
            $event->setUserGroup($eventUserGroup);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUserGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserGroupEvent::class, 10],
            [BeforeCreateUserGroupEvent::class, 0],
            [CreateUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateUserGroupStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateUserGroupEvent::class,
            CreateUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(UserGroupCreateStruct::class),
            $this->createMock(UserGroup::class),
        ];

        $userGroup = $this->createMock(UserGroup::class);
        $eventUserGroup = $this->createMock(UserGroup::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('createUserGroup')->willReturn($userGroup);

        $traceableEventDispatcher->addListener(BeforeCreateUserGroupEvent::class, static function (BeforeCreateUserGroupEvent $event) use ($eventUserGroup): void {
            $event->setUserGroup($eventUserGroup);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUserGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateUserGroupEvent::class, 0],
            [CreateUserGroupEvent::class, 0],
        ]);
    }

    public function testUpdateUserTokenEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserTokenEvent::class,
            UpdateUserTokenEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserTokenUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserToken')->willReturn($updatedUser);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserToken(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserTokenEvent::class, 0],
            [UpdateUserTokenEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateUserTokenResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserTokenEvent::class,
            UpdateUserTokenEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserTokenUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $eventUpdatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserToken')->willReturn($updatedUser);

        $traceableEventDispatcher->addListener(BeforeUpdateUserTokenEvent::class, static function (BeforeUpdateUserTokenEvent $event) use ($eventUpdatedUser): void {
            $event->setUpdatedUser($eventUpdatedUser);
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserToken(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserTokenEvent::class, 10],
            [BeforeUpdateUserTokenEvent::class, 0],
            [UpdateUserTokenEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateUserTokenStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUserTokenEvent::class,
            UpdateUserTokenEvent::class
        );

        $parameters = [
            $this->createMock(User::class),
            $this->createMock(UserTokenUpdateStruct::class),
        ];

        $updatedUser = $this->createMock(User::class);
        $eventUpdatedUser = $this->createMock(User::class);
        $innerServiceMock = $this->createMock(UserServiceInterface::class);
        $innerServiceMock->method('updateUserToken')->willReturn($updatedUser);

        $traceableEventDispatcher->addListener(BeforeUpdateUserTokenEvent::class, static function (BeforeUpdateUserTokenEvent $event) use ($eventUpdatedUser): void {
            $event->setUpdatedUser($eventUpdatedUser);
            $event->stopPropagation();
        }, 10);

        $service = new UserService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUserToken(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedUser, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUserTokenEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateUserTokenEvent::class, 0],
            [UpdateUserTokenEvent::class, 0],
        ]);
    }
}
