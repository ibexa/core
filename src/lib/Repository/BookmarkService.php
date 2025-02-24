<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Bookmark\Handler as BookmarkHandler;
use Ibexa\Contracts\Core\Repository\BookmarkService as BookmarkServiceInterface;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\Values\Bookmark\BookmarkList;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BookmarkService implements BookmarkServiceInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    protected $repository;

    /** @var \Ibexa\Contracts\Core\Persistence\Bookmark\Handler */
    protected $bookmarkHandler;

    /** @var \Psr\Log\LoggerInterface */
    private $logger;

    /**
     * BookmarkService constructor.
     *
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     * @param \Ibexa\Contracts\Core\Persistence\Bookmark\Handler $bookmarkHandler
     */
    public function __construct(RepositoryInterface $repository, BookmarkHandler $bookmarkHandler, LoggerInterface $logger = null)
    {
        $this->repository = $repository;
        $this->bookmarkHandler = $bookmarkHandler;
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function createBookmark(Location $location): void
    {
        $loadedLocation = $this->repository->getLocationService()->loadLocation($location->id);

        if ($this->isBookmarked($loadedLocation)) {
            throw new InvalidArgumentException('$location', 'Location is already bookmarked.');
        }

        $createStruct = new CreateStruct();
        $createStruct->name = $loadedLocation->contentInfo->name;
        $createStruct->locationId = $loadedLocation->id;
        $createStruct->userId = $this->getCurrentUserId();

        $this->repository->beginTransaction();
        try {
            $this->bookmarkHandler->create($createStruct);
            $this->repository->commit();
        } catch (Exception $ex) {
            $this->repository->rollback();
            throw $ex;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBookmark(Location $location): void
    {
        $loadedLocation = $this->repository->getLocationService()->loadLocation($location->id);

        $bookmarks = $this->bookmarkHandler->loadByUserIdAndLocationId(
            $this->getCurrentUserId(),
            [$loadedLocation->id]
        );

        if (empty($bookmarks)) {
            throw new InvalidArgumentException('$location', 'Location is not bookmarked.');
        }

        $this->repository->beginTransaction();
        try {
            $this->bookmarkHandler->delete(reset($bookmarks)->id);
            $this->repository->commit();
        } catch (Exception $ex) {
            $this->repository->rollback();
            throw $ex;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function loadBookmarks(int $offset = 0, int $limit = 25): BookmarkList
    {
        $currentUserId = $this->getCurrentUserId();

        $filter = new Filter();
        try {
            $filter
                ->withCriterion(new Criterion\IsBookmarked($currentUserId))
                ->withSortClause(new SortClause\BookmarkId(Query::SORT_DESC))
                ->sliceBy($limit, $offset);

            $result = $this->repository->getlocationService()->find($filter, []);
        } catch (BadStateException $e) {
            $this->logger->debug($e->getMessage(), [
                'exception' => $e,
            ]);

            return new BookmarkList();
        }

        $list = new BookmarkList();
        $list->totalCount = $result->totalCount;
        $list->items = $result->locations;

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function isBookmarked(Location $location): bool
    {
        $bookmarks = $this->bookmarkHandler->loadByUserIdAndLocationId(
            $this->getCurrentUserId(),
            [$location->id]
        );

        return !empty($bookmarks);
    }

    private function getCurrentUserId(): int
    {
        return $this->repository
            ->getPermissionResolver()
            ->getCurrentUserReference()
            ->getUserId();
    }
}

class_alias(BookmarkService::class, 'eZ\Publish\Core\Repository\BookmarkService');
