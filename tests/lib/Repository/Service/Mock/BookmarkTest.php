<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationList;
use Ibexa\Core\Repository\BookmarkService;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Core\Repository\Values\User\UserReference;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversMethod(BookmarkService::class, 'createBookmark')]
#[CoversMethod(BookmarkService::class, 'deleteBookmark')]
#[CoversMethod(BookmarkService::class, 'loadBookmarks')]
#[CoversMethod(BookmarkService::class, 'isBookmarked')]
class BookmarkTest extends BaseServiceMockTest
{
    public const BOOKMARK_ID = 2;
    public const CURRENT_USER_ID = 7;
    public const LOCATION_ID = 1;

    /** @var \Ibexa\Contracts\Core\Persistence\Bookmark\Handler|\PHPUnit\Framework\MockObject\MockObject */
    private $bookmarkHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bookmarkHandler = $this->getPersistenceMockHandler('Bookmark\\Handler');

        $permissionResolverMock = $this->createMock(PermissionResolver::class);
        $permissionResolverMock
            ->expects(self::atLeastOnce())
            ->method('getCurrentUserReference')
            ->willReturn(new UserReference(self::CURRENT_USER_ID));

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::atLeastOnce())
            ->method('getPermissionResolver')
            ->willReturn($permissionResolverMock);
    }

    public function testCreateBookmark()
    {
        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([]);

        $this->assertTransactionIsCommitted(function () {
            $this->bookmarkHandler
                ->expects($this->once())
                ->method('create')
                ->willReturnCallback(function (CreateStruct $createStruct) {
                    $this->assertEquals(self::LOCATION_ID, $createStruct->locationId);
                    $this->assertEquals(self::CURRENT_USER_ID, $createStruct->userId);

                    return new Bookmark();
                });
        });

        $this->createBookmarkService()->createBookmark($location);
    }

    public function testCreateBookmarkThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([self::LOCATION_ID => new Bookmark()]);

        $this->assertTransactionIsNotStarted(function () {
            $this->bookmarkHandler->expects($this->never())->method('create');
        });

        $this->createBookmarkService()->createBookmark($location);
    }

    public function testCreateBookmarkWithRollback()
    {
        $this->expectException(\Exception::class);

        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([]);

        $this->assertTransactionIsRollback(function () {
            $this->bookmarkHandler
                ->expects($this->once())
                ->method('create')
                ->willThrowException(self::createStub(Exception::class));
        });

        $this->createBookmarkService()->createBookmark($location);
    }

    public function testDeleteBookmarkExisting()
    {
        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $bookmark = new Bookmark(['id' => self::BOOKMARK_ID]);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([self::LOCATION_ID => $bookmark]);

        $this->assertTransactionIsCommitted(function () use ($bookmark) {
            $this->bookmarkHandler
                ->expects($this->once())
                ->method('delete')
                ->with($bookmark->id);
        });

        $this->createBookmarkService()->deleteBookmark($location);
    }

    public function testDeleteBookmarkWithRollback()
    {
        $this->expectException(\Exception::class);

        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([self::LOCATION_ID => new Bookmark(['id' => self::BOOKMARK_ID])]);

        $this->assertTransactionIsRollback(function () {
            $this->bookmarkHandler
                ->expects($this->once())
                ->method('delete')
                ->willThrowException(self::createStub(Exception::class));
        });

        $this->createBookmarkService()->deleteBookmark($location);
    }

    public function testDeleteBookmarkNonExisting()
    {
        $this->expectException(InvalidArgumentException::class);

        $location = $this->createLocation(self::LOCATION_ID);

        $this->assertLocationIsLoaded($location);

        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([]);

        $this->assertTransactionIsNotStarted(function () {
            $this->bookmarkHandler->expects($this->never())->method('delete');
        });

        $this->createBookmarkService()->deleteBookmark($location);
    }

    public function testLoadBookmarks()
    {
        $offset = 0;
        $limit = 25;

        $expectedTotalCount = 10;
        $expectedItems = array_map(function ($locationId) {
            return $this->createLocation($locationId);
        }, range(1, $expectedTotalCount));
        $locationList = new LocationList(['totalCount' => $expectedTotalCount, 'locations' => $expectedItems]);

        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock
            ->expects(self::once())
            ->method('find')
            ->willReturn($locationList);

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::any())
            ->method('getLocationService')
            ->willReturn($locationServiceMock);

        // All tests in this class expect a call to getPermissionResolver()->getCurrentUserReference(), except this very test
        // This is because PermissionResolver is called from BookmarkQueryBuilder, not from BookmarkService when loading bookmarks
        // As it is defined in setup() that getCurrentUserReference needs to be called at least once, we'll force a call here
        $repository->getPermissionResolver()->getCurrentUserReference();

        $bookmarks = $this->createBookmarkService()->loadBookmarks($offset, $limit);

        self::assertEquals($expectedTotalCount, $bookmarks->totalCount);
        self::assertEquals($expectedItems, $bookmarks->items);
    }

    public function testLocationShouldNotBeBookmarked()
    {
        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([]);

        self::assertFalse($this->createBookmarkService()->isBookmarked($this->createLocation(self::LOCATION_ID)));
    }

    public function testLocationShouldBeBookmarked()
    {
        $this->bookmarkHandler
            ->expects(self::once())
            ->method('loadByUserIdAndLocationId')
            ->with(self::CURRENT_USER_ID, [self::LOCATION_ID])
            ->willReturn([self::LOCATION_ID => new Bookmark()]);

        self::assertTrue($this->createBookmarkService()->isBookmarked($this->createLocation(self::LOCATION_ID)));
    }

    private function assertLocationIsLoaded(Location $location): MockObject
    {
        $locationServiceMock = $this->createMock(LocationService::class);
        $locationServiceMock
            ->expects(self::once())
            ->method('loadLocation')
            ->willReturn($location);

        $repository = $this->getRepositoryMock();
        $repository
            ->expects(self::once())
            ->method('getLocationService')
            ->willReturn($locationServiceMock);

        return $locationServiceMock;
    }

    private function assertTransactionIsNotStarted(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::never())->method('beginTransaction');
        $operation();
        $repository->expects(self::never())->method('commit');
        $repository->expects(self::never())->method('rollback');
    }

    private function assertTransactionIsCommitted(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::once())->method('beginTransaction');
        $operation();
        $repository->expects(self::once())->method('commit');
        $repository->expects(self::never())->method('rollback');
    }

    private function assertTransactionIsRollback(callable $operation): void
    {
        $repository = $this->getRepositoryMock();
        $repository->expects(self::once())->method('beginTransaction');
        $operation();
        $repository->expects(self::never())->method('commit');
        $repository->expects(self::once())->method('rollback');
    }

    private function createLocation(int $id = self::CURRENT_USER_ID, string $name = 'Lorem ipsum...'): Location
    {
        return new Location([
            'id' => $id,
            'contentInfo' => new ContentInfo([
                'name' => $name,
            ]),
        ]);
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\BookmarkService|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createBookmarkService(?array $methods = null)
    {
        return $this
            ->getMockBuilder(BookmarkService::class)
            ->setConstructorArgs([$this->getRepositoryMock(), $this->bookmarkHandler])
            ->onlyMethods(array_values($methods ?? []))
            ->getMock();
    }
}
