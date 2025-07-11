<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Role\AddPolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\AssignRoleToUserEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\AssignRoleToUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeAddPolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeAssignRoleToUserEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeAssignRoleToUserGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeCreateRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeCreateRoleEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeDeleteRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeDeleteRoleEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforePublishRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeRemovePolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeRemoveRoleAssignmentEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeUpdatePolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\BeforeUpdateRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\CreateRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\CreateRoleEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\DeleteRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\DeleteRoleEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\PublishRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\RemovePolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\RemoveRoleAssignmentEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\UpdatePolicyByRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Role\UpdateRoleDraftEvent;
use Ibexa\Contracts\Core\Repository\RoleService as RoleServiceInterface;
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
use Ibexa\Core\Event\RoleService;

class RoleServiceTest extends AbstractServiceTestCase
{
    public function testPublishRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishRoleDraftEvent::class,
            PublishRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->publishRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforePublishRoleDraftEvent::class, 0],
            [PublishRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testPublishRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishRoleDraftEvent::class,
            PublishRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforePublishRoleDraftEvent::class, static function (BeforePublishRoleDraftEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->publishRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforePublishRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforePublishRoleDraftEvent::class, 0],
            [PublishRoleDraftEvent::class, 0],
        ]);
    }

    public function testAssignRoleToUserEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignRoleToUserEvent::class,
            AssignRoleToUserEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(User::class),
            $this->createMock(RoleLimitation::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->assignRoleToUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignRoleToUserEvent::class, 0],
            [AssignRoleToUserEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignRoleToUserStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignRoleToUserEvent::class,
            AssignRoleToUserEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(User::class),
            $this->createMock(RoleLimitation::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignRoleToUserEvent::class, static function (BeforeAssignRoleToUserEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->assignRoleToUser(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignRoleToUserEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignRoleToUserEvent::class, 0],
            [BeforeAssignRoleToUserEvent::class, 0],
        ]);
    }

    public function testUpdateRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateRoleDraftEvent::class,
            UpdateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(RoleUpdateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updateRoleDraft')->willReturn($updatedRoleDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateRoleDraftEvent::class, 0],
            [UpdateRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateRoleDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateRoleDraftEvent::class,
            UpdateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(RoleUpdateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updateRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeUpdateRoleDraftEvent::class, static function (BeforeUpdateRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateRoleDraftEvent::class, 10],
            [BeforeUpdateRoleDraftEvent::class, 0],
            [UpdateRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateRoleDraftEvent::class,
            UpdateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(RoleUpdateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updateRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeUpdateRoleDraftEvent::class, static function (BeforeUpdateRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateRoleDraftEvent::class, 0],
            [UpdateRoleDraftEvent::class, 0],
        ]);
    }

    public function testAssignRoleToUserGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignRoleToUserGroupEvent::class,
            AssignRoleToUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(UserGroup::class),
            $this->createMock(RoleLimitation::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->assignRoleToUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignRoleToUserGroupEvent::class, 0],
            [AssignRoleToUserGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignRoleToUserGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignRoleToUserGroupEvent::class,
            AssignRoleToUserGroupEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
            $this->createMock(UserGroup::class),
            $this->createMock(RoleLimitation::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignRoleToUserGroupEvent::class, static function (BeforeAssignRoleToUserGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->assignRoleToUserGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignRoleToUserGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignRoleToUserGroupEvent::class, 0],
            [BeforeAssignRoleToUserGroupEvent::class, 0],
        ]);
    }

    public function testUpdatePolicyByRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdatePolicyByRoleDraftEvent::class,
            UpdatePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
            $this->createMock(PolicyUpdateStruct::class),
        ];

        $updatedPolicyDraft = $this->createMock(PolicyDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updatePolicyByRoleDraft')->willReturn($updatedPolicyDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updatePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedPolicyDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdatePolicyByRoleDraftEvent::class, 0],
            [UpdatePolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdatePolicyByRoleDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdatePolicyByRoleDraftEvent::class,
            UpdatePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
            $this->createMock(PolicyUpdateStruct::class),
        ];

        $updatedPolicyDraft = $this->createMock(PolicyDraft::class);
        $eventUpdatedPolicyDraft = $this->createMock(PolicyDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updatePolicyByRoleDraft')->willReturn($updatedPolicyDraft);

        $traceableEventDispatcher->addListener(BeforeUpdatePolicyByRoleDraftEvent::class, static function (BeforeUpdatePolicyByRoleDraftEvent $event) use ($eventUpdatedPolicyDraft) {
            $event->setUpdatedPolicyDraft($eventUpdatedPolicyDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updatePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedPolicyDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdatePolicyByRoleDraftEvent::class, 10],
            [BeforeUpdatePolicyByRoleDraftEvent::class, 0],
            [UpdatePolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdatePolicyByRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdatePolicyByRoleDraftEvent::class,
            UpdatePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
            $this->createMock(PolicyUpdateStruct::class),
        ];

        $updatedPolicyDraft = $this->createMock(PolicyDraft::class);
        $eventUpdatedPolicyDraft = $this->createMock(PolicyDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('updatePolicyByRoleDraft')->willReturn($updatedPolicyDraft);

        $traceableEventDispatcher->addListener(BeforeUpdatePolicyByRoleDraftEvent::class, static function (BeforeUpdatePolicyByRoleDraftEvent $event) use ($eventUpdatedPolicyDraft) {
            $event->setUpdatedPolicyDraft($eventUpdatedPolicyDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updatePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedPolicyDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdatePolicyByRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdatePolicyByRoleDraftEvent::class, 0],
            [UpdatePolicyByRoleDraftEvent::class, 0],
        ]);
    }

    public function testCreateRoleEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleEvent::class,
            CreateRoleEvent::class
        );

        $parameters = [
            $this->createMock(RoleCreateStruct::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRole')->willReturn($roleDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRole(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($roleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleEvent::class, 0],
            [CreateRoleEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateRoleResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleEvent::class,
            CreateRoleEvent::class
        );

        $parameters = [
            $this->createMock(RoleCreateStruct::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $eventRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRole')->willReturn($roleDraft);

        $traceableEventDispatcher->addListener(BeforeCreateRoleEvent::class, static function (BeforeCreateRoleEvent $event) use ($eventRoleDraft) {
            $event->setRoleDraft($eventRoleDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRole(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleEvent::class, 10],
            [BeforeCreateRoleEvent::class, 0],
            [CreateRoleEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateRoleStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleEvent::class,
            CreateRoleEvent::class
        );

        $parameters = [
            $this->createMock(RoleCreateStruct::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $eventRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRole')->willReturn($roleDraft);

        $traceableEventDispatcher->addListener(BeforeCreateRoleEvent::class, static function (BeforeCreateRoleEvent $event) use ($eventRoleDraft) {
            $event->setRoleDraft($eventRoleDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRole(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateRoleEvent::class, 0],
            [CreateRoleEvent::class, 0],
        ]);
    }

    public function testRemovePolicyByRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemovePolicyByRoleDraftEvent::class,
            RemovePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('removePolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemovePolicyByRoleDraftEvent::class, 0],
            [RemovePolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnRemovePolicyByRoleDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemovePolicyByRoleDraftEvent::class,
            RemovePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('removePolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeRemovePolicyByRoleDraftEvent::class, static function (BeforeRemovePolicyByRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemovePolicyByRoleDraftEvent::class, 10],
            [BeforeRemovePolicyByRoleDraftEvent::class, 0],
            [RemovePolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRemovePolicyByRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemovePolicyByRoleDraftEvent::class,
            RemovePolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyDraft::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('removePolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeRemovePolicyByRoleDraftEvent::class, static function (BeforeRemovePolicyByRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removePolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemovePolicyByRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRemovePolicyByRoleDraftEvent::class, 0],
            [RemovePolicyByRoleDraftEvent::class, 0],
        ]);
    }

    public function testAddPolicyByRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddPolicyByRoleDraftEvent::class,
            AddPolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyCreateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('addPolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addPolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeAddPolicyByRoleDraftEvent::class, 0],
            [AddPolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnAddPolicyByRoleDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddPolicyByRoleDraftEvent::class,
            AddPolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyCreateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('addPolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeAddPolicyByRoleDraftEvent::class, static function (BeforeAddPolicyByRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addPolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeAddPolicyByRoleDraftEvent::class, 10],
            [BeforeAddPolicyByRoleDraftEvent::class, 0],
            [AddPolicyByRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAddPolicyByRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddPolicyByRoleDraftEvent::class,
            AddPolicyByRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
            $this->createMock(PolicyCreateStruct::class),
        ];

        $updatedRoleDraft = $this->createMock(RoleDraft::class);
        $eventUpdatedRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('addPolicyByRoleDraft')->willReturn($updatedRoleDraft);

        $traceableEventDispatcher->addListener(BeforeAddPolicyByRoleDraftEvent::class, static function (BeforeAddPolicyByRoleDraftEvent $event) use ($eventUpdatedRoleDraft) {
            $event->setUpdatedRoleDraft($eventUpdatedRoleDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addPolicyByRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeAddPolicyByRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AddPolicyByRoleDraftEvent::class, 0],
            [BeforeAddPolicyByRoleDraftEvent::class, 0],
        ]);
    }

    public function testDeleteRoleEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRoleEvent::class,
            DeleteRoleEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRole(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRoleEvent::class, 0],
            [DeleteRoleEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteRoleStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRoleEvent::class,
            DeleteRoleEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteRoleEvent::class, static function (BeforeDeleteRoleEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRole(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRoleEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteRoleEvent::class, 0],
            [DeleteRoleEvent::class, 0],
        ]);
    }

    public function testDeleteRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRoleDraftEvent::class,
            DeleteRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRoleDraftEvent::class, 0],
            [DeleteRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRoleDraftEvent::class,
            DeleteRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(RoleDraft::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteRoleDraftEvent::class, static function (BeforeDeleteRoleDraftEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteRoleDraftEvent::class, 0],
            [DeleteRoleDraftEvent::class, 0],
        ]);
    }

    public function testRemoveRoleAssignmentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveRoleAssignmentEvent::class,
            RemoveRoleAssignmentEvent::class
        );

        $parameters = [
            $this->createMock(RoleAssignment::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->removeRoleAssignment(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveRoleAssignmentEvent::class, 0],
            [RemoveRoleAssignmentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRemoveRoleAssignmentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveRoleAssignmentEvent::class,
            RemoveRoleAssignmentEvent::class
        );

        $parameters = [
            $this->createMock(RoleAssignment::class),
        ];

        $innerServiceMock = $this->createMock(RoleServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeRemoveRoleAssignmentEvent::class, static function (BeforeRemoveRoleAssignmentEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $service->removeRoleAssignment(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveRoleAssignmentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRemoveRoleAssignmentEvent::class, 0],
            [RemoveRoleAssignmentEvent::class, 0],
        ]);
    }

    public function testCreateRoleDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleDraftEvent::class,
            CreateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRoleDraft')->willReturn($roleDraft);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($roleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleDraftEvent::class, 0],
            [CreateRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateRoleDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleDraftEvent::class,
            CreateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $eventRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRoleDraft')->willReturn($roleDraft);

        $traceableEventDispatcher->addListener(BeforeCreateRoleDraftEvent::class, static function (BeforeCreateRoleDraftEvent $event) use ($eventRoleDraft) {
            $event->setRoleDraft($eventRoleDraft);
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleDraftEvent::class, 10],
            [BeforeCreateRoleDraftEvent::class, 0],
            [CreateRoleDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateRoleDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateRoleDraftEvent::class,
            CreateRoleDraftEvent::class
        );

        $parameters = [
            $this->createMock(Role::class),
        ];

        $roleDraft = $this->createMock(RoleDraft::class);
        $eventRoleDraft = $this->createMock(RoleDraft::class);
        $innerServiceMock = $this->createMock(RoleServiceInterface::class);
        $innerServiceMock->method('createRoleDraft')->willReturn($roleDraft);

        $traceableEventDispatcher->addListener(BeforeCreateRoleDraftEvent::class, static function (BeforeCreateRoleDraftEvent $event) use ($eventRoleDraft) {
            $event->setRoleDraft($eventRoleDraft);
            $event->stopPropagation();
        }, 10);

        $service = new RoleService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createRoleDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventRoleDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateRoleDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateRoleDraftEvent::class, 0],
            [CreateRoleDraftEvent::class, 0],
        ]);
    }
}
