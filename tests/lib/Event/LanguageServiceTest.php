<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Language\BeforeCreateLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\BeforeDeleteLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\BeforeDisableLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\BeforeEnableLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\BeforeUpdateLanguageNameEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\CreateLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\DeleteLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\DisableLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\EnableLanguageEvent;
use Ibexa\Contracts\Core\Repository\Events\Language\UpdateLanguageNameEvent;
use Ibexa\Contracts\Core\Repository\LanguageService as LanguageServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\LanguageCreateStruct;
use Ibexa\Core\Event\LanguageService;

class LanguageServiceTest extends AbstractServiceTestCase
{
    public function testDeleteLanguageEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteLanguageEvent::class,
            DeleteLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $innerServiceMock = self::createStub(LanguageServiceInterface::class);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteLanguageEvent::class, 0],
            [DeleteLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteLanguageStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteLanguageEvent::class,
            DeleteLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $innerServiceMock = self::createStub(LanguageServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteLanguageEvent::class, static function (BeforeDeleteLanguageEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteLanguageEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteLanguageEvent::class, 0],
            [DeleteLanguageEvent::class, 0],
        ]);
    }

    public function testCreateLanguageEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLanguageEvent::class,
            CreateLanguageEvent::class
        );

        $parameters = [
            self::createStub(LanguageCreateStruct::class),
        ];

        $language = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('createLanguage')->willReturn($language);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($language, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLanguageEvent::class, 0],
            [CreateLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateLanguageResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLanguageEvent::class,
            CreateLanguageEvent::class
        );

        $parameters = [
            self::createStub(LanguageCreateStruct::class),
        ];

        $language = self::createStub(Language::class);
        $eventLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('createLanguage')->willReturn($language);

        $traceableEventDispatcher->addListener(BeforeCreateLanguageEvent::class, static function (BeforeCreateLanguageEvent $event) use ($eventLanguage) {
            $event->setLanguage($eventLanguage);
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLanguageEvent::class, 10],
            [BeforeCreateLanguageEvent::class, 0],
            [CreateLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateLanguageStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateLanguageEvent::class,
            CreateLanguageEvent::class
        );

        $parameters = [
            self::createStub(LanguageCreateStruct::class),
        ];

        $language = self::createStub(Language::class);
        $eventLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('createLanguage')->willReturn($language);

        $traceableEventDispatcher->addListener(BeforeCreateLanguageEvent::class, static function (BeforeCreateLanguageEvent $event) use ($eventLanguage) {
            $event->setLanguage($eventLanguage);
            $event->stopPropagation();
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateLanguageEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateLanguageEvent::class, 0],
            [CreateLanguageEvent::class, 0],
        ]);
    }

    public function testUpdateLanguageNameEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLanguageNameEvent::class,
            UpdateLanguageNameEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
            'random_value_5cff79c3161276.87987683',
        ];

        $updatedLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('updateLanguageName')->willReturn($updatedLanguage);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLanguageName(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLanguageNameEvent::class, 0],
            [UpdateLanguageNameEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateLanguageNameResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLanguageNameEvent::class,
            UpdateLanguageNameEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
            'random_value_5cff79c3161312.94068030',
        ];

        $updatedLanguage = self::createStub(Language::class);
        $eventUpdatedLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('updateLanguageName')->willReturn($updatedLanguage);

        $traceableEventDispatcher->addListener(BeforeUpdateLanguageNameEvent::class, static function (BeforeUpdateLanguageNameEvent $event) use ($eventUpdatedLanguage) {
            $event->setUpdatedLanguage($eventUpdatedLanguage);
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLanguageName(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLanguageNameEvent::class, 10],
            [BeforeUpdateLanguageNameEvent::class, 0],
            [UpdateLanguageNameEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateLanguageNameStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateLanguageNameEvent::class,
            UpdateLanguageNameEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
            'random_value_5cff79c3161386.01414999',
        ];

        $updatedLanguage = self::createStub(Language::class);
        $eventUpdatedLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('updateLanguageName')->willReturn($updatedLanguage);

        $traceableEventDispatcher->addListener(BeforeUpdateLanguageNameEvent::class, static function (BeforeUpdateLanguageNameEvent $event) use ($eventUpdatedLanguage) {
            $event->setUpdatedLanguage($eventUpdatedLanguage);
            $event->stopPropagation();
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateLanguageName(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateLanguageNameEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateLanguageNameEvent::class, 0],
            [UpdateLanguageNameEvent::class, 0],
        ]);
    }

    public function testDisableLanguageEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDisableLanguageEvent::class,
            DisableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $disabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('disableLanguage')->willReturn($disabledLanguage);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->disableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($disabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeDisableLanguageEvent::class, 0],
            [DisableLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnDisableLanguageResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDisableLanguageEvent::class,
            DisableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $disabledLanguage = self::createStub(Language::class);
        $eventDisabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('disableLanguage')->willReturn($disabledLanguage);

        $traceableEventDispatcher->addListener(BeforeDisableLanguageEvent::class, static function (BeforeDisableLanguageEvent $event) use ($eventDisabledLanguage) {
            $event->setDisabledLanguage($eventDisabledLanguage);
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->disableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventDisabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeDisableLanguageEvent::class, 10],
            [BeforeDisableLanguageEvent::class, 0],
            [DisableLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDisableLanguageStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDisableLanguageEvent::class,
            DisableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $disabledLanguage = self::createStub(Language::class);
        $eventDisabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('disableLanguage')->willReturn($disabledLanguage);

        $traceableEventDispatcher->addListener(BeforeDisableLanguageEvent::class, static function (BeforeDisableLanguageEvent $event) use ($eventDisabledLanguage) {
            $event->setDisabledLanguage($eventDisabledLanguage);
            $event->stopPropagation();
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->disableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventDisabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeDisableLanguageEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDisableLanguageEvent::class, 0],
            [DisableLanguageEvent::class, 0],
        ]);
    }

    public function testEnableLanguageEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEnableLanguageEvent::class,
            EnableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $enabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('enableLanguage')->willReturn($enabledLanguage);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->enableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($enabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeEnableLanguageEvent::class, 0],
            [EnableLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnEnableLanguageResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEnableLanguageEvent::class,
            EnableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $enabledLanguage = self::createStub(Language::class);
        $eventEnabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('enableLanguage')->willReturn($enabledLanguage);

        $traceableEventDispatcher->addListener(BeforeEnableLanguageEvent::class, static function (BeforeEnableLanguageEvent $event) use ($eventEnabledLanguage) {
            $event->setEnabledLanguage($eventEnabledLanguage);
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->enableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventEnabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeEnableLanguageEvent::class, 10],
            [BeforeEnableLanguageEvent::class, 0],
            [EnableLanguageEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testEnableLanguageStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeEnableLanguageEvent::class,
            EnableLanguageEvent::class
        );

        $parameters = [
            self::createStub(Language::class),
        ];

        $enabledLanguage = self::createStub(Language::class);
        $eventEnabledLanguage = self::createStub(Language::class);
        $innerServiceMock = $this->createMock(LanguageServiceInterface::class);
        $innerServiceMock->method('enableLanguage')->willReturn($enabledLanguage);

        $traceableEventDispatcher->addListener(BeforeEnableLanguageEvent::class, static function (BeforeEnableLanguageEvent $event) use ($eventEnabledLanguage) {
            $event->setEnabledLanguage($eventEnabledLanguage);
            $event->stopPropagation();
        }, 10);

        $service = new LanguageService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->enableLanguage(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventEnabledLanguage, $result);
        self::assertSame($calledListeners, [
            [BeforeEnableLanguageEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeEnableLanguageEvent::class, 0],
            [EnableLanguageEvent::class, 0],
        ]);
    }
}
