<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\EventSubscriber;

use Ibexa\Contracts\Core\Repository\Events\Trash\DeleteTrashItemEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\EmptyTrashEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\RecoverEvent;
use Ibexa\Contracts\Core\Repository\Events\Trash\TrashEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\TrashItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TrashEventSubscriber extends AbstractSearchEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RecoverEvent::class => 'onRecover',
            TrashEvent::class => 'onTrash',
            DeleteTrashItemEvent::class => 'onDeleteTrashItem',
            EmptyTrashEvent::class => 'onEmptyTrashEvent',
        ];
    }

    public function onRecover(RecoverEvent $event)
    {
        $this->indexSubtree($event->getLocation()->id);
    }

    public function onTrash(TrashEvent $event)
    {
        if ($event->getTrashItem() instanceof TrashItem) {
            $this->searchHandler->deleteContent(
                $event->getLocation()->contentId
            );
        }

        $this->searchHandler->deleteLocation(
            $event->getLocation()->id,
            $event->getLocation()->contentId
        );
    }

    public function onDeleteTrashItem(DeleteTrashItemEvent $event): void
    {
        $contentHandler = $this->persistenceHandler->contentHandler();

        $reverseRelationContentIds = $event->getResult()->reverseRelationContentIds;
        foreach ($reverseRelationContentIds as $contentId) {
            $persistenceContent = $contentHandler->load($contentId);

            $this->searchHandler->indexContent($persistenceContent);
        }
    }

    public function onEmptyTrashEvent(EmptyTrashEvent $event): void
    {
        $contentHandler = $this->persistenceHandler->contentHandler();

        $results = $event->getResultList()->getIterator();

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult $result */
        foreach ($results as $result) {
            $reverseRelationContentIds = $result->reverseRelationContentIds;
            foreach ($reverseRelationContentIds as $contentId) {
                $persistenceContent = $contentHandler->load($contentId);

                $this->searchHandler->indexContent($persistenceContent);
            }
        }
    }
}

class_alias(TrashEventSubscriber::class, 'eZ\Publish\Core\Search\Common\EventSubscriber\TrashEventSubscriber');
