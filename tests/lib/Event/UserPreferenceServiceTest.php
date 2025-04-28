<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\UserPreference\BeforeSetUserPreferenceEvent;
use Ibexa\Contracts\Core\Repository\Events\UserPreference\SetUserPreferenceEvent;
use Ibexa\Contracts\Core\Repository\UserPreferenceService as UserPreferenceServiceInterface;
use Ibexa\Core\Event\UserPreferenceService;

class UserPreferenceServiceTest extends AbstractServiceTestCase
{
    public function testSetUserPreferenceEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetUserPreferenceEvent::class,
            SetUserPreferenceEvent::class
        );

        $parameters = [
            [],
        ];

        $innerServiceMock = $this->createMock(UserPreferenceServiceInterface::class);

        $service = new UserPreferenceService($innerServiceMock, $traceableEventDispatcher);
        $service->setUserPreference(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetUserPreferenceEvent::class, 0],
            [SetUserPreferenceEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testSetUserPreferenceStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeSetUserPreferenceEvent::class,
            SetUserPreferenceEvent::class
        );

        $parameters = [
            [],
        ];

        $innerServiceMock = $this->createMock(UserPreferenceServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeSetUserPreferenceEvent::class, static function (BeforeSetUserPreferenceEvent $event): void {
            $event->stopPropagation();
        }, 10);

        $service = new UserPreferenceService($innerServiceMock, $traceableEventDispatcher);
        $service->setUserPreference(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeSetUserPreferenceEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeSetUserPreferenceEvent::class, 0],
            [SetUserPreferenceEvent::class, 0],
        ]);
    }
}
