<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Legacy;

use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Bookmark\Handler as BookmarkHandler;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Bookmark\Handler
 */
final class BookmarkHandlerTest extends RepositoryTestCase
{
    private Handler $handler;

    private BookmarkHandler $bookmarkHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->handler = self::getServiceByClassName(Handler::class);
        $this->bookmarkHandler = $this->handler->bookmarkHandler();
    }

    public function testLoadUserIdsByLocation(): void
    {
        $content = $this->createTestContent();
        $locationId = $content->getContentInfo()->getMainLocationId();

        self::assertNotEmpty($locationId);

        $location = $this->handler->locationHandler()->load($locationId);

        // Location is not bookmarked yet
        self::assertSameArrayWithUserIds([], $location);

        $bookmark = $this->addToBookmark($location);

        self::assertSameArrayWithUserIds([self::ADMIN_USER_ID], $location);

        $this->deleteBookmark($bookmark->id);

        // Check if location has been removed from bookmarks
        self::assertSameArrayWithUserIds([], $location);
    }

    /**
     * @param array<int> $expected
     */
    private function assertSameArrayWithUserIds(
        array $expected,
        Location $location
    ): void {
        self::assertSame(
            $expected,
            $this->bookmarkHandler->loadUserIdsByLocation($location)
        );
    }

    private function createTestContent(): Content
    {
        return $this->createFolder(
            ['eng-GB' => 'Foo']
        );
    }

    private function addToBookmark(Location $location): Bookmark
    {
        $createStruct = new CreateStruct();
        $createStruct->userId = self::ADMIN_USER_ID;
        $createStruct->locationId = $location->id;

        return $this->bookmarkHandler->create($createStruct);
    }

    private function deleteBookmark(int $bookmarkId): void
    {
        $this->bookmarkHandler->delete($bookmarkId);
    }
}
