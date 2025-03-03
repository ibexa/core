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
use Ibexa\Core\Persistence\Legacy\Content\Location\Trash\Handler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Trash\Handler
 */
class TrashHandlerTest extends TestCase
{
    /**
     * Mocked location handler instance.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Handler
     */
    protected $locationHandler;

    /**
     * Mocked location gateway instance.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway
     */
    protected $locationGateway;

    /**
     * Mocked location mapper instance.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Location\Mapper
     */
    protected $locationMapper;

    /**
     * Mocked content handler instance.
     *
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $contentHandler;

    protected function getTrashHandler()
    {
        return new Handler(
            $this->locationHandler = $this->createMock(CoreContent\Location\Handler::class),
            $this->locationGateway = $this->createMock(CoreContent\Location\Gateway::class),
            $this->locationMapper = $this->createMock(CoreContent\Location\Mapper::class),
            $this->contentHandler = $this->createMock(CoreContent\Handler::class)
        );
    }

    public function testTrashSubtree()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::at(0))
            ->method('getSubtreeContent')
            ->with(20)
            ->will(
                self::returnValue(
                    [
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
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::at(1))
            ->method('countLocationsByContentId')
            ->with(10)
            ->will(self::returnValue(1));

        $this->locationGateway
            ->expects(self::at(2))
            ->method('trashLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::at(3))
            ->method('countLocationsByContentId')
            ->with(11)
            ->will(self::returnValue(2));

        $this->locationGateway
            ->expects(self::at(4))
            ->method('removeLocation')
            ->with(21);

        $this->locationGateway
            ->expects(self::at(5))
            ->method('loadTrashByLocation')
            ->with(20)
            ->will(self::returnValue($array = ['data…']));

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->will(self::returnValue(new Trashed(['id' => 20])));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);
    }

    public function testTrashSubtreeReturnsNull()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::at(0))
            ->method('getSubtreeContent')
            ->with(20)
            ->will(
                self::returnValue(
                    [
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
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::at(1))
            ->method('countLocationsByContentId')
            ->with(10)
            ->will(self::returnValue(2));

        $this->locationGateway
            ->expects(self::at(2))
            ->method('removeLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::at(3))
            ->method('countLocationsByContentId')
            ->with(11)
            ->will(self::returnValue(1));

        $this->locationGateway
            ->expects(self::at(4))
            ->method('trashLocation')
            ->with(21);

        $returnValue = $handler->trashSubtree(20);
        self::assertNull($returnValue);
    }

    public function testTrashSubtreeUpdatesMainLocation()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::at(0))
            ->method('getSubtreeContent')
            ->with(20)
            ->will(
                self::returnValue(
                    [
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
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::at(1))
            ->method('countLocationsByContentId')
            ->with(10)
            ->will(self::returnValue(1));

        $this->locationGateway
            ->expects(self::at(2))
            ->method('trashLocation')
            ->with(20);

        $this->locationGateway
            ->expects(self::at(3))
            ->method('countLocationsByContentId')
            ->with(11)
            ->will(self::returnValue(2));

        $this->locationGateway
            ->expects(self::at(4))
            ->method('removeLocation')
            ->with(21);

        $this->locationGateway
            ->expects(self::at(5))
            ->method('getFallbackMainNodeData')
            ->with(11, 21)
            ->will(
                self::returnValue(
                    [
                        'node_id' => 100,
                        'contentobject_version' => 101,
                        'parent_node_id' => 102,
                    ]
                )
            );

        $this->locationHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(11, 100);

        $this->locationGateway
            ->expects(self::at(6))
            ->method('loadTrashByLocation')
            ->with(20)
            ->will(self::returnValue($array = ['data…']));

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->will(self::returnValue(new Trashed(['id' => 20])));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);
    }

    public function testRecover()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::at(0))
            ->method('untrashLocation')
            ->with(69, 23)
            ->will(
                self::returnValue(
                    new Trashed(['id' => 70])
                )
            );

        self::assertSame(70, $handler->recover(69, 23));
    }

    public function testLoadTrashItem()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects(self::at(0))
            ->method('loadTrashByLocation')
            ->with(69)
            ->will(self::returnValue($array = ['data…']));

        $this->locationMapper
            ->expects(self::at(0))
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

        // Index for locationGateway calls
        $i = 0;
        // Index for contentHandler calls
        $iContent = 0;
        // Index for locationMapper calls
        $iLocation = 0;

        $this->locationGateway
            ->expects(self::at($i++))
            ->method('countTrashed')
            ->willReturn(2);

        $this->locationGateway
            ->expects(self::at($i++))
            ->method('listTrashed')
            ->willReturn($expectedTrashed);

        $trashedItemIds = [];
        $trashedContentIds = [];

        foreach ($expectedTrashed as $trashedElement) {
            $this->locationMapper
                ->expects(self::at($iLocation++))
                ->method('createLocationFromRow')
                ->willReturn(
                    new Trashed(
                        [
                            'id' => $trashedElement['node_id'],
                            'contentId' => $trashedElement['contentobject_id'],
                            'pathString' => $trashedElement['path_string'],
                        ]
                    )
                );

            $this->contentHandler
                ->expects(self::at($iContent++))
                ->method('loadReverseRelations')
                ->with($trashedElement['contentobject_id'])
                ->willReturn([]);

            $this->locationGateway
                ->expects(self::at($i++))
                ->method('removeElementFromTrash')
                ->with($trashedElement['node_id']);

            $this->locationGateway
                ->expects(self::at($i++))
                ->method('countLocationsByContentId')
                ->with($trashedElement['contentobject_id'])
                ->willReturn(0);

            $this->contentHandler
                ->expects(self::at($iContent++))
                ->method('deleteContent')
                ->with($trashedElement['contentobject_id']);

            $trashedItemIds[] = $trashedElement['node_id'];
            $trashedContentIds[] = $trashedElement['contentobject_id'];
        }

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
