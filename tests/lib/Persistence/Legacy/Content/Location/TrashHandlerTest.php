<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Location;

use Ibexa\Contracts\Core\Persistence\Content\Location\Trash\TrashResult;
use Ibexa\Contracts\Core\Persistence\Content\Location\Trashed;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Trash\TrashItemDeleteResultList;
use Ibexa\Core\Persistence\Legacy\Content as CoreContent;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\Location\Trash\Handler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Trash\Handler
 */
class TrashHandlerTest extends TestCase
{
    /**
     * Mocked location handler instance.
     *
     * @var CoreContent\Location\Handler
     */
    protected $locationHandler;

    /**
     * Mocked location gateway instance.
     *
     * @var Gateway
     */
    protected $locationGateway;

    /**
     * Mocked location mapper instance.
     *
     * @var Mapper
     */
    protected $locationMapper;

    /**
     * Mocked content handler instance.
     *
     * @var MockObject
     */
    protected $contentHandler;

    protected function getTrashHandler()
    {
        return new Handler(
            $this->locationHandler = $this->createMock(CoreContent\Location\Handler::class),
            $this->locationGateway = $this->createMock(Gateway::class),
            $this->locationMapper = $this->createMock(Mapper::class),
            $this->contentHandler = $this->createMock(CoreContent\Handler::class)
        );
    }

