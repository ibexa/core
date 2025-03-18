<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Bookmark;

use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Bookmark\CreateStruct;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;
use Ibexa\Core\Persistence\Legacy\Bookmark\Handler;
use Ibexa\Core\Persistence\Legacy\Bookmark\Mapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HandlerTest extends TestCase
{
    public const BOOKMARK_ID = 7;

    /** @var \Ibexa\Core\Persistence\Legacy\Bookmark\Gateway|\PHPUnit\Framework\MockObject\MockObject */
    private MockObject $gateway;

    /** @var \Ibexa\Core\Persistence\Legacy\Bookmark\Mapper|\PHPUnit\Framework\MockObject\MockObject */
    private MockObject $mapper;

    /** @var \Ibexa\Core\Persistence\Legacy\Bookmark\Handler */
    private Handler $handler;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->handler = new Handler($this->gateway, $this->mapper);
    }

    public function testCreate(): void
    {
        $createStruct = new CreateStruct([
            'locationId' => 54,
            'userId' => 87,
        ]);

        $bookmark = new Bookmark([
            'locationId' => 54,
            'userId' => 87,
        ]);

        $this->mapper
            ->expects(self::once())
            ->method('createBookmarkFromCreateStruct')
            ->with($createStruct)
            ->willReturn($bookmark);

        $this->gateway
            ->expects(self::once())
            ->method('insertBookmark')
            ->with($bookmark)
            ->willReturn(self::BOOKMARK_ID);

        $this->handler->create($createStruct);

        self::assertEquals($bookmark->id, self::BOOKMARK_ID);
    }

    public function testDelete(): void
    {
        $this->gateway
            ->expects(self::once())
            ->method('deleteBookmark')
            ->with(self::BOOKMARK_ID);

        $this->handler->delete(self::BOOKMARK_ID);
    }

    public function testLoadByUserIdAndLocationIdExistingBookmark(): void
    {
        $userId = 87;
        $locationId = 54;

        $rows = [
            [
                'name' => 'Contact',
                'node_id' => $locationId,
                'user_id' => $userId,
            ],
        ];

        $object = new Bookmark([
            'locationId' => $locationId,
            'userId' => $userId,
        ]);

        $this->gateway
            ->expects(self::once())
            ->method('loadBookmarkDataByUserIdAndLocationId')
            ->with($userId, [$locationId])
            ->willReturn($rows);

        $this->mapper
            ->expects(self::once())
            ->method('extractBookmarksFromRows')
            ->with($rows)
            ->willReturn([$object]);

        self::assertEquals([$locationId => $object], $this->handler->loadByUserIdAndLocationId($userId, [$locationId]));
    }

    public function testLoadByUserIdAndLocationIdNonExistingBookmark(): void
    {
        $userId = 87;
        $locationId = 54;

        $this->gateway
            ->expects(self::once())
            ->method('loadBookmarkDataByUserIdAndLocationId')
            ->with($userId, [$locationId])
            ->willReturn([]);

        $this->mapper
            ->expects(self::once())
            ->method('extractBookmarksFromRows')
            ->with([])
            ->willReturn([]);

        self::assertEmpty($this->handler->loadByUserIdAndLocationId($userId, [$locationId]));
    }

    public function testLoadUserBookmarks(): void
    {
        $userId = 87;
        $offset = 50;
        $limit = 25;

        $rows = [
            [
                'id' => '12',
                'name' => '',
                'node_id' => '2',
                'user_id' => $userId,
            ],
            [
                'id' => '75',
                'name' => '',
                'node_id' => '54',
                'user_id' => $userId,
            ],
        ];

        $objects = [
            new Bookmark([
                'id' => 12,
                'locationId' => 2,
                'userId' => 78,
            ]),
            new Bookmark([
                'id' => 75,
                'locationId' => 54,
                'userId' => 87,
            ]),
        ];

        $this->gateway
            ->expects(self::once())
            ->method('loadUserBookmarks')
            ->with($userId, $offset, $limit)
            ->willReturn($rows);

        $this->mapper
            ->expects(self::once())
            ->method('extractBookmarksFromRows')
            ->with($rows)
            ->willReturn($objects);

        self::assertEquals($objects, $this->handler->loadUserBookmarks($userId, $offset, $limit));
    }

    public function testLocationSwapped(): void
    {
        $location1Id = 1;
        $location2Id = 2;

        $this->gateway
            ->expects(self::once())
            ->method('locationSwapped')
            ->with($location1Id, $location2Id);

        $this->handler->locationSwapped($location1Id, $location2Id);
    }
}
