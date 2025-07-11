<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\BookmarkService as BookmarkServiceInterface;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\BeforeCreateBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\BeforeDeleteBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\CreateBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\DeleteBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Event\BookmarkService;

class BookmarkServiceTest extends AbstractServiceTestCase
{
    public function testCreateBookmarkEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateBookmarkEvent::class,
            CreateBookmarkEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(BookmarkServiceInterface::class);

        $service = new BookmarkService($innerServiceMock, $traceableEventDispatcher);
        $service->createBookmark(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeCreateBookmarkEvent::class, 0],
            [CreateBookmarkEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateBookmarkStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateBookmarkEvent::class,
            CreateBookmarkEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(BookmarkServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeCreateBookmarkEvent::class, static function (BeforeCreateBookmarkEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new BookmarkService($innerServiceMock, $traceableEventDispatcher);
        $service->createBookmark(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeCreateBookmarkEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateBookmarkEvent::class, 0],
            [CreateBookmarkEvent::class, 0],
        ]);
    }

    public function testDeleteBookmarkEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteBookmarkEvent::class,
            DeleteBookmarkEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(BookmarkServiceInterface::class);

        $service = new BookmarkService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteBookmark(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteBookmarkEvent::class, 0],
            [DeleteBookmarkEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteBookmarkStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteBookmarkEvent::class,
            DeleteBookmarkEvent::class
        );

        $parameters = [
            $this->createMock(Location::class),
        ];

        $innerServiceMock = $this->createMock(BookmarkServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteBookmarkEvent::class, static function (BeforeDeleteBookmarkEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new BookmarkService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteBookmark(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteBookmarkEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteBookmarkEvent::class, 0],
            [DeleteBookmarkEvent::class, 0],
        ]);
    }
}
