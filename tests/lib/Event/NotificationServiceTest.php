<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Notification\BeforeCreateNotificationEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\BeforeDeleteNotificationEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\BeforeMarkNotificationAsReadEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\BeforeMarkNotificationAsUnreadEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\CreateNotificationEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\DeleteNotificationEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\MarkNotificationAsReadEvent;
use Ibexa\Contracts\Core\Repository\Events\Notification\MarkNotificationAsUnreadEvent;
use Ibexa\Contracts\Core\Repository\NotificationService as NotificationServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;
use Ibexa\Core\Event\NotificationService;

class NotificationServiceTest extends AbstractServiceTest
{
    public function testCreateNotificationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateNotificationEvent::class,
            CreateNotificationEvent::class
        );

        $parameters = [
            $this->createMock(CreateStruct::class),
        ];

        $notification = $this->createMock(Notification::class);
        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);
        $innerServiceMock->method('createNotification')->willReturn($notification);

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createNotification(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        $this->assertSame($notification, $result);
        $this->assertSame($calledListeners, [
            [BeforeCreateNotificationEvent::class, 0],
            [CreateNotificationEvent::class, 0],
        ]);
        $this->assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateNotificationResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateNotificationEvent::class,
            CreateNotificationEvent::class
        );

        $parameters = [
            $this->createMock(CreateStruct::class),
        ];

        $notification = $this->createMock(Notification::class);
        $eventNotification = $this->createMock(Notification::class);
        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);
        $innerServiceMock->method('createNotification')->willReturn($notification);

        $traceableEventDispatcher->addListener(
            BeforeCreateNotificationEvent::class,
            static function (BeforeCreateNotificationEvent $event) use ($eventNotification): void {
                $event->setNotification($eventNotification);
            },
            10
        );

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createNotification(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        $this->assertSame($eventNotification, $result);
        $this->assertSame($calledListeners, [
            [BeforeCreateNotificationEvent::class, 10],
            [BeforeCreateNotificationEvent::class, 0],
            [CreateNotificationEvent::class, 0],
        ]);
        $this->assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateNotificationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateNotificationEvent::class,
            CreateNotificationEvent::class
        );

        $parameters = [
            $this->createMock(CreateStruct::class),
        ];

        $notification = $this->createMock(Notification::class);
        $eventNotification = $this->createMock(Notification::class);
        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);
        $innerServiceMock->method('createNotification')->willReturn($notification);

        $traceableEventDispatcher->addListener(
            BeforeCreateNotificationEvent::class,
            static function (BeforeCreateNotificationEvent $event) use ($eventNotification): void {
                $event->setNotification($eventNotification);
                $event->stopPropagation();
            },
            10
        );

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createNotification(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        $this->assertSame($eventNotification, $result);
        $this->assertSame($calledListeners, [
            [BeforeCreateNotificationEvent::class, 10],
        ]);
        $this->assertSame($notCalledListeners, [
            [BeforeCreateNotificationEvent::class, 0],
            [CreateNotificationEvent::class, 0],
        ]);
    }

    public function testDeleteNotificationEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteNotificationEvent::class,
            DeleteNotificationEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteNotification(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        $this->assertSame($calledListeners, [
            [BeforeDeleteNotificationEvent::class, 0],
            [DeleteNotificationEvent::class, 0],
        ]);
        $this->assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteNotificationStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteNotificationEvent::class,
            DeleteNotificationEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $traceableEventDispatcher->addListener(
            BeforeDeleteNotificationEvent::class,
            static function (BeforeDeleteNotificationEvent $event): void {
                $event->stopPropagation();
            },
            10
        );

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteNotification(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        $this->assertSame($calledListeners, [
            [BeforeDeleteNotificationEvent::class, 10],
        ]);
        $this->assertSame($notCalledListeners, [
            [BeforeDeleteNotificationEvent::class, 0],
            [DeleteNotificationEvent::class, 0],
        ]);
    }

    public function testMarkNotificationAsReadEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMarkNotificationAsReadEvent::class,
            MarkNotificationAsReadEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->markNotificationAsRead(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        $this->assertSame($calledListeners, [
            [BeforeMarkNotificationAsReadEvent::class, 0],
            [MarkNotificationAsReadEvent::class, 0],
        ]);
        $this->assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testMarkNotificationAsUnreadEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMarkNotificationAsUnreadEvent::class,
            MarkNotificationAsUnreadEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->markNotificationAsUnread(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMarkNotificationAsUnreadEvent::class, 0],
            [MarkNotificationAsUnreadEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testMarkNotificationAsReadStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMarkNotificationAsReadEvent::class,
            MarkNotificationAsReadEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $traceableEventDispatcher->addListener(
            BeforeMarkNotificationAsReadEvent::class,
            static function (BeforeMarkNotificationAsReadEvent $event): void {
                $event->stopPropagation();
            },
            10
        );

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->markNotificationAsRead(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        $this->assertSame($calledListeners, [
            [BeforeMarkNotificationAsReadEvent::class, 10],
        ]);
        $this->assertSame($notCalledListeners, [
            [BeforeMarkNotificationAsReadEvent::class, 0],
            [MarkNotificationAsReadEvent::class, 0],
        ]);
    }

    public function testMarkNotificationAsUnreadStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeMarkNotificationAsUnreadEvent::class,
            MarkNotificationAsUnreadEvent::class
        );

        $parameters = [
            $this->createMock(Notification::class),
        ];

        $innerServiceMock = $this->createMock(NotificationServiceInterface::class);

        $traceableEventDispatcher->addListener(
            BeforeMarkNotificationAsUnreadEvent::class,
            static function (BeforeMarkNotificationAsUnreadEvent $event): void {
                $event->stopPropagation();
            },
            10
        );

        $service = new NotificationService($innerServiceMock, $traceableEventDispatcher);
        $service->markNotificationAsUnread(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeMarkNotificationAsUnreadEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeMarkNotificationAsUnreadEvent::class, 0],
            [MarkNotificationAsUnreadEvent::class, 0],
        ]);
    }
}

class_alias(NotificationServiceTest::class, 'eZ\Publish\Core\Event\Tests\NotificationServiceTest');
