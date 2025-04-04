<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common\EventSubscriber;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Events\Content\CopyContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\DeleteTranslationEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\HideContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\PublishVersionEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\RevealContentEvent;
use Ibexa\Contracts\Core\Repository\Events\Content\UpdateContentMetadataEvent;
use Ibexa\Contracts\Core\Search\ContentTranslationHandler;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContentEventSubscriber extends AbstractSearchEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CopyContentEvent::class => 'onCopyContent',
            DeleteContentEvent::class => 'onDeleteContent',
            DeleteTranslationEvent::class => 'onDeleteTranslation',
            HideContentEvent::class => 'onHideContent',
            PublishVersionEvent::class => 'onPublishVersion',
            RevealContentEvent::class => 'onRevealContent',
            UpdateContentMetadataEvent::class => 'onUpdateContentMetadata',
        ];
    }

    public function onCopyContent(CopyContentEvent $event)
    {
        $this->searchHandler->indexContent(
            $this->persistenceHandler->contentHandler()->load(
                $event->getContent()->getVersionInfo()->getContentInfo()->id,
                $event->getContent()->getVersionInfo()->versionNo
            )
        );

        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent(
            $event->getContent()->getVersionInfo()->getContentInfo()->id
        );

        foreach ($locations as $location) {
            $this->searchHandler->indexLocation($location);
        }
    }

    public function onDeleteContent(DeleteContentEvent $event)
    {
        $this->searchHandler->deleteContent($event->getContentInfo()->id);

        foreach ($event->getLocations() as $locationId) {
            $this->searchHandler->deleteLocation($locationId, $event->getContentInfo()->id);
        }
    }

    public function onDeleteTranslation(DeleteTranslationEvent $event)
    {
        $contentInfo = $this->persistenceHandler->contentHandler()->loadContentInfo(
            $event->getContentInfo()->id
        );

        if ($contentInfo->status !== ContentInfo::STATUS_PUBLISHED) {
            return;
        }

        if ($this->searchHandler instanceof ContentTranslationHandler) {
            $this->searchHandler->deleteTranslation(
                $contentInfo->id,
                $event->getLanguageCode()
            );
        }

        $this->searchHandler->indexContent(
            $this->persistenceHandler->contentHandler()->load(
                $contentInfo->id,
                $contentInfo->currentVersionNo
            )
        );

        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent(
            $contentInfo->id
        );

        foreach ($locations as $location) {
            $this->searchHandler->indexLocation($location);
        }
    }

    public function onHideContent(HideContentEvent $event)
    {
        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent($event->getContentInfo()->id);
        foreach ($locations as $location) {
            $this->indexSubtree($location->id);
        }
    }

    public function onPublishVersion(PublishVersionEvent $event)
    {
        $this->searchHandler->indexContent(
            $this->persistenceHandler->contentHandler()->load($event->getContent()->id, $event->getContent()->getVersionInfo()->versionNo)
        );

        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent($event->getContent()->id);
        foreach ($locations as $location) {
            $this->searchHandler->indexLocation($location);
        }
    }

    public function onRevealContent(RevealContentEvent $event)
    {
        $locations = $this->persistenceHandler->locationHandler()->loadLocationsByContent($event->getContentInfo()->id);
        foreach ($locations as $location) {
            $this->indexSubtree($location->id);
        }
    }

    public function onUpdateContentMetadata(UpdateContentMetadataEvent $event)
    {
        $contentInfo = $this->persistenceHandler->contentHandler()->loadContentInfo($event->getContent()->id);
        if ($contentInfo->status !== ContentInfo::STATUS_PUBLISHED) {
            return;
        }
        $this->searchHandler->indexContent(
            $this->persistenceHandler->contentHandler()->load($contentInfo->id, $contentInfo->currentVersionNo)
        );
        $this->searchHandler->indexLocation($this->persistenceHandler->locationHandler()->load($contentInfo->mainLocationId));
    }
}