    public function testTrashSubtree()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturn([
                [
                    'contentobject_id' => 10,
                    'node_id' => 20,
                    'main_node_id' => 30,
                    'parent_node_id' => 40,
                ],
                [
                    'contentobject_id' => 11,
                    'node_id' => 21,
                    'main_node_id' => 31,
                    'parent_node_id' => 41,
                ],
            ]);

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    10 => 1,
                    11 => 2,
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $this->locationGateway
            ->expects(self::once())
            ->method('trashLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::once())
            ->method('removeLocation')
            ->with(21);

        $this->locationGateway
            ->expects(self::once())
            ->method('loadTrashByLocation')
            ->with(20)
            ->willReturn($array = ['data...']);

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->willReturn(new Trashed(['id' => 20]));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);
    }

    public function testTrashSubtreeReturnsNull()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturn([
                [
                    'contentobject_id' => 10,
                    'node_id' => 20,
                    'main_node_id' => 30,
                    'parent_node_id' => 40,
                ],
                [
                    'contentobject_id' => 11,
                    'node_id' => 21,
                    'main_node_id' => 31,
                    'parent_node_id' => 41,
                ],
            ]);

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    10 => 2,
                    11 => 1,
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $this->locationGateway
            ->expects(self::once())
            ->method('removeLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::once())
            ->method('trashLocation')
            ->with(21);

        $returnValue = $handler->trashSubtree(20);
        self::assertNull($returnValue);
    }

    public function testTrashSubtreeUpdatesMainLocation()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturn([
                [
                    'contentobject_id' => 10,
                    'node_id' => 20,
                    'main_node_id' => 30,
                    'parent_node_id' => 40,
                ],
                [
                    'contentobject_id' => 11,
                    'node_id' => 21,
                    'main_node_id' => 21,
                    'parent_node_id' => 41,
                ],
            ]);

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    10 => 1,
                    11 => 2,
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $this->locationGateway
            ->expects(self::once())
            ->method('trashLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::once())
            ->method('removeLocation')
            ->with(21);

        $this->locationGateway
            ->expects(self::once())
            ->method('getFallbackMainNodeData')
            ->with(11, 21)
            ->willReturn([
                'node_id' => 100,
                'contentobject_version' => 101,
                'parent_node_id' => 102,
            ]);

        $this->locationHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(11, 100);

        $this->locationGateway
            ->expects(self::once())
            ->method('loadTrashByLocation')
            ->with(20)
            ->willReturn($array = ['data...']);

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->willReturn(new Trashed(['id' => 20]));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);
    }

    public function testRecover()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('untrashLocation')
            ->with(69, 23)
            ->willReturn(new Trashed(['id' => 70]));

        self::assertSame(70, $handler->recover(69, 23));
    }

    public function testLoadTrashItem()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('loadTrashByLocation')
            ->with(69)
            ->willReturn($array = ['data...']);

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed());

        $handler->loadTrashItem(69);
    }

    public function testEmptyTrash(): void
    {
        $handler = $this->getTrashHandler();

        $expectedTrashed = [
            [
                'node_id' => 69,
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
            ],
            [
                'node_id' => 70,
                'path_string' => '/1/2/70/',
                'contentobject_id' => 68,
            ],
        ];

        $this->locationGateway
            ->expects(self::once())
            ->method('countTrashed')
            ->willReturn(2);

        $this->locationGateway
            ->expects(self::once())
            ->method('listTrashed')
            ->willReturn($expectedTrashed);

        $trashedItemIds = [];
        $trashedContentIds = [];

        foreach ($expectedTrashed as $trashedElement) {
            $trashedItemIds[] = $trashedElement['node_id'];
            $trashedContentIds[] = $trashedElement['contentobject_id'];
        }

        $this->locationMapper
            ->expects(self::exactly(2))
            ->method('createLocationFromRow')
            ->willReturnCallback(static function ($row) {
                return new Trashed([
                    'id' => $row['node_id'],
                    'contentId' => $row['contentobject_id'],
                    'pathString' => $row['path_string'],
                ]);
            });

        $this->contentHandler
            ->expects(self::exactly(2))
            ->method('loadReverseRelations')
            ->willReturnCallback(static function ($contentId) use ($trashedContentIds) {
                self::assertContains($contentId, $trashedContentIds);

                return [];
            });

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('removeElementFromTrash')
            ->willReturnCallback(static function ($nodeId) use ($trashedItemIds) {
                self::assertContains($nodeId, $trashedItemIds);
            });

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function ($contentId) use ($trashedContentIds) {
                self::assertContains($contentId, $trashedContentIds);

                return 0;
            });

        $this->contentHandler
            ->expects(self::exactly(2))
            ->method('deleteContent')
            ->willReturnCallback(static function ($contentId) use ($trashedContentIds) {
                self::assertContains($contentId, $trashedContentIds);
            });

        $returnValue = $handler->emptyTrash();

        self::assertInstanceOf(TrashItemDeleteResultList::class, $returnValue);

        foreach ($returnValue->items as $key => $trashItemDeleteResult) {
            self::assertEquals($trashItemDeleteResult->trashItemId, $trashedItemIds[$key]);
            self::assertEquals($trashItemDeleteResult->contentId, $trashedContentIds[$key]);
            self::assertTrue($trashItemDeleteResult->contentRemoved);
        }
    }

    public function testDeleteTrashItemNoMoreLocations(): void
    {
        $handler = $this->getTrashHandler();

        $trashItemId = 69;
        $contentId = 67;
        $this->locationGateway
            ->expects(self::once())
            ->method('loadTrashByLocation')
            ->with($trashItemId)
            ->willReturn(
                [
                    'node_id' => $trashItemId,
                    'contentobject_id' => $contentId,
                    'path_string' => '/1/2/69',
                ]
            );

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->willReturn(
                new Trashed(
                    [
                        'id' => $trashItemId,
                        'contentId' => $contentId,
                        'pathString' => '/1/2/69',
                    ]
                )
            );

        $this->contentHandler
            ->expects(self::once())
            ->method('loadReverseRelations')
            ->with($contentId)
            ->willReturn([]);

        $this->locationGateway
            ->expects(self::once())
            ->method('removeElementFromTrash')
            ->with($trashItemId);

        $this->locationGateway
            ->expects(self::once())
            ->method('countLocationsByContentId')
            ->with($contentId)
            ->willReturn(0);

        $this->contentHandler
            ->expects(self::once())
            ->method('deleteContent')
            ->with($contentId);

        $trashItemDeleteResult = $handler->deleteTrashItem($trashItemId);

        self::assertInstanceOf(TrashItemDeleteResult::class, $trashItemDeleteResult);
        self::assertEquals($trashItemId, $trashItemDeleteResult->trashItemId);
        self::assertEquals($contentId, $trashItemDeleteResult->contentId);
        self::assertTrue($trashItemDeleteResult->contentRemoved);
    }

    public function testDeleteTrashItemStillHaveLocations()
    {
        $handler = $this->getTrashHandler();

        $trashItemId = 69;
        $contentId = 67;
        $this->locationGateway
            ->expects(self::once())
            ->method('loadTrashByLocation')
            ->with($trashItemId)
            ->will(
                self::returnValue(
                    [
                        'node_id' => $trashItemId,
                        'contentobject_id' => $contentId,
                        'path_string' => '/1/2/69',
                    ]
                )
            );

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->will(
                self::returnValue(
                    new Trashed(
                        [
                            'id' => $trashItemId,
                            'contentId' => $contentId,
                            'pathString' => '/1/2/69',
                        ]
                    )
                )
            );

        $this->locationGateway
            ->expects(self::once())
            ->method('removeElementFromTrash')
            ->with($trashItemId);

        $this->locationGateway
            ->expects(self::once())
            ->method('countLocationsByContentId')
            ->with($contentId)
            ->will(self::returnValue(1));

        $this->contentHandler
            ->expects(self::never())
            ->method('deleteContent');

        $trashItemDeleteResult = $handler->deleteTrashItem($trashItemId);

        self::assertInstanceOf(TrashItemDeleteResult::class, $trashItemDeleteResult);
        self::assertEquals($trashItemId, $trashItemDeleteResult->trashItemId);
        self::assertEquals($contentId, $trashItemDeleteResult->contentId);
        self::assertFalse($trashItemDeleteResult->contentRemoved);
    }

    public function testFindTrashItemsWhenEmpty()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('countTrashed')
            ->willReturn(0);

        $this->locationGateway
            ->expects(self::never())
            ->method('listTrashed');

        $this->locationMapper
            ->expects(self::never())
            ->method(self::anything());

        $trashResult = $handler->findTrashItems();

        self::assertInstanceOf(TrashResult::class, $trashResult);
        self::assertEquals(0, $trashResult->totalCount);
        self::assertIsArray($trashResult->items);
        self::assertEmpty($trashResult->items);
        self::assertIsIterable($trashResult);
        self::assertCount(0, $trashResult);// Can't assert as empty, however we can count it.
    }

    public function testFindTrashItemsWithLimits()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('countTrashed')
            ->willReturn(2);

        $this->locationGateway
            ->expects(self::once())
            ->method('listTrashed')
            ->with(1, 1, null)
            ->willReturn([['fake data']]);

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with(['fake data'])
            ->willReturn(new \stdClass());

        $trashResult = $handler->findTrashItems(null, 1, 1);

        self::assertInstanceOf(TrashResult::class, $trashResult);
        self::assertEquals(2, $trashResult->totalCount);
        self::assertIsArray($trashResult->items);
        self::assertCount(1, $trashResult->items);
        self::assertIsIterable($trashResult);
        self::assertCount(1, $trashResult);
    }
}
