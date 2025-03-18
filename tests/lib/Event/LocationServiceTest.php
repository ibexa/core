<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Location\BeforeCopySubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeCreateLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeDeleteLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeHideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeMoveSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeSwapLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeUnhideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\BeforeUpdateLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\CopySubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\CreateLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\DeleteLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\HideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\MoveSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\SwapLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\UnhideLocationEvent;
use Ibexa\Contracts\Core\Repository\Events\Location\UpdateLocationEvent;
use Ibexa\Contracts\Core\Repository\LocationService as LocationServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationUpdateStruct;
use Ibexa\Core\Event\LocationService;

class LocationServiceTest extends AbstractServiceTest
{
    public function testCopySubtreeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopySubtreeEvent::class,
            CopySubtreeEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $location = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('copySubtree')->willReturn($location);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copySubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($location, $result);
        self::assertSame($calledListeners, [
            [BeforeCopySubtreeEvent::class, 0],
            [CopySubtreeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCopySubtreeResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopySubtreeEvent::class,
            CopySubtreeEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $location = $this->createMock(Location::class);
        $eventLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('copySubtree')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeCopySubtreeEvent::class, static function (BeforeCopySubtreeEvent $event) use ($eventLocation): void {
            $event->setLocation($eventLocation);
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copySubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeCopySubtreeEvent::class, 10],
            [BeforeCopySubtreeEvent::class, 0],
            [CopySubtreeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCopySubtreeStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopySubtreeEvent::class,
            CopySubtreeEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $location = $this->createMock(Location::class);
        $eventLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('copySubtree')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeCopySubtreeEvent::class, static function (BeforeCopySubtreeEvent $event) use ($eventLocation): void {
            $event->setLocation($eventLocation);
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copySubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeCopySubtreeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCopySubtreeEvent::class, 0],
            [CopySubtreeEvent::class, 0],
        ]);
    }

    public function testDeleteLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteLocationEvent::class,
            DeleteLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteLocationEvent::class, 0],
            [DeleteLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteLocationEvent::class,
            DeleteLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteLocationEvent::class, static function (BeforeDeleteLocationEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteLocationEvent::class, 0],
            [DeleteLocationEvent::class, 0],
        ]);
    }

    public function testUnhideLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnhideLocationEvent::class,
            UnhideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $revealedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('unhideLocation')->willReturn($revealedLocation);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->unhideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($revealedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUnhideLocationEvent::class, 0],
            [UnhideLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUnhideLocationResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnhideLocationEvent::class,
            UnhideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $revealedLocation = $this->createMock(Location::class);
        $eventRevealedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('unhideLocation')->willReturn($revealedLocation);

        $traceableEventDispatcher->addListener(BeforeUnhideLocationEvent::class, static function (BeforeUnhideLocationEvent $event) use ($eventRevealedLocation): void {
            $event->setRevealedLocation($eventRevealedLocation);
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->unhideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventRevealedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUnhideLocationEvent::class, 10],
            [BeforeUnhideLocationEvent::class, 0],
            [UnhideLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUnhideLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnhideLocationEvent::class,
            UnhideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $revealedLocation = $this->createMock(Location::class);
        $eventRevealedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('unhideLocation')->willReturn($revealedLocation);

        $traceableEventDispatcher->addListener(BeforeUnhideLocationEvent::class, static function (BeforeUnhideLocationEvent $event) use ($eventRevealedLocation): void {
            $event->setRevealedLocation($eventRevealedLocation);
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->unhideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventRevealedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUnhideLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUnhideLocationEvent::class, 0],
            [UnhideLocationEvent::class, 0],
        ]);
    }

    public function testHideLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeHideLocationEvent::class,
            HideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $hiddenLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('hideLocation')->willReturn($hiddenLocation);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->hideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($hiddenLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeHideLocationEvent::class, 0],
            [HideLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnHideLocationResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeHideLocationEvent::class,
            HideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $hiddenLocation = $this->createMock(Location::class);
        $eventHiddenLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('hideLocation')->willReturn($hiddenLocation);

        $traceableEventDispatcher->addListener(BeforeHideLocationEvent::class, static function (BeforeHideLocationEvent $event) use ($eventHiddenLocation): void {
            $event->setHiddenLocation($eventHiddenLocation);
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->hideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventHiddenLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeHideLocationEvent::class, 10],
            [BeforeHideLocationEvent::class, 0],
            [HideLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testHideLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeHideLocationEvent::class,
            HideLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $hiddenLocation = $this->createMock(Location::class);
        $eventHiddenLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('hideLocation')->willReturn($hiddenLocation);

        $traceableEventDispatcher->addListener(BeforeHideLocationEvent::class, static function (BeforeHideLocationEvent $event) use ($eventHiddenLocation): void {
            $event->setHiddenLocation($eventHiddenLocation);
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->hideLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventHiddenLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeHideLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeHideLocationEvent::class, 0],
            [HideLocationEvent::class, 0],
        ]);
    }

    public function testSwapLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSwapLocationEvent::class,
            SwapLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->swapLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSwapLocationEvent::class, 0],
            [SwapLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testSwapLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSwapLocationEvent::class,
            SwapLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeSwapLocationEvent::class, static function (BeforeSwapLocationEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->swapLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSwapLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeSwapLocationEvent::class, 0],
            [SwapLocationEvent::class, 0],
        ]);
    }

    public function testMoveSubtreeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMoveSubtreeEvent::class,
            MoveSubtreeEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->moveSubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMoveSubtreeEvent::class, 0],
            [MoveSubtreeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testMoveSubtreeStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMoveSubtreeEvent::class,
            MoveSubtreeEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(LocationServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeMoveSubtreeEvent::class, static function (BeforeMoveSubtreeEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $service->moveSubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMoveSubtreeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeMoveSubtreeEvent::class, 0],
            [MoveSubtreeEvent::class, 0],
        ]);
    }

    public function testUpdateLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLocationEvent::class,
            UpdateLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(LocationUpdateStruct::class),
        ];

        $updatedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('updateLocation')->willReturn($updatedLocation);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLocationEvent::class, 0],
            [UpdateLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateLocationResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLocationEvent::class,
            UpdateLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(LocationUpdateStruct::class),
        ];

        $updatedLocation = $this->createMock(Location::class);
        $eventUpdatedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('updateLocation')->willReturn($updatedLocation);

        $traceableEventDispatcher->addListener(BeforeUpdateLocationEvent::class, static function (BeforeUpdateLocationEvent $event) use ($eventUpdatedLocation): void {
            $event->setUpdatedLocation($eventUpdatedLocation);
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLocationEvent::class, 10],
            [BeforeUpdateLocationEvent::class, 0],
            [UpdateLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLocationEvent::class,
            UpdateLocationEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
            $this->createMock(LocationUpdateStruct::class),
        ];

        $updatedLocation = $this->createMock(Location::class);
        $eventUpdatedLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('updateLocation')->willReturn($updatedLocation);

        $traceableEventDispatcher->addListener(BeforeUpdateLocationEvent::class, static function (BeforeUpdateLocationEvent $event) use ($eventUpdatedLocation): void {
            $event->setUpdatedLocation($eventUpdatedLocation);
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateLocationEvent::class, 0],
            [UpdateLocationEvent::class, 0],
        ]);
    }

    public function testCreateLocationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLocationEvent::class,
            CreateLocationEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
        ];

        $location = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('createLocation')->willReturn($location);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($location, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLocationEvent::class, 0],
            [CreateLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateLocationResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLocationEvent::class,
            CreateLocationEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
        ];

        $location = $this->createMock(Location::class);
        $eventLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('createLocation')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeCreateLocationEvent::class, static function (BeforeCreateLocationEvent $event) use ($eventLocation): void {
            $event->setLocation($eventLocation);
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLocationEvent::class, 10],
            [BeforeCreateLocationEvent::class, 0],
            [CreateLocationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateLocationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLocationEvent::class,
            CreateLocationEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
        ];

        $location = $this->createMock(Location::class);
        $eventLocation = $this->createMock(Location::class);
        $innerServiceMock = $this->createMock(LocationServiceInterface::class);
        $innerServiceMock->method('createLocation')->willReturn($location);

        $traceableEventDispatcher->addListener(BeforeCreateLocationEvent::class, static function (BeforeCreateLocationEvent $event) use ($eventLocation): void {
            $event->setLocation($eventLocation);
            $event->stopPropagation();
        }, 10);

        $service = new LocationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLocation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocation, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLocationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateLocationEvent::class, 0],
            [CreateLocationEvent::class, 0],
        ]);
    }
}
