<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\URL\BeforeUpdateUrlEvent;
use Ibexa\Contracts\Core\Repository\Events\URL\UpdateUrlEvent;
use Ibexa\Contracts\Core\Repository\URLService as URLServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\URLUpdateStruct;
use Ibexa\Core\Event\URLService;

class URLServiceTest extends AbstractServiceTestCase
{
    public function testUpdateUrlEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUrlEvent::class,
            UpdateUrlEvent::class
        );

        $parameters = [
            $this->createMock(URL::class),
            $this->createMock(URLUpdateStruct::class),
        ];

        $updatedUrl = $this->createMock(URL::class);
        $innerServiceMock = $this->createMock(URLServiceInterface::class);
        $innerServiceMock->method('updateUrl')->willReturn($updatedUrl);

        $service = new URLService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUrl(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedUrl, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUrlEvent::class, 0],
            [UpdateUrlEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateUrlResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUrlEvent::class,
            UpdateUrlEvent::class
        );

        $parameters = [
            $this->createMock(URL::class),
            $this->createMock(URLUpdateStruct::class),
        ];

        $updatedUrl = $this->createMock(URL::class);
        $eventUpdatedUrl = $this->createMock(URL::class);
        $innerServiceMock = $this->createMock(URLServiceInterface::class);
        $innerServiceMock->method('updateUrl')->willReturn($updatedUrl);

        $traceableEventDispatcher->addListener(BeforeUpdateUrlEvent::class, static function (BeforeUpdateUrlEvent $event) use ($eventUpdatedUrl) {
            $event->setUpdatedUrl($eventUpdatedUrl);
        }, 10);

        $service = new URLService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUrl(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedUrl, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUrlEvent::class, 10],
            [BeforeUpdateUrlEvent::class, 0],
            [UpdateUrlEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateUrlStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateUrlEvent::class,
            UpdateUrlEvent::class
        );

        $parameters = [
            $this->createMock(URL::class),
            $this->createMock(URLUpdateStruct::class),
        ];

        $updatedUrl = $this->createMock(URL::class);
        $eventUpdatedUrl = $this->createMock(URL::class);
        $innerServiceMock = $this->createMock(URLServiceInterface::class);
        $innerServiceMock->method('updateUrl')->willReturn($updatedUrl);

        $traceableEventDispatcher->addListener(BeforeUpdateUrlEvent::class, static function (BeforeUpdateUrlEvent $event) use ($eventUpdatedUrl) {
            $event->setUpdatedUrl($eventUpdatedUrl);
            $event->stopPropagation();
        }, 10);

        $service = new URLService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateUrl(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedUrl, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateUrlEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateUrlEvent::class, 0],
            [UpdateUrlEvent::class, 0],
        ]);
    }
}
