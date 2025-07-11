<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\ContentService as ContentServiceInterface;
use Ibexa\Contracts\Core\Repository\Events\Content\AddRelationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeAddRelationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeCopyContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeCreateContentDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeCreateContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeDeleteContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeDeleteRelationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeDeleteTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeDeleteVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeHideContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforePublishVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeRevealContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeUpdateContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\BeforeUpdateContentMetadataEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\CopyContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\CreateContentDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\CreateContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteRelationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\HideContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\PublishVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\RevealContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\UpdateContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\UpdateContentMetadataEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Event\ContentService;

class ContentServiceTest extends AbstractServiceTestCase
{
    public function testDeleteContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentEvent::class,
            DeleteContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $locations = [];
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('deleteContent')->willReturn($locations);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($locations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteContentEvent::class, 0],
            [DeleteContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnDeleteContentResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentEvent::class,
            DeleteContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('deleteContent')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteContentEvent::class, static function (BeforeDeleteContentEvent $event) use ($eventLocations) {
            $event->setLocations($eventLocations);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteContentEvent::class, 10],
            [BeforeDeleteContentEvent::class, 0],
            [DeleteContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentEvent::class,
            DeleteContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $locations = [];
        $eventLocations = [];
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('deleteContent')->willReturn($locations);

        $traceableEventDispatcher->addListener(BeforeDeleteContentEvent::class, static function (BeforeDeleteContentEvent $event) use ($eventLocations) {
            $event->setLocations($eventLocations);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->deleteContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventLocations, $result);
        self::assertSame($calledListeners, [
            [BeforeDeleteContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteContentEvent::class, 0],
            [DeleteContentEvent::class, 0],
        ]);
    }

    public function testCopyContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentEvent::class,
            CopyContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
            $this->createMock(VersionInfo::class),
        ];

        $content = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('copyContent')->willReturn($content);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($content, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentEvent::class, 0],
            [CopyContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCopyContentResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentEvent::class,
            CopyContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
            $this->createMock(VersionInfo::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('copyContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeCopyContentEvent::class, static function (BeforeCopyContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentEvent::class, 10],
            [BeforeCopyContentEvent::class, 0],
            [CopyContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCopyContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentEvent::class,
            CopyContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(LocationCreateStruct::class),
            $this->createMock(VersionInfo::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('copyContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeCopyContentEvent::class, static function (BeforeCopyContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCopyContentEvent::class, 0],
            [CopyContentEvent::class, 0],
        ]);
    }

    public function testUpdateContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentEvent::class,
            UpdateContentEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContent')->willReturn($content);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($content, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentEvent::class, 0],
            [UpdateContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateContentResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentEvent::class,
            UpdateContentEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeUpdateContentEvent::class, static function (BeforeUpdateContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentEvent::class, 10],
            [BeforeUpdateContentEvent::class, 0],
            [UpdateContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentEvent::class,
            UpdateContentEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeUpdateContentEvent::class, static function (BeforeUpdateContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateContentEvent::class, 0],
            [UpdateContentEvent::class, 0],
        ]);
    }

    public function testDeleteRelationEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRelationEvent::class,
            DeleteRelationEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRelation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRelationEvent::class, 0],
            [DeleteRelationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteRelationStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteRelationEvent::class,
            DeleteRelationEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteRelationEvent::class, static function (BeforeDeleteRelationEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteRelation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteRelationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteRelationEvent::class, 0],
            [DeleteRelationEvent::class, 0],
        ]);
    }

