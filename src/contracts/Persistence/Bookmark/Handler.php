<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Bookmark;

use Ibexa\Contracts\Core\Persistence\Content\Location;

interface Handler
{
    /**
     * Create a new bookmark.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct $createStruct
     *
     * @return \Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark
     */
    public function create(CreateStruct $createStruct): Bookmark;

    /**
     * Delete a bookmark.
     *
     * @param int $bookmarkId
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function delete(int $bookmarkId): void;

    /**
     * Get bookmark by user id and location id.
     *
     * @param int $userId
     * @param array $locationIds
     *
     * @return \Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark[]
     */
    public function loadByUserIdAndLocationId(int $userId, array $locationIds): array;

    /**
     * Get user ids who have bookmarked given location.
     *
     * @return array<int>
     */
    public function loadUserIdsByLocation(Location $location): array;

    /**
     * Loads bookmarks owned by user.
     *
     * @deprecated The "Handler::loadUserBookmarks()" method is deprecated, will be removed in 5.0.0. Use "LocationService::find()" and "Criterion\IsBookmarked" instead.
     *
     * @param int $userId
     * @param int $offset the start offset for paging
     * @param int $limit the number of bookmarked locations returned
     *
     * @return \Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark[]
     */
    public function loadUserBookmarks(int $userId, int $offset = 0, int $limit = -1): array;

    /**
     * Count bookmarks owned by user.
     *
     * @deprecated The "Handler::countUserBookmarks()" method is deprecated, will be removed in 5.0.0. Use "LocationService::count()" and "Criterion\IsBookmarked" instead.
     *
     * @param int $userId
     *
     * @return int
     */
    public function countUserBookmarks(int $userId): int;

    /**
     * Notifies the underlying engine that a location was swapped.
     *
     * This method triggers the change of the bookmarked locations.
     *
     * @param int $location1Id ID of first location
     * @param int $location2Id ID of second location
     */
    public function locationSwapped(int $location1Id, int $location2Id): void;
}

class_alias(Handler::class, 'eZ\Publish\SPI\Persistence\Bookmark\Handler');
