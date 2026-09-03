<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;

/**
 * Test case for the BookmarkService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\BookmarkService
 */
class BookmarkServiceTest extends BaseTest
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

        $this->assertTrue($isBookmarked);
    }

    public function testIsNotBookmarked()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $location = $repository->getLocationService()->loadLocation($this->generateId('location', self::LOCATION_ID_NOT_BOOKMARKED));
        $isBookmarked = $repository->getBookmarkService()->isBookmarked($location);
        /* END: Use Case */

        $this->assertFalse($isBookmarked);
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

        $this->assertFalse($beforeCreateBookmark);
        $this->assertTrue($afterCreateBookmark);
    }

    /**
     * @depends testCreateBookmark
     */
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

        $this->assertTrue($beforeDeleteBookmark);
        $this->assertFalse($afterDeleteBookmark);
    }

    /**
     * @depends testDeleteBookmark
     */
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

        self::assertEquals(5, $bookmarks->totalCount);
        // Assert bookmarks order: recently added should be first
        self::assertEquals([15, 13, 12], array_map(static function ($location) {
            return $location->id;
        }, $bookmarks->items));
    }

    public function testCountBookmarks(): void
    {
        $repository = $this->getRepository();

        $filter = new Filter();
        $filter
            ->withCriterion(new Criterion\Location\IsBookmarked(true));
        $count = $repository->getLocationService()->count($filter, []);

        self::assertEquals(5, $count);
    }

    /**
     * Regression test for IBX-6773: bookmarking an item and then losing read access to it used to
     * make the whole bookmark list explode with an UnauthorizedException, because every bookmark
     * was resolved through LocationService::loadLocation(). The item must simply be filtered out
     * instead.
     */
    public function testLoadBookmarksSkipsBookmarksUserLostAccessTo(): void
    {
        $repository = $this->getRepository();
        $sectionService = $repository->getSectionService();
        $permissionResolver = $repository->getPermissionResolver();
        $bookmarkService = $repository->getBookmarkService();

        $administratorUser = $permissionResolver->getCurrentUserReference();

        // A section the restricted user will *not* be allowed to read
        $sectionCreateStruct = $sectionService->newSectionCreateStruct();
        $sectionCreateStruct->name = 'Restricted';
        $sectionCreateStruct->identifier = 'restricted_bookmarks';
        $restrictedSection = $sectionService->createSection($sectionCreateStruct);

        // Created as administrator, so it lands in the Standard section (ID 1)
        $folder = $this->createFolder(['eng-GB' => 'Bookmarked folder'], 2);
        $folderLocationId = $folder->getContentInfo()->getMainLocationId();
        self::assertNotNull($folderLocationId);

        // User may only read content in the Standard section
        $user = $this->createUserWithPolicies(
            'bookmark_section_limited',
            [
                [
                    'module' => 'content',
                    'function' => 'read',
                    'limitations' => [new SectionLimitation(['limitationValues' => [1]])],
                ],
            ]
        );

        $permissionResolver->setCurrentUserReference($user);

        $bookmarkService->createBookmark(
            $repository->getLocationService()->loadLocation($folderLocationId)
        );

        // Sanity check: while readable, the bookmark shows up
        $bookmarks = $bookmarkService->loadBookmarks();
        self::assertSame(1, $bookmarks->totalCount);
        self::assertSame(
            [$folderLocationId],
            array_map(
                static function (Location $location): int {
                    return $location->getId();
                },
                $bookmarks->items
            )
        );

        // Move the bookmarked item out of reach of the user
        $permissionResolver->setCurrentUserReference($administratorUser);
        $sectionService->assignSection($folder->getContentInfo(), $restrictedSection);

        $permissionResolver->setCurrentUserReference($user);

        // Used to throw UnauthorizedException
        $bookmarksAfterLosingAccess = $bookmarkService->loadBookmarks();

        self::assertSame(
            0,
            $bookmarksAfterLosingAccess->totalCount,
            'Bookmark of a no longer readable item should not be counted'
        );
        self::assertSame(
            [],
            $bookmarksAfterLosingAccess->items,
            'Bookmark of a no longer readable item should not be listed'
        );
    }

    public function testLoadBookmarksAfterTrashingBookmarkedLocation(): void
    {
        $repository = $this->getRepository();
        $bookmarkService = $repository->getBookmarkService();

        $folder = $this->createFolder(['eng-GB' => 'Folder to be trashed'], 2);
        $location = $this->loadMainLocation($folder);

        $bookmarkService->createBookmark($location);
        self::assertBookmarkRowCount(1, $location->getId(), $this->getRawDatabaseConnection());

        $repository->getTrashService()->trash($location);

        $this->assertBookmarkGone($location->getId());
    }

    public function testLoadBookmarksAfterDeletingBookmarkedContent(): void
    {
        $repository = $this->getRepository();
        $bookmarkService = $repository->getBookmarkService();

        $folder = $this->createFolder(['eng-GB' => 'Folder to be deleted'], 2);
        $location = $this->loadMainLocation($folder);

        $bookmarkService->createBookmark($location);
        self::assertBookmarkRowCount(1, $location->getId(), $this->getRawDatabaseConnection());

        $repository->getContentService()->deleteContent($folder->getContentInfo());

        $this->assertBookmarkGone($location->getId());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function loadMainLocation(Content $content): Location
    {
        $mainLocationId = $content->getContentInfo()->getMainLocationId();
        self::assertNotNull($mainLocationId);

        return $this->getRepository()->getLocationService()->loadLocation($mainLocationId);
    }

    /**
     * Asserts both that the bookmark is no longer listed and that its row is actually gone.
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \ErrorException
     */
    private function assertBookmarkGone(int $locationId): void
    {
        $bookmarks = $this->getRepository()->getBookmarkService()->loadBookmarks(0, 9999);

        foreach ($bookmarks as $bookmarkedLocation) {
            self::assertNotEquals(
                $locationId,
                $bookmarkedLocation->getId(),
                'Bookmark of a removed Location should not be listed'
            );
        }

        self::assertBookmarkRowCount(0, $locationId, $this->getRawDatabaseConnection());
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private static function assertBookmarkRowCount(
        int $expectedCount,
        int $locationId,
        Connection $connection
    ): void {
        $query = $connection->createQueryBuilder();
        $query
            ->select('COUNT(' . DoctrineDatabase::COLUMN_ID . ')')
            ->from(DoctrineDatabase::TABLE_BOOKMARKS)
            ->where(
                $query->expr()->eq(
                    DoctrineDatabase::COLUMN_LOCATION_ID,
                    $query->createNamedParameter($locationId, ParameterType::INTEGER)
                )
            );

        self::assertSame(
            $expectedCount,
            (int)$query->execute()->fetchColumn(),
            sprintf(
                'Expected %d "%s" row(s) for Location %d',
                $expectedCount,
                DoctrineDatabase::TABLE_BOOKMARKS,
                $locationId
            )
        );
    }
}

class_alias(BookmarkServiceTest::class, 'eZ\Publish\API\Repository\Tests\BookmarkServiceTest');
