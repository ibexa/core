<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\URLWildcard\BeforeCreateEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\BeforeRemoveEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\BeforeTranslateEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\BeforeUpdateEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\CreateEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\RemoveEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\TranslateEvent;
use Ibexa\Contracts\Core\Repository\Events\URLWildcard\UpdateEvent;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\URLWildcardService as URLWildcardServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardTranslationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcardUpdateStruct;
use Ibexa\Core\Event\URLWildcardService;

class URLWildcardServiceTest extends AbstractServiceTestCase
{
    /**
     * @throws UnauthorizedException
     */
    public function testRemoveEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveEvent::class,
            RemoveEvent::class
        );

        $parameters = [
            $this->createMock(URLWildcard::class),
        ];

        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $service->remove(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveEvent::class, 0],
            [RemoveEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    /**
     * @throws UnauthorizedException
     */
    public function testRemoveStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveEvent::class,
            RemoveEvent::class
        );

        $parameters = [
            $this->createMock(URLWildcard::class),
        ];

        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeRemoveEvent::class, static function (BeforeRemoveEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $service->remove(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRemoveEvent::class, 0],
            [RemoveEvent::class, 0],
        ]);
    }

    /**
     * @throws BadStateException
     * @throws ContentValidationException
     * @throws InvalidArgumentException
     * @throws UnauthorizedException
     */
    public function testUpdateEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateEvent::class,
            UpdateEvent::class
        );

        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $service->update(
            $this->createMock(URLWildcard::class),
            new URLWildcardUpdateStruct()
        );

        $calledListeners = $this->getListenersStack(
            $traceableEventDispatcher->getCalledListeners()
        );

        self::assertSame($calledListeners, [
            [BeforeUpdateEvent::class, 0],
            [UpdateEvent::class, 0],
        ]);

        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateEvent::class,
            UpdateEvent::class
        );

        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);

        $traceableEventDispatcher->addListener(
            BeforeUpdateEvent::class,
            static function (BeforeUpdateEvent $event) {
                $event->stopPropagation();
            },
            10
        );

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $service->update(
            $this->createMock(URLWildcard::class),
            new URLWildcardUpdateStruct()
        );

        $calledListeners = $this->getListenersStack(
            $traceableEventDispatcher->getCalledListeners()
        );
        $notCalledListeners = $this->getListenersStack(
            $traceableEventDispatcher->getNotCalledListeners()
        );

        self::assertSame($calledListeners, [
            [BeforeUpdateEvent::class, 10],
        ]);

        self::assertSame($notCalledListeners, [
            [BeforeUpdateEvent::class, 0],
            [UpdateEvent::class, 0],
        ]);
    }

    public function testCreateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateEvent::class,
            CreateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316c1f5.58580131',
            'random_value_5cff79c316c223.93334332',
            'random_value_5cff79c316c237.08397355',
        ];

        $urlWildcard = $this->createMock(URLWildcard::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('create')->willReturn($urlWildcard);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->create(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($urlWildcard, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateEvent::class, 0],
            [CreateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateEvent::class,
            CreateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316c2d5.26653678',
            'random_value_5cff79c316c2e7.55400833',
            'random_value_5cff79c316c2f8.59874187',
        ];

        $urlWildcard = $this->createMock(URLWildcard::class);
        $eventUrlWildcard = $this->createMock(URLWildcard::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('create')->willReturn($urlWildcard);

        $traceableEventDispatcher->addListener(BeforeCreateEvent::class, static function (BeforeCreateEvent $event) use ($eventUrlWildcard) {
            $event->setUrlWildcard($eventUrlWildcard);
        }, 10);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->create(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUrlWildcard, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateEvent::class, 10],
            [BeforeCreateEvent::class, 0],
            [CreateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateEvent::class,
            CreateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316c359.46056769',
            'random_value_5cff79c316c361.53134429',
            'random_value_5cff79c316c374.82657815',
        ];

        $urlWildcard = $this->createMock(URLWildcard::class);
        $eventUrlWildcard = $this->createMock(URLWildcard::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('create')->willReturn($urlWildcard);

        $traceableEventDispatcher->addListener(BeforeCreateEvent::class, static function (BeforeCreateEvent $event) use ($eventUrlWildcard) {
            $event->setUrlWildcard($eventUrlWildcard);
            $event->stopPropagation();
        }, 10);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->create(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUrlWildcard, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateEvent::class, 0],
            [CreateEvent::class, 0],
        ]);
    }

    public function testTranslateEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTranslateEvent::class,
            TranslateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316cfa7.72466150',
        ];

        $result = $this->createMock(URLWildcardTranslationResult::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('translate')->willReturn($result);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->translate(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($result, $result);
        self::assertSame($calledListeners, [
            [BeforeTranslateEvent::class, 0],
            [TranslateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnTranslateResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTranslateEvent::class,
            TranslateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316d370.25863709',
        ];

        $result = $this->createMock(URLWildcardTranslationResult::class);
        $eventResult = $this->createMock(URLWildcardTranslationResult::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('translate')->willReturn($result);

        $traceableEventDispatcher->addListener(BeforeTranslateEvent::class, static function (BeforeTranslateEvent $event) use ($eventResult) {
            $event->setResult($eventResult);
        }, 10);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->translate(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventResult, $result);
        self::assertSame($calledListeners, [
            [BeforeTranslateEvent::class, 10],
            [BeforeTranslateEvent::class, 0],
            [TranslateEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testTranslateStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeTranslateEvent::class,
            TranslateEvent::class
        );

        $parameters = [
            'random_value_5cff79c316d3f9.73226122',
        ];

        $result = $this->createMock(URLWildcardTranslationResult::class);
        $eventResult = $this->createMock(URLWildcardTranslationResult::class);
        $innerServiceMock = $this->createMock(URLWildcardServiceInterface::class);
        $innerServiceMock->method('translate')->willReturn($result);

        $traceableEventDispatcher->addListener(BeforeTranslateEvent::class, static function (BeforeTranslateEvent $event) use ($eventResult) {
            $event->setResult($eventResult);
            $event->stopPropagation();
        }, 10);

        $service = new URLWildcardService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->translate(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventResult, $result);
        self::assertSame($calledListeners, [
            [BeforeTranslateEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeTranslateEvent::class, 0],
            [TranslateEvent::class, 0],
        ]);
    }
}
