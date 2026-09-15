<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Bookmark\BookmarkList;

/**
 * Test case for the BookmarkService.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Contracts\Core\Repository\BookmarkService::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\BookmarkService::class, 'isBookmarked')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\BookmarkService::class, 'createBookmark')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\BookmarkService::class, 'deleteBookmark')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\BookmarkService::class, 'loadBookmarks')]
class BookmarkServiceTest extends BaseTestCase
{
    public const LOCATION_ID_BOOKMARKED = 5;
    public const LOCATION_ID_NOT_BOOKMARKED = 44;

    public function testIsBookmarked()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $location = $repository->getLocationService()->loadLocation($this->generateId('location', self::LOCATION_ID_BOOKMARKED));
        $isBookmarked = $repository->getBookmarkService()->isBookmarked($location);
        /* END: Use Case */

        self::assertTrue($isBookmarked);
    }

    public function testIsNotBookmarked()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $location = $repository->getLocationService()->loadLocation($this->generateId('location', self::LOCATION_ID_NOT_BOOKMARKED));
        $isBookmarked = $repository->getBookmarkService()->isBookmarked($location);
        /* END: Use Case */

        self::assertFalse($isBookmarked);
    }

    public function testCreateBookmark()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $bookmarkService = $repository->getBookmarkService();
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($this->generateId('location', self::LOCATION_ID_NOT_BOOKMARKED));
        $beforeCreateBookmark = $bookmarkService->isBookmarked($location);
        $bookmarkService->createBookmark($location);
        $afterCreateBookmark = $bookmarkService->isBookmarked($location);
        /* END: Use Case */

        self::assertFalse($beforeCreateBookmark);
        self::assertTrue($afterCreateBookmark);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testCreateBookmark')]
    public function testCreateBookmarkThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $bookmarkService = $repository->getBookmarkService();
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($this->generateId('location', self::LOCATION_ID_BOOKMARKED));
        $bookmarkService->createBookmark($location);
        /* END: Use Case */
    }

    public function testDeleteBookmark()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $bookmarkService = $repository->getBookmarkService();
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($this->generateId('location', self::LOCATION_ID_BOOKMARKED));

        $beforeDeleteBookmark = $bookmarkService->isBookmarked($location);
        $bookmarkService->deleteBookmark($location);
        $afterDeleteBookmark = $bookmarkService->isBookmarked($location);
        /* END: Use Case */

        self::assertTrue($beforeDeleteBookmark);
        self::assertFalse($afterDeleteBookmark);
    }

    #[\PHPUnit\Framework\Attributes\Depends('testDeleteBookmark')]
    public function testDeleteBookmarkThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $bookmarkService = $repository->getBookmarkService();
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($this->generateId('location', self::LOCATION_ID_NOT_BOOKMARKED));
        $bookmarkService->deleteBookmark($location);
        /* END: Use Case */
    }

    public function testLoadBookmarks()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $bookmarks = $repository->getBookmarkService()->loadBookmarks(1, 3);
        /* END: Use Case */

        self::assertInstanceOf(BookmarkList::class, $bookmarks);
        self::assertEquals($bookmarks->totalCount, 5);
        // Assert bookmarks order: recently added should be first
        self::assertEquals([15, 13, 12], array_map(static function ($location) {
            return $location->id;
        }, $bookmarks->items));
    }
}
