<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Event;

use Ibexa\Contracts\Core\Repository\ContentTypeService as ContentTypeServiceInterface;
use Ibexa\Contracts\Core\Repository\Events\ContentType\AddFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\AssignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeAddFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeAssignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCopyContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeCreateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeDeleteContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeDeleteContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforePublishContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeRemoveContentTypeTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeRemoveFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUnassignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\BeforeUpdateFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CopyContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\CreateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\DeleteContentTypeEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\DeleteContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\PublishContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\RemoveContentTypeTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\RemoveFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UnassignContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateContentTypeDraftEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateContentTypeGroupEvent;
use Ibexa\Contracts\Core\Repository\Events\ContentType\UpdateFieldDefinitionEvent;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeDraft;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroup;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentTypeUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinitionUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Event\ContentTypeService;

class ContentTypeServiceTest extends AbstractServiceTestCase
{
    public function testAddFieldDefinitionEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddFieldDefinitionEvent::class,
            AddFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinitionCreateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->addFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAddFieldDefinitionEvent::class, 0],
            [AddFieldDefinitionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAddFieldDefinitionStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAddFieldDefinitionEvent::class,
            AddFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinitionCreateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAddFieldDefinitionEvent::class, static function (BeforeAddFieldDefinitionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->addFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAddFieldDefinitionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AddFieldDefinitionEvent::class, 0],
            [BeforeAddFieldDefinitionEvent::class, 0],
        ]);
    }

    public function testDeleteContentTypeGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentTypeGroupEvent::class,
            DeleteContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteContentTypeGroupEvent::class, 0],
            [DeleteContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteContentTypeGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentTypeGroupEvent::class,
            DeleteContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteContentTypeGroupEvent::class, static function (BeforeDeleteContentTypeGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteContentTypeGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteContentTypeGroupEvent::class, 0],
            [DeleteContentTypeGroupEvent::class, 0],
        ]);
    }

    public function testCreateContentTypeDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeDraftEvent::class,
            CreateContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeDraft')->willReturn($contentTypeDraft);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($contentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeDraftEvent::class, 0],
            [CreateContentTypeDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateContentTypeDraftResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeDraftEvent::class,
            CreateContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeDraft')->willReturn($contentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeDraftEvent::class, static function (BeforeCreateContentTypeDraftEvent $event) use ($eventContentTypeDraft) {
            $event->setContentTypeDraft($eventContentTypeDraft);
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeDraftEvent::class, 10],
            [BeforeCreateContentTypeDraftEvent::class, 0],
            [CreateContentTypeDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateContentTypeDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeDraftEvent::class,
            CreateContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeDraft')->willReturn($contentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeDraftEvent::class, static function (BeforeCreateContentTypeDraftEvent $event) use ($eventContentTypeDraft) {
            $event->setContentTypeDraft($eventContentTypeDraft);
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateContentTypeDraftEvent::class, 0],
            [CreateContentTypeDraftEvent::class, 0],
        ]);
    }

    public function testCreateContentTypeGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeGroupEvent::class,
            CreateContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroupCreateStruct::class),
        ];

        $contentTypeGroup = $this->createMock(ContentTypeGroup::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeGroup')->willReturn($contentTypeGroup);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($contentTypeGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeGroupEvent::class, 0],
            [CreateContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateContentTypeGroupResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeGroupEvent::class,
            CreateContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroupCreateStruct::class),
        ];

        $contentTypeGroup = $this->createMock(ContentTypeGroup::class);
        $eventContentTypeGroup = $this->createMock(ContentTypeGroup::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeGroup')->willReturn($contentTypeGroup);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeGroupEvent::class, static function (BeforeCreateContentTypeGroupEvent $event) use ($eventContentTypeGroup) {
            $event->setContentTypeGroup($eventContentTypeGroup);
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContentTypeGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeGroupEvent::class, 10],
            [BeforeCreateContentTypeGroupEvent::class, 0],
            [CreateContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateContentTypeGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeGroupEvent::class,
            CreateContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroupCreateStruct::class),
        ];

        $contentTypeGroup = $this->createMock(ContentTypeGroup::class);
        $eventContentTypeGroup = $this->createMock(ContentTypeGroup::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentTypeGroup')->willReturn($contentTypeGroup);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeGroupEvent::class, static function (BeforeCreateContentTypeGroupEvent $event) use ($eventContentTypeGroup) {
            $event->setContentTypeGroup($eventContentTypeGroup);
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContentTypeGroup, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateContentTypeGroupEvent::class, 0],
            [CreateContentTypeGroupEvent::class, 0],
        ]);
    }

    public function testUpdateContentTypeGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentTypeGroupEvent::class,
            UpdateContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroup::class),
            $this->createMock(ContentTypeGroupUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateContentTypeGroupEvent::class, 0],
            [UpdateContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateContentTypeGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentTypeGroupEvent::class,
            UpdateContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeGroup::class),
            $this->createMock(ContentTypeGroupUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeUpdateContentTypeGroupEvent::class, static function (BeforeUpdateContentTypeGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateContentTypeGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateContentTypeGroupEvent::class, 0],
            [UpdateContentTypeGroupEvent::class, 0],
        ]);
    }

    public function testCreateContentTypeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeEvent::class,
            CreateContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeCreateStruct::class),
            [],
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentType')->willReturn($contentTypeDraft);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($contentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeEvent::class, 0],
            [CreateContentTypeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCreateContentTypeResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeEvent::class,
            CreateContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeCreateStruct::class),
            [],
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentType')->willReturn($contentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeEvent::class, static function (BeforeCreateContentTypeEvent $event) use ($eventContentTypeDraft) {
            $event->setContentTypeDraft($eventContentTypeDraft);
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeEvent::class, 10],
            [BeforeCreateContentTypeEvent::class, 0],
            [CreateContentTypeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCreateContentTypeStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCreateContentTypeEvent::class,
            CreateContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeCreateStruct::class),
            [],
        ];

        $contentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('createContentType')->willReturn($contentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeCreateContentTypeEvent::class, static function (BeforeCreateContentTypeEvent $event) use ($eventContentTypeDraft) {
            $event->setContentTypeDraft($eventContentTypeDraft);
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->createContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeCreateContentTypeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCreateContentTypeEvent::class, 0],
            [CreateContentTypeEvent::class, 0],
        ]);
    }

    public function testRemoveContentTypeTranslationEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveContentTypeTranslationEvent::class,
            RemoveContentTypeTranslationEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            'random_value_5cff79c318f864.57583321',
        ];

        $newContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('removeContentTypeTranslation')->willReturn($newContentTypeDraft);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removeContentTypeTranslation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($newContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemoveContentTypeTranslationEvent::class, 0],
            [RemoveContentTypeTranslationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnRemoveContentTypeTranslationResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveContentTypeTranslationEvent::class,
            RemoveContentTypeTranslationEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            'random_value_5cff79c318f913.11826610',
        ];

        $newContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventNewContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('removeContentTypeTranslation')->willReturn($newContentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeRemoveContentTypeTranslationEvent::class, static function (BeforeRemoveContentTypeTranslationEvent $event) use ($eventNewContentTypeDraft) {
            $event->setNewContentTypeDraft($eventNewContentTypeDraft);
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removeContentTypeTranslation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventNewContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemoveContentTypeTranslationEvent::class, 10],
            [BeforeRemoveContentTypeTranslationEvent::class, 0],
            [RemoveContentTypeTranslationEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRemoveContentTypeTranslationStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveContentTypeTranslationEvent::class,
            RemoveContentTypeTranslationEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            'random_value_5cff79c318f983.61112462',
        ];

        $newContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $eventNewContentTypeDraft = $this->createMock(ContentTypeDraft::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('removeContentTypeTranslation')->willReturn($newContentTypeDraft);

        $traceableEventDispatcher->addListener(BeforeRemoveContentTypeTranslationEvent::class, static function (BeforeRemoveContentTypeTranslationEvent $event) use ($eventNewContentTypeDraft) {
            $event->setNewContentTypeDraft($eventNewContentTypeDraft);
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->removeContentTypeTranslation(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventNewContentTypeDraft, $result);
        self::assertSame($calledListeners, [
            [BeforeRemoveContentTypeTranslationEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRemoveContentTypeTranslationEvent::class, 0],
            [RemoveContentTypeTranslationEvent::class, 0],
        ]);
    }

    public function testUnassignContentTypeGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnassignContentTypeGroupEvent::class,
            UnassignContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->unassignContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUnassignContentTypeGroupEvent::class, 0],
            [UnassignContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUnassignContentTypeGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUnassignContentTypeGroupEvent::class,
            UnassignContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeUnassignContentTypeGroupEvent::class, static function (BeforeUnassignContentTypeGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->unassignContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUnassignContentTypeGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUnassignContentTypeGroupEvent::class, 0],
            [UnassignContentTypeGroupEvent::class, 0],
        ]);
    }

    public function testPublishContentTypeDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishContentTypeDraftEvent::class,
            PublishContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->publishContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforePublishContentTypeDraftEvent::class, 0],
            [PublishContentTypeDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testPublishContentTypeDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforePublishContentTypeDraftEvent::class,
            PublishContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforePublishContentTypeDraftEvent::class, static function (BeforePublishContentTypeDraftEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->publishContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforePublishContentTypeDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforePublishContentTypeDraftEvent::class, 0],
            [PublishContentTypeDraftEvent::class, 0],
        ]);
    }

    public function testUpdateFieldDefinitionEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateFieldDefinitionEvent::class,
            UpdateFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinition::class),
            $this->createMock(FieldDefinitionUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateFieldDefinitionEvent::class, 0],
            [UpdateFieldDefinitionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateFieldDefinitionStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateFieldDefinitionEvent::class,
            UpdateFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinition::class),
            $this->createMock(FieldDefinitionUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeUpdateFieldDefinitionEvent::class, static function (BeforeUpdateFieldDefinitionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateFieldDefinitionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateFieldDefinitionEvent::class, 0],
            [UpdateFieldDefinitionEvent::class, 0],
        ]);
    }

    public function testRemoveFieldDefinitionEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveFieldDefinitionEvent::class,
            RemoveFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinition::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->removeFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveFieldDefinitionEvent::class, 0],
            [RemoveFieldDefinitionEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testRemoveFieldDefinitionStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeRemoveFieldDefinitionEvent::class,
            RemoveFieldDefinitionEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(FieldDefinition::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeRemoveFieldDefinitionEvent::class, static function (BeforeRemoveFieldDefinitionEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->removeFieldDefinition(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeRemoveFieldDefinitionEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeRemoveFieldDefinitionEvent::class, 0],
            [RemoveFieldDefinitionEvent::class, 0],
        ]);
    }

    public function testAssignContentTypeGroupEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignContentTypeGroupEvent::class,
            AssignContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->assignContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignContentTypeGroupEvent::class, 0],
            [AssignContentTypeGroupEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testAssignContentTypeGroupStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeAssignContentTypeGroupEvent::class,
            AssignContentTypeGroupEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(ContentTypeGroup::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeAssignContentTypeGroupEvent::class, static function (BeforeAssignContentTypeGroupEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->assignContentTypeGroup(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeAssignContentTypeGroupEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [AssignContentTypeGroupEvent::class, 0],
            [BeforeAssignContentTypeGroupEvent::class, 0],
        ]);
    }

    public function testUpdateContentTypeDraftEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentTypeDraftEvent::class,
            UpdateContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(ContentTypeUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateContentTypeDraftEvent::class, 0],
            [UpdateContentTypeDraftEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testUpdateContentTypeDraftStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeUpdateContentTypeDraftEvent::class,
            UpdateContentTypeDraftEvent::class
        );

        $parameters = [
            $this->createMock(ContentTypeDraft::class),
            $this->createMock(ContentTypeUpdateStruct::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeUpdateContentTypeDraftEvent::class, static function (BeforeUpdateContentTypeDraftEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->updateContentTypeDraft(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeUpdateContentTypeDraftEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeUpdateContentTypeDraftEvent::class, 0],
            [UpdateContentTypeDraftEvent::class, 0],
        ]);
    }

    public function testDeleteContentTypeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentTypeEvent::class,
            DeleteContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteContentTypeEvent::class, 0],
            [DeleteContentTypeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testDeleteContentTypeStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeDeleteContentTypeEvent::class,
            DeleteContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
        ];

        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);

        $traceableEventDispatcher->addListener(BeforeDeleteContentTypeEvent::class, static function (BeforeDeleteContentTypeEvent $event) {
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $service->deleteContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($calledListeners, [
            [BeforeDeleteContentTypeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeDeleteContentTypeEvent::class, 0],
            [DeleteContentTypeEvent::class, 0],
        ]);
    }

    public function testCopyContentTypeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentTypeEvent::class,
            CopyContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(User::class),
        ];

        $contentTypeCopy = $this->createMock(ContentType::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('copyContentType')->willReturn($contentTypeCopy);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($contentTypeCopy, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentTypeEvent::class, 0],
            [CopyContentTypeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testReturnCopyContentTypeResultInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentTypeEvent::class,
            CopyContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(User::class),
        ];

        $contentTypeCopy = $this->createMock(ContentType::class);
        $eventContentTypeCopy = $this->createMock(ContentType::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('copyContentType')->willReturn($contentTypeCopy);

        $traceableEventDispatcher->addListener(BeforeCopyContentTypeEvent::class, static function (BeforeCopyContentTypeEvent $event) use ($eventContentTypeCopy) {
            $event->setContentTypeCopy($eventContentTypeCopy);
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());

        self::assertSame($eventContentTypeCopy, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentTypeEvent::class, 10],
            [BeforeCopyContentTypeEvent::class, 0],
            [CopyContentTypeEvent::class, 0],
        ]);
        self::assertSame([], $traceableEventDispatcher->getNotCalledListeners());
    }

    public function testCopyContentTypeStopPropagationInBeforeEvents()
    {
        $traceableEventDispatcher = $this->getEventDispatcher(
            BeforeCopyContentTypeEvent::class,
            CopyContentTypeEvent::class
        );

        $parameters = [
            $this->createMock(ContentType::class),
            $this->createMock(User::class),
        ];

        $contentTypeCopy = $this->createMock(ContentType::class);
        $eventContentTypeCopy = $this->createMock(ContentType::class);
        $innerServiceMock = $this->createMock(ContentTypeServiceInterface::class);
        $innerServiceMock->method('copyContentType')->willReturn($contentTypeCopy);

        $traceableEventDispatcher->addListener(BeforeCopyContentTypeEvent::class, static function (BeforeCopyContentTypeEvent $event) use ($eventContentTypeCopy) {
            $event->setContentTypeCopy($eventContentTypeCopy);
            $event->stopPropagation();
        }, 10);

        $service = new ContentTypeService($innerServiceMock, $traceableEventDispatcher);
        $result = $service->copyContentType(...$parameters);

        $calledListeners = $this->getListenersStack($traceableEventDispatcher->getCalledListeners());
        $notCalledListeners = $this->getListenersStack($traceableEventDispatcher->getNotCalledListeners());

        self::assertSame($eventContentTypeCopy, $result);
        self::assertSame($calledListeners, [
            [BeforeCopyContentTypeEvent::class, 10],
        ]);
        self::assertSame($notCalledListeners, [
            [BeforeCopyContentTypeEvent::class, 0],
            [CopyContentTypeEvent::class, 0],
        ]);
    }
}
