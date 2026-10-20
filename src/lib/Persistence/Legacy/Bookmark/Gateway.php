<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Bookmark;

use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Content\Location;

/**
 * Base class for bookmark gateways.
 */
abstract class Gateway
{
    /**
     * Insert a bookmark.
     *
     * @param Bookmark $bookmark
     *
     * @return int ID
     */
    abstract public function insertBookmark(Bookmark $bookmark): int;

    /**
     * Delete bookmark with the given $id.
     *
     * @param int $id ID of bookmark
     */
    abstract public function deleteBookmark(int $id): void;

    /**
     * Load data for an bookmark with the given $userId and $locationId.
     *
     * @param int $userId ID of user
     * @param array $locationIds ID of location
     *
     * @return array
     */
    abstract public function loadBookmarkDataByUserIdAndLocationId(
        int $userId,
        array $locationIds
    ): array;

    /**
     * Load user ids by the given $location.
     *
     * @return array<int>
     */
    abstract public function loadUserIdsByLocation(Location $location): array;

    /**
     * Updates related bookmarks when location was swapped.
     *
     * @param int $location1Id ID of first location
     * @param int $location2Id ID of second location
     */
    abstract public function locationSwapped(
        int $location1Id,
        int $location2Id
    ): void;
}
