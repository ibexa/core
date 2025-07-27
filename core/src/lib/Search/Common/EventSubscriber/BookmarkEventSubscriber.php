<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Common\EventSubscriber;

use Ibexa\Contracts\Core\Repository\Events\Bookmark\CreateBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\DeleteBookmarkEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @internal
 */
final class BookmarkEventSubscriber extends AbstractSearchEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CreateBookmarkEvent::class => ['onCreateBookmark', -100],
            DeleteBookmarkEvent::class => ['onDeleteBookmark', -100],
        ];
    }

    public function onCreateBookmark(CreateBookmarkEvent $event): void
    {
        $location = $event->getLocation();
        $this->updateContentIndex($location->getContentId());
        $this->updateLocationIndex($location->getId());
    }

    public function onDeleteBookmark(DeleteBookmarkEvent $event): void
    {
        $location = $event->getLocation();
        $this->updateContentIndex($location->getContentId());
        $this->updateLocationIndex($location->getId());
    }

    private function updateContentIndex(int $contentId): void
    {
        $persistenceContent = $this->persistenceHandler->contentHandler()->load($contentId);

        $this->searchHandler->indexContent($persistenceContent);
    }

    private function updateLocationIndex(int $locationId): void
    {
        $persistenceLocation = $this->persistenceHandler->locationHandler()->load($locationId);

        $this->searchHandler->indexLocation($persistenceLocation);
    }
}
