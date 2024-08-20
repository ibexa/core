<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Common\EventSubscriber;

use Ibexa\Contracts\Core\Repository\Events\Bookmark\CreateBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Events\Bookmark\DeleteBookmarkEvent;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Core\Search\Common\Indexer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class BookmarkEventSubscriber implements EventSubscriberInterface
{
    /** @var \Ibexa\Core\Search\Common\Indexer&\Ibexa\Core\Search\Common\IncrementalIndexer */
    private Indexer $indexer;

    /**
     * @param \Ibexa\Core\Search\Common\Indexer&\Ibexa\Core\Search\Common\IncrementalIndexer $indexer
     */
    public function __construct(Indexer $indexer)
    {
        $this->indexer = $indexer;
    }

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

        $this->updateSearchIndex($location);
    }

    public function onDeleteBookmark(DeleteBookmarkEvent $event): void
    {
        $location = $event->getLocation();

        $this->updateSearchIndex($location);
    }

    private function updateSearchIndex(Location $location): void
    {
        $contentId = $location->getContentId();

        $this->indexer->updateSearchIndex([$contentId], true);
    }
}