    public function testCreateContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentEvent::class,
            CreateContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentCreateStruct::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContent')->willReturn($content);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($content, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentEvent::class, 0],
            [CreateContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateContentResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentEvent::class,
            CreateContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentCreateStruct::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeCreateContentEvent::class, static function (BeforeCreateContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentEvent::class, 10],
            [BeforeCreateContentEvent::class, 0],
            [CreateContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentEvent::class,
            CreateContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentCreateStruct::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContent')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeCreateContentEvent::class, static function (BeforeCreateContentEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateContentEvent::class, 0],
            [CreateContentEvent::class, 0],
        ]);
    }

    public function testHideContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeHideContentEvent::class,
            HideContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->hideContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeHideContentEvent::class, 0],
            [HideContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testHideContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeHideContentEvent::class,
            HideContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeHideContentEvent::class, static function (BeforeHideContentEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->hideContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeHideContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeHideContentEvent::class, 0],
            [HideContentEvent::class, 0],
        ]);
    }

    public function testDeleteVersionEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteVersionEvent::class,
            DeleteVersionEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteVersion(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteVersionEvent::class, 0],
            [DeleteVersionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteVersionStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteVersionEvent::class,
            DeleteVersionEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteVersionEvent::class, static function (BeforeDeleteVersionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteVersion(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteVersionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteVersionEvent::class, 0],
            [DeleteVersionEvent::class, 0],
        ]);
    }

    public function testAddRelationEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddRelationEvent::class,
            AddRelationEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $relation = $this->createMock(Relation::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('addRelation')->willReturn($relation);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addRelation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($relation, $result);
        self::assertSame($calledListeners, [
            [BeforeAddRelationEvent::class, 0],
            [AddRelationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnAddRelationResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddRelationEvent::class,
            AddRelationEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $relation = $this->createMock(Relation::class);
        $eventRelation = $this->createMock(Relation::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('addRelation')->willReturn($relation);

        $traceableEventDispatcher->addListener(BeforeAddRelationEvent::class, static function (BeforeAddRelationEvent $event) use ($eventRelation) {
            $event->setRelation($eventRelation);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addRelation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventRelation, $result);
        self::assertSame($calledListeners, [
            [BeforeAddRelationEvent::class, 10],
            [BeforeAddRelationEvent::class, 0],
            [AddRelationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAddRelationStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddRelationEvent::class,
            AddRelationEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            $this->createMock(ContentInfo::class),
        ];

        $relation = $this->createMock(Relation::class);
        $eventRelation = $this->createMock(Relation::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('addRelation')->willReturn($relation);

        $traceableEventDispatcher->addListener(BeforeAddRelationEvent::class, static function (BeforeAddRelationEvent $event) use ($eventRelation) {
            $event->setRelation($eventRelation);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->addRelation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventRelation, $result);
        self::assertSame($calledListeners, [
            [BeforeAddRelationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AddRelationEvent::class, 0],
            [BeforeAddRelationEvent::class, 0],
        ]);
    }

    public function testUpdateContentMetadataEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentMetadataEvent::class,
            UpdateContentMetadataEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ContentMetadataUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContentMetadata')->willReturn($content);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContentMetadata(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($content, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentMetadataEvent::class, 0],
            [UpdateContentMetadataEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnUpdateContentMetadataResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentMetadataEvent::class,
            UpdateContentMetadataEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ContentMetadataUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContentMetadata')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeUpdateContentMetadataEvent::class, static function (BeforeUpdateContentMetadataEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContentMetadata(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentMetadataEvent::class, 10],
            [BeforeUpdateContentMetadataEvent::class, 0],
            [UpdateContentMetadataEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateContentMetadataStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentMetadataEvent::class,
            UpdateContentMetadataEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(ContentMetadataUpdateStruct::class),
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('updateContentMetadata')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforeUpdateContentMetadataEvent::class, static function (BeforeUpdateContentMetadataEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->updateContentMetadata(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforeUpdateContentMetadataEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateContentMetadataEvent::class, 0],
            [UpdateContentMetadataEvent::class, 0],
        ]);
    }

    public function testDeleteTranslationEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteTranslationEvent::class,
            DeleteTranslationEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            'random_value_5cff79c31a2f31.74205767',
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteTranslation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteTranslationEvent::class, 0],
            [DeleteTranslationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteTranslationStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteTranslationEvent::class,
            DeleteTranslationEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            'random_value_5cff79c31a2fc0.71971617',
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteTranslationEvent::class, static function (BeforeDeleteTranslationEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteTranslation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteTranslationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteTranslationEvent::class, 0],
            [DeleteTranslationEvent::class, 0],
        ]);
    }

    public function testPublishVersionEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishVersionEvent::class,
            PublishVersionEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('publishVersion')->willReturn($content);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->publishVersion(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($content, $result);
        self::assertSame($calledListeners, [
            [BeforePublishVersionEvent::class, 0],
            [PublishVersionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnPublishVersionResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishVersionEvent::class,
            PublishVersionEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('publishVersion')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforePublishVersionEvent::class, static function (BeforePublishVersionEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->publishVersion(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforePublishVersionEvent::class, 10],
            [BeforePublishVersionEvent::class, 0],
            [PublishVersionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testPublishVersionStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishVersionEvent::class,
            PublishVersionEvent::class
        );

        $parameters = [
            $this->createMock(VersionInfo::class),
            [],
        ];

        $content = $this->createMock(Content::class);
        $eventContent = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('publishVersion')->willReturn($content);

        $traceableEventDispatcher->addListener(BeforePublishVersionEvent::class, static function (BeforePublishVersionEvent $event) use ($eventContent) {
            $event->setContent($eventContent);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->publishVersion(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContent, $result);
        self::assertSame($calledListeners, [
            [BeforePublishVersionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforePublishVersionEvent::class, 0],
            [PublishVersionEvent::class, 0],
        ]);
    }

    public function testCreateContentDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentDraftEvent::class,
            CreateContentDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(VersionInfo::class),
            $this->createMock(User::class),
        ];

        $contentDraft = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContentDraft')->willReturn($contentDraft);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($contentDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentDraftEvent::class, 0],
            [CreateContentDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateContentDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentDraftEvent::class,
            CreateContentDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(VersionInfo::class),
            $this->createMock(User::class),
        ];

        $contentDraft = $this->createMock(Content::class);
        $eventContentDraft = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContentDraft')->willReturn($contentDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentDraftEvent::class, static function (BeforeCreateContentDraftEvent $event) use ($eventContentDraft) {
            $event->setContentDraft($eventContentDraft);
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContentDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentDraftEvent::class, 10],
            [BeforeCreateContentDraftEvent::class, 0],
            [CreateContentDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateContentDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentDraftEvent::class,
            CreateContentDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
            $this->createMock(VersionInfo::class),
            $this->createMock(User::class),
        ];

        $contentDraft = $this->createMock(Content::class);
        $eventContentDraft = $this->createMock(Content::class);
        $innerServiceMock = $this->createMock(ContentServiceInterface::class);
        $innerServiceMock->method('createContentDraft')->willReturn($contentDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentDraftEvent::class, static function (BeforeCreateContentDraftEvent $event) use ($eventContentDraft) {
            $event->setContentDraft($eventContentDraft);
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContentDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateContentDraftEvent::class, 0],
            [CreateContentDraftEvent::class, 0],
        ]);
    }

    public function testRevealContentEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRevealContentEvent::class,
            RevealContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->revealContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRevealContentEvent::class, 0],
            [RevealContentEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRevealContentStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRevealContentEvent::class,
            RevealContentEvent::class
        );

        $parameters = [
            $this->createMock(ContentInfo::class),
        ];

        $innerServiceMock = $this->createMock(ContentServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeRevealContentEvent::class, static function (BeforeRevealContentEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentService($innerServiceMock, $traceableEventDispatcher);
        $service->revealContent(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRevealContentEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRevealContentEvent::class, 0],
            [RevealContentEvent::class, 0],
        ]);
    }
}
