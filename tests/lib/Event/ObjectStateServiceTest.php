<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeCreateObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeCreateObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeDeleteObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeDeleteObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeSetContentStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeSetPriorityOfObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeUpdateObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\BeforeUpdateObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\CreateObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\CreateObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\DeleteObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\DeleteObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\SetContentStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\SetPriorityOfObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\UpdateObjectStateEvent;
use Ibexa\Contracts\Core\Repository\Events\ObjectState\UpdateObjectStateGroupEvent;
use Ibexa\Contracts\Core\Repository\ObjectStateService as ObjectStateServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectState;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroup;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateUpdateStruct;
use Ibexa\Core\Event\ObjectStateService;

class ObjectStateServiceTest extends AbstractServiceTestCase
{
    public function testSetContentStateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetContentStateEvent::class,
            SetContentStateEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectState::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->setContentState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetContentStateEvent::class, 0],
            [SetContentStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testSetContentStateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetContentStateEvent::class,
            SetContentStateEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectState::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeSetContentStateEvent::class, static function (BeforeSetContentStateEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->setContentState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetContentStateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeSetContentStateEvent::class, 0],
            [SetContentStateEvent::class, 0],
        ]);
    }

    public function testCreateObjectStateGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateGroupEvent::class,
            CreateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroupCreateStruct::class),
        ];

        $objectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectStateGroup')->willReturn($objectStateGroup);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($objectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateGroupEvent::class, 0],
            [CreateObjectStateGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateObjectStateGroupResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateGroupEvent::class,
            CreateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroupCreateStruct::class),
        ];

        $objectStateGroup = $this->createMock(ObjectStateGroup::class);
        $eventObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectStateGroup')->willReturn($objectStateGroup);

        $traceableEventDispatcher->addListener(BeforeCreateObjectStateGroupEvent::class, static function (BeforeCreateObjectStateGroupEvent $event) use ($eventObjectStateGroup) {
            $event->setObjectStateGroup($eventObjectStateGroup);
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventObjectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateGroupEvent::class, 10],
            [BeforeCreateObjectStateGroupEvent::class, 0],
            [CreateObjectStateGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateObjectStateGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateGroupEvent::class,
            CreateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroupCreateStruct::class),
        ];

        $objectStateGroup = $this->createMock(ObjectStateGroup::class);
        $eventObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectStateGroup')->willReturn($objectStateGroup);

        $traceableEventDispatcher->addListener(BeforeCreateObjectStateGroupEvent::class, static function (BeforeCreateObjectStateGroupEvent $event) use ($eventObjectStateGroup) {
            $event->setObjectStateGroup($eventObjectStateGroup);
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventObjectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateObjectStateGroupEvent::class, 0],
            [CreateObjectStateGroupEvent::class, 0],
        ]);
    }

    public function testUpdateObjectStateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateEvent::class,
            UpdateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
            $this->createMock(ObjectStateUpdateStruct::class),
        ];

        $updatedObjectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectState')->willReturn($updatedObjectState);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedObjectState, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateEvent::class, 0],
            [UpdateObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateObjectStateResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateEvent::class,
            UpdateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
            $this->createMock(ObjectStateUpdateStruct::class),
        ];

        $updatedObjectState = $this->createMock(ObjectState::class);
        $eventUpdatedObjectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectState')->willReturn($updatedObjectState);

        $traceableEventDispatcher->addListener(BeforeUpdateObjectStateEvent::class, static function (BeforeUpdateObjectStateEvent $event) use ($eventUpdatedObjectState) {
            $event->setUpdatedObjectState($eventUpdatedObjectState);
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedObjectState, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateEvent::class, 10],
            [BeforeUpdateObjectStateEvent::class, 0],
            [UpdateObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateObjectStateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateEvent::class,
            UpdateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
            $this->createMock(ObjectStateUpdateStruct::class),
        ];

        $updatedObjectState = $this->createMock(ObjectState::class);
        $eventUpdatedObjectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectState')->willReturn($updatedObjectState);

        $traceableEventDispatcher->addListener(BeforeUpdateObjectStateEvent::class, static function (BeforeUpdateObjectStateEvent $event) use ($eventUpdatedObjectState) {
            $event->setUpdatedObjectState($eventUpdatedObjectState);
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedObjectState, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateObjectStateEvent::class, 0],
            [UpdateObjectStateEvent::class, 0],
        ]);
    }

    public function testCreateObjectStateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateEvent::class,
            CreateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateCreateStruct::class),
        ];

        $objectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectState')->willReturn($objectState);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($objectState, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateEvent::class, 0],
            [CreateObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateObjectStateResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateEvent::class,
            CreateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateCreateStruct::class),
        ];

        $objectState = $this->createMock(ObjectState::class);
        $eventObjectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectState')->willReturn($objectState);

        $traceableEventDispatcher->addListener(BeforeCreateObjectStateEvent::class, static function (BeforeCreateObjectStateEvent $event) use ($eventObjectState) {
            $event->setObjectState($eventObjectState);
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventObjectState, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateEvent::class, 10],
            [BeforeCreateObjectStateEvent::class, 0],
            [CreateObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateObjectStateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateObjectStateEvent::class,
            CreateObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateCreateStruct::class),
        ];

        $objectState = $this->createMock(ObjectState::class);
        $eventObjectState = $this->createMock(ObjectState::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('createObjectState')->willReturn($objectState);

        $traceableEventDispatcher->addListener(BeforeCreateObjectStateEvent::class, static function (BeforeCreateObjectStateEvent $event) use ($eventObjectState) {
            $event->setObjectState($eventObjectState);
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventObjectState, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateObjectStateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateObjectStateEvent::class, 0],
            [CreateObjectStateEvent::class, 0],
        ]);
    }

    public function testUpdateObjectStateGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateGroupEvent::class,
            UpdateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateGroupUpdateStruct::class),
        ];

        $updatedObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectStateGroup')->willReturn($updatedObjectStateGroup);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedObjectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateGroupEvent::class, 0],
            [UpdateObjectStateGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateObjectStateGroupResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateGroupEvent::class,
            UpdateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateGroupUpdateStruct::class),
        ];

        $updatedObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $eventUpdatedObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectStateGroup')->willReturn($updatedObjectStateGroup);

        $traceableEventDispatcher->addListener(BeforeUpdateObjectStateGroupEvent::class, static function (BeforeUpdateObjectStateGroupEvent $event) use ($eventUpdatedObjectStateGroup) {
            $event->setUpdatedObjectStateGroup($eventUpdatedObjectStateGroup);
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedObjectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateGroupEvent::class, 10],
            [BeforeUpdateObjectStateGroupEvent::class, 0],
            [UpdateObjectStateGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateObjectStateGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateObjectStateGroupEvent::class,
            UpdateObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
            $this->createMock(ObjectStateGroupUpdateStruct::class),
        ];

        $updatedObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $eventUpdatedObjectStateGroup = $this->createMock(ObjectStateGroup::class);
        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);
        $innerServiceMock->method('updateObjectStateGroup')->willReturn($updatedObjectStateGroup);

        $traceableEventDispatcher->addListener(BeforeUpdateObjectStateGroupEvent::class, static function (BeforeUpdateObjectStateGroupEvent $event) use ($eventUpdatedObjectStateGroup) {
            $event->setUpdatedObjectStateGroup($eventUpdatedObjectStateGroup);
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedObjectStateGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateObjectStateGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateObjectStateGroupEvent::class, 0],
            [UpdateObjectStateGroupEvent::class, 0],
        ]);
    }

    public function testSetPriorityOfObjectStateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetPriorityOfObjectStateEvent::class,
            SetPriorityOfObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
            100,
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->setPriorityOfObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetPriorityOfObjectStateEvent::class, 0],
            [SetPriorityOfObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testSetPriorityOfObjectStateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetPriorityOfObjectStateEvent::class,
            SetPriorityOfObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
            100,
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeSetPriorityOfObjectStateEvent::class, static function (BeforeSetPriorityOfObjectStateEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->setPriorityOfObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetPriorityOfObjectStateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeSetPriorityOfObjectStateEvent::class, 0],
            [SetPriorityOfObjectStateEvent::class, 0],
        ]);
    }

    public function testDeleteObjectStateGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteObjectStateGroupEvent::class,
            DeleteObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteObjectStateGroupEvent::class, 0],
            [DeleteObjectStateGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteObjectStateGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteObjectStateGroupEvent::class,
            DeleteObjectStateGroupEvent::class
        );

        $parameters = [
            $this->createMock(ObjectStateGroup::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteObjectStateGroupEvent::class, static function (BeforeDeleteObjectStateGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteObjectStateGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteObjectStateGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteObjectStateGroupEvent::class, 0],
            [DeleteObjectStateGroupEvent::class, 0],
        ]);
    }

    public function testDeleteObjectStateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteObjectStateEvent::class,
            DeleteObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteObjectStateEvent::class, 0],
            [DeleteObjectStateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteObjectStateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteObjectStateEvent::class,
            DeleteObjectStateEvent::class
        );

        $parameters = [
            $this->createMock(ObjectState::class),
        ];

        $innerServiceMock = $this->createMock(ObjectStateServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteObjectStateEvent::class, static function (BeforeDeleteObjectStateEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ObjectStateService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteObjectState(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteObjectStateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteObjectStateEvent::class, 0],
            [DeleteObjectStateEvent::class, 0],
        ]);
    }
}
