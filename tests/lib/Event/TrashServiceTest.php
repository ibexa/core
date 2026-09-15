<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Trash\BeforeDeleteTrashItemEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\BeforeEmptyTrashEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\BeforeRecoverEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\BeforeTrashEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\DeleteTrashItemEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\EmptyTrashEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\RecoverEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\TrashEvent;
use Ibexa\Contracts\Core\Repository\TrashService as TrashServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResultList;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;
use Ibexa\Core\Event\TrashService;

class TrashServiceTest extends AbstractServiceTestCase
{
    public function testEmptyTrashEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEmptyTrashEvent::class,
            EmptyTrashEvent::class
        );

        $parameters = [
        ];

        $resultList = self::createStub(TrashItemDeleteResultList::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('emptyTrash')->willReturn($resultList);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->emptyTrash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($resultList, $result);
        self::assertSame($calledListeners, [
            [BeforeEmptyTrashEvent::class, 0],
            [EmptyTrashEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnEmptyTrashResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEmptyTrashEvent::class,
            EmptyTrashEvent::class
        );

        $parameters = [
        ];

        $resultList = self::createStub(TrashItemDeleteResultList::class);
        $eventResultList = self::createStub(TrashItemDeleteResultList::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('emptyTrash')->willReturn($resultList);

        $traceableEventDispatcher->addListener(BeforeEmptyTrashEvent::class, static function (BeforeEmptyTrashEvent $event) use ($eventResultList) {
            $event->setResultList($eventResultList);
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->emptyTrash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventResultList, $result);
        self::assertSame($calledListeners, [
            [BeforeEmptyTrashEvent::class, 10],
            [BeforeEmptyTrashEvent::class, 0],
            [EmptyTrashEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testEmptyTrashStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEmptyTrashEvent::class,
            EmptyTrashEvent::class
        );

        $parameters = [
        ];

        $resultList = self::createStub(TrashItemDeleteResultList::class);
        $eventResultList = self::createStub(TrashItemDeleteResultList::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('emptyTrash')->willReturn($resultList);

        $traceableEventDispatcher->addListener(BeforeEmptyTrashEvent::class, static function (BeforeEmptyTrashEvent $event) use ($eventResultList) {
            $event->setResultList($eventResultList);
            $event->stopPropagation();
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->emptyTrash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventResultList, $result);
        self::assertSame($calledListeners, [
            [BeforeEmptyTrashEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeEmptyTrashEvent::class, 0],
            [EmptyTrashEvent::class, 0],
        ]);
    }

    public function testTrashEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTrashEvent::class,
            TrashEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
        ];

        $trashItem = self::createStub(TrashItem::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('trash')->willReturn($trashItem);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->trash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($trashItem, $result);
        self::assertSame($calledListeners, [
            [BeforeTrashEvent::class, 0],
            [TrashEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnTrashResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTrashEvent::class,
            TrashEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
        ];

        $trashItem = self::createStub(TrashItem::class);
        $eventTrashItem = self::createStub(TrashItem::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('trash')->willReturn($trashItem);

        $traceableEventDispatcher->addListener(BeforeTrashEvent::class, static function (BeforeTrashEvent $event) use ($eventTrashItem) {
            $event->setResult($eventTrashItem);
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->trash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventTrashItem, $result);
        self::assertSame($calledListeners, [
            [BeforeTrashEvent::class, 10],
            [BeforeTrashEvent::class, 0],
            [TrashEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testTrashStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTrashEvent::class,
            TrashEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
        ];

        $trashItem = self::createStub(TrashItem::class);
        $eventTrashItem = self::createStub(TrashItem::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('trash')->willReturn($trashItem);

        $traceableEventDispatcher->addListener(BeforeTrashEvent::class, static function (BeforeTrashEvent $event) use ($eventTrashItem) {
            $event->setResult($eventTrashItem);
            $event->stopPropagation();
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->trash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventTrashItem, $result);
        self::assertSame($calledListeners, [
            [BeforeTrashEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeTrashEvent::class, 0],
            [TrashEvent::class, 0],
        ]);
    }

    public function testTrashStopPropagationInBeforeEventsSetsNullResult(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTrashEvent::class,
            TrashEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
        ];

        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->expects(self::never())->method('trash');

        $traceableEventDispatcher->addListener(BeforeTrashEvent::class, static function (BeforeTrashEvent $event) {
            $event->setResult(null);
            $event->stopPropagation();
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->trash(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertNull($result);
        self::assertSame($calledListeners, [
            [BeforeTrashEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeTrashEvent::class, 0],
            [TrashEvent::class, 0],
        ]);
    }

    public function testRecoverEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRecoverEvent::class,
            RecoverEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
            self::createStub(Location::class),
        ];

        $location = self::createStub(Location::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('recover')->willReturn($location);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->recover(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($location, $result);
        self::assertSame($calledListeners, [
            [BeforeRecoverEvent::class, 0],
            [RecoverEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnRecoverResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRecoverEvent::class,
            RecoverEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
            self::createStub(Location::class),
        ];

        $location = self::createStub(Location::class);
        $eventLocation = self::createStub(Location::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('recover')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeRecoverEvent::class, static function (BeforeRecoverEvent $event) use ($eventLocation) {
            $event->setLocation($eventLocation);
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->recover(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeRecoverEvent::class, 10],
            [BeforeRecoverEvent::class, 0],
            [RecoverEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRecoverStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRecoverEvent::class,
            RecoverEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
            self::createStub(Location::class),
        ];

        $location = self::createStub(Location::class);
        $eventLocation = self::createStub(Location::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('recover')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeRecoverEvent::class, static function (BeforeRecoverEvent $event) use ($eventLocation) {
            $event->setLocation($eventLocation);
            $event->stopPropagation();
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->recover(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeRecoverEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRecoverEvent::class, 0],
            [RecoverEvent::class, 0],
        ]);
    }

    public function testDeleteTrashItemEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteTrashItemEvent::class,
            DeleteTrashItemEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
        ];

        $result = self::createStub(TrashItemDeleteResult::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('deleteTrashItem')->willReturn($result);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteTrashItem(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($result, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteTrashItemEvent::class, 0],
            [DeleteTrashItemEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnDeleteTrashItemResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteTrashItemEvent::class,
            DeleteTrashItemEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
        ];

        $result = self::createStub(TrashItemDeleteResult::class);
        $eventResult = self::createStub(TrashItemDeleteResult::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('deleteTrashItem')->willReturn($result);

        $traceableEventDispatcher->addListener(BeforeDeleteTrashItemEvent::class, static function (BeforeDeleteTrashItemEvent $event) use ($eventResult) {
            $event->setResult($eventResult);
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteTrashItem(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventResult, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteTrashItemEvent::class, 10],
            [BeforeDeleteTrashItemEvent::class, 0],
            [DeleteTrashItemEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteTrashItemStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteTrashItemEvent::class,
            DeleteTrashItemEvent::class
        );

        $parameters = [
            self::createStub(TrashItem::class),
        ];

        $result = self::createStub(TrashItemDeleteResult::class);
        $eventResult = self::createStub(TrashItemDeleteResult::class);
        $innerServiceMock = $this->createMock(TrashServiceInterface::class);
        $innerServiceMock->method('deleteTrashItem')->willReturn($result);

        $traceableEventDispatcher->addListener(BeforeDeleteTrashItemEvent::class, static function (BeforeDeleteTrashItemEvent $event) use ($eventResult) {
            $event->setResult($eventResult);
            $event->stopPropagation();
        }, 10);

        $service = new TrashService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteTrashItem(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventResult, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteTrashItemEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteTrashItemEvent::class, 0],
            [DeleteTrashItemEvent::class, 0],
        ]);
    }
}
