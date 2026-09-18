<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\Events\Section\AssignSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\AssignSectionToSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\BeforeAssignSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\BeforeAssignSectionToSubtreeEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\BeforeCreateSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\BeforeDeleteSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\BeforeUpdateSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\CreateSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\DeleteSectionEvent;
use Ibexa\Contracts\Core\Repository\Events\Section\UpdateSectionEvent;
use Ibexa\Contracts\Core\Repository\SectionService as SectionServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\SectionUpdateStruct;
use Ibexa\Core\Event\SectionService;

class SectionServiceTest extends AbstractServiceTestCase
{
    public function testAssignSectionEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignSectionEvent::class,
            AssignSectionEvent::class
        );

        $parameters = [
            self::createStub(ContentInfo::class),
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->assignSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignSectionEvent::class, 0],
            [AssignSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignSectionStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignSectionEvent::class,
            AssignSectionEvent::class
        );

        $parameters = [
            self::createStub(ContentInfo::class),
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignSectionEvent::class, static function (BeforeAssignSectionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->assignSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignSectionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignSectionEvent::class, 0],
            [BeforeAssignSectionEvent::class, 0],
        ]);
    }

    public function testUpdateSectionEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateSectionEvent::class,
            UpdateSectionEvent::class
        );

        $parameters = [
            self::createStub(Section::class),
            self::createStub(SectionUpdateStruct::class),
        ];

        $updatedSection = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('updateSection')->willReturn($updatedSection);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($updatedSection, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateSectionEvent::class, 0],
            [UpdateSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateSectionResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateSectionEvent::class,
            UpdateSectionEvent::class
        );

        $parameters = [
            self::createStub(Section::class),
            self::createStub(SectionUpdateStruct::class),
        ];

        $updatedSection = self::createStub(Section::class);
        $eventUpdatedSection = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('updateSection')->willReturn($updatedSection);

        $traceableEventDispatcher->addListener(BeforeUpdateSectionEvent::class, static function (BeforeUpdateSectionEvent $event) use ($eventUpdatedSection) {
            $event->setUpdatedSection($eventUpdatedSection);
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventUpdatedSection, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateSectionEvent::class, 10],
            [BeforeUpdateSectionEvent::class, 0],
            [UpdateSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateSectionStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateSectionEvent::class,
            UpdateSectionEvent::class
        );

        $parameters = [
            self::createStub(Section::class),
            self::createStub(SectionUpdateStruct::class),
        ];

        $updatedSection = self::createStub(Section::class);
        $eventUpdatedSection = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('updateSection')->willReturn($updatedSection);

        $traceableEventDispatcher->addListener(BeforeUpdateSectionEvent::class, static function (BeforeUpdateSectionEvent $event) use ($eventUpdatedSection) {
            $event->setUpdatedSection($eventUpdatedSection);
            $event->stopPropagation();
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventUpdatedSection, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateSectionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateSectionEvent::class, 0],
            [UpdateSectionEvent::class, 0],
        ]);
    }

    public function testAssignSectionToSubtreeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignSectionToSubtreeEvent::class,
            AssignSectionToSubtreeEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->assignSectionToSubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignSectionToSubtreeEvent::class, 0],
            [AssignSectionToSubtreeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignSectionToSubtreeStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignSectionToSubtreeEvent::class,
            AssignSectionToSubtreeEvent::class
        );

        $parameters = [
            self::createStub(Location::class),
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignSectionToSubtreeEvent::class, static function (BeforeAssignSectionToSubtreeEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->assignSectionToSubtree(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignSectionToSubtreeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignSectionToSubtreeEvent::class, 0],
            [BeforeAssignSectionToSubtreeEvent::class, 0],
        ]);
    }

    public function testDeleteSectionEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteSectionEvent::class,
            DeleteSectionEvent::class
        );

        $parameters = [
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteSectionEvent::class, 0],
            [DeleteSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteSectionStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteSectionEvent::class,
            DeleteSectionEvent::class
        );

        $parameters = [
            self::createStub(Section::class),
        ];

        $innerServiceMock = self::createStub(SectionServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteSectionEvent::class, static function (BeforeDeleteSectionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteSectionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteSectionEvent::class, 0],
            [DeleteSectionEvent::class, 0],
        ]);
    }

    public function testCreateSectionEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateSectionEvent::class,
            CreateSectionEvent::class
        );

        $parameters = [
            self::createStub(SectionCreateStruct::class),
        ];

        $section = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('createSection')->willReturn($section);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($section, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateSectionEvent::class, 0],
            [CreateSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateSectionResultInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateSectionEvent::class,
            CreateSectionEvent::class
        );

        $parameters = [
            self::createStub(SectionCreateStruct::class),
        ];

        $section = self::createStub(Section::class);
        $eventSection = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('createSection')->willReturn($section);

        $traceableEventDispatcher->addListener(BeforeCreateSectionEvent::class, static function (BeforeCreateSectionEvent $event) use ($eventSection) {
            $event->setSection($eventSection);
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventSection, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateSectionEvent::class, 10],
            [BeforeCreateSectionEvent::class, 0],
            [CreateSectionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateSectionStopPropagationInBeforeEvents(): void
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateSectionEvent::class,
            CreateSectionEvent::class
        );

        $parameters = [
            self::createStub(SectionCreateStruct::class),
        ];

        $section = self::createStub(Section::class);
        $eventSection = self::createStub(Section::class);
        $innerServiceMock = $this->createMock(SectionServiceInterface::class);
        $innerServiceMock->method('createSection')->willReturn($section);

        $traceableEventDispatcher->addListener(BeforeCreateSectionEvent::class, static function (BeforeCreateSectionEvent $event) use ($eventSection) {
            $event->setSection($eventSection);
            $event->stopPropagation();
        }, 10);

        $service = new SectionService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createSection(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventSection, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateSectionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateSectionEvent::class, 0],
            [CreateSectionEvent::class, 0],
        ]);
    }
}
