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

    /**
     * Records the call, keeping only the first $recordedArguments arguments (the ones the test cares about).
     *
     * @param list<mixed> $calls
     * @param mixed $return
     */
    private static function recorder(
        array &$calls,
        string $method,
        $return,
        int $recordedArguments = PHP_INT_MAX
    ): \Closure {
        return static function (...$arguments) use (&$calls, $method, $return, $recordedArguments) {
            $calls[] = [$method, ...array_values(array_slice($arguments, 0, $recordedArguments))];

            return $return;
        };
    }

    /**
     * @param list<mixed> $calls
     * @param array<int, mixed> $returnMap return value keyed by the first argument
     */
    private static function mapRecorder(
        array &$calls,
        string $method,
        array $returnMap
    ): \Closure {
        return static function (...$arguments) use (&$calls, $method, $returnMap) {
            $calls[] = [$method, ...array_values($arguments)];

            return $returnMap[$arguments[0]];
        };
    }

    public function testTrashSubtree()
    {
        $handler = $this->getTrashHandler();
        $calls = [];

        $this->locationGateway
            ->expects($this->once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturnCallback(
                self::recorder(
                    $calls,
                    'getSubtreeContent',
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
                    ],
                    1
                )
            );

        $this->locationGateway
            ->expects($this->exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(self::mapRecorder($calls, 'countLocationsByContentId', [10 => 1, 11 => 2]));

        $this->locationGateway
            ->expects($this->once())
            ->method('trashLocation')
            ->with(20)
            ->willReturnCallback(self::recorder($calls, 'trashLocation', null));

        $this->locationGateway
            ->expects($this->once())
            ->method('removeLocation')
            ->with(21)
            ->willReturnCallback(self::recorder($calls, 'removeLocation', null));

        $this->locationHandler
            ->expects($this->once())
            ->method('markSubtreeModified')
            ->with(40);

        $this->locationGateway
            ->expects($this->once())
            ->method('loadTrashByLocation')
            ->with(20)
            ->willReturnCallback(self::recorder($calls, 'loadTrashByLocation', $array = ['data…']));

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->will($this->returnValue(new Trashed(['id' => 20])));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);

        self::assertSame(
            [
                ['getSubtreeContent', 20],
                ['countLocationsByContentId', 10],
                ['trashLocation', 20],
                ['countLocationsByContentId', 11],
                ['removeLocation', 21],
                ['loadTrashByLocation', 20],
            ],
            $calls
        );
    }

    public function testTrashSubtreeReturnsNull()
    {
        $handler = $this->getTrashHandler();
        $calls = [];

        $this->locationGateway
            ->expects($this->once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturnCallback(
                self::recorder(
                    $calls,
                    'getSubtreeContent',
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
                    ],
                    1
                )
            );

        $this->locationGateway
            ->expects($this->exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(self::mapRecorder($calls, 'countLocationsByContentId', [10 => 2, 11 => 1]));

        $this->locationGateway
            ->expects($this->once())
            ->method('removeLocation')
            ->with(20)
            ->willReturnCallback(self::recorder($calls, 'removeLocation', null));

        $this->locationGateway
            ->expects($this->once())
            ->method('trashLocation')
            ->with(21)
            ->willReturnCallback(self::recorder($calls, 'trashLocation', null));

        $this->locationHandler
            ->expects($this->once())
            ->method('markSubtreeModified')
            ->with(40);

        $returnValue = $handler->trashSubtree(20);
        self::assertNull($returnValue);
        self::assertSame(
            [
                ['getSubtreeContent', 20],
                ['countLocationsByContentId', 10],
                ['removeLocation', 20],
                ['countLocationsByContentId', 11],
                ['trashLocation', 21],
            ],
            $calls
        );
    }

    public function testTrashSubtreeUpdatesMainLocation()
    {
        $handler = $this->getTrashHandler();
        $calls = [];

        $this->locationGateway
            ->expects($this->once())
            ->method('getSubtreeContent')
            ->with(20)
            ->willReturnCallback(
                self::recorder(
                    $calls,
                    'getSubtreeContent',
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
                    ],
                    1
                )
            );

        $this->locationGateway
            ->expects($this->exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(self::mapRecorder($calls, 'countLocationsByContentId', [10 => 1, 11 => 2]));

        $this->locationGateway
            ->expects($this->once())
            ->method('trashLocation')
            ->with(20)
            ->willReturnCallback(self::recorder($calls, 'trashLocation', null));

        $this->locationGateway
            ->expects($this->once())
            ->method('removeLocation')
            ->with(21)
            ->willReturnCallback(self::recorder($calls, 'removeLocation', null));

        $this->locationGateway
            ->expects($this->once())
            ->method('getFallbackMainNodeData')
            ->with(11, 21)
            ->willReturnCallback(
                self::recorder(
                    $calls,
                    'getFallbackMainNodeData',
                    [
                        'node_id' => 100,
                        'contentobject_version' => 101,
                        'parent_node_id' => 102,
                    ]
                )
            );

        $this->locationHandler
            ->expects($this->once())
            ->method('changeMainLocation')
            ->with(11, 100, 101, 102);

        $this->locationHandler
            ->expects($this->once())
            ->method('markSubtreeModified')
            ->with(40);

        $this->locationGateway
            ->expects($this->once())
            ->method('loadTrashByLocation')
            ->with(20)
            ->willReturnCallback(self::recorder($calls, 'loadTrashByLocation', $array = ['data…']));

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->with($array, null, new Trashed())
            ->will($this->returnValue(new Trashed(['id' => 20])));

        $trashedObject = $handler->trashSubtree(20);
        self::assertInstanceOf(Trashed::class, $trashedObject);
        self::assertSame(20, $trashedObject->id);

        self::assertSame(
            [
                ['getSubtreeContent', 20],
                ['countLocationsByContentId', 10],
                ['trashLocation', 20],
                ['countLocationsByContentId', 11],
                ['removeLocation', 21],
                ['getFallbackMainNodeData', 11, 21],
                ['loadTrashByLocation', 20],
            ],
            $calls
        );
    }

    public function testRecover()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('untrashLocation')
            ->with(69, 23)
            ->will(
                $this->returnValue(
                    new Trashed(['id' => 70])
                )
            );

        self::assertSame(70, $handler->recover(69, 23));
    }

    public function testLoadTrashItem()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('loadTrashByLocation')
            ->with(69)
            ->will($this->returnValue($array = ['data…']));

        $this->locationMapper
            ->expects($this->once())
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

        $locationCalls = [];
        $contentCalls = [];

        $this->locationGateway
            ->expects(self::once())
            ->method('countTrashed')
            ->willReturnCallback(self::recorder($locationCalls, 'countTrashed', 2, 0));

        $this->locationGateway
            ->expects(self::once())
            ->method('listTrashed')
            ->willReturnCallback(self::recorder($locationCalls, 'listTrashed', $expectedTrashed, 0));

        $trashedItemIds = array_column($expectedTrashed, 'node_id');
        $trashedContentIds = array_column($expectedTrashed, 'contentobject_id');

        $this->locationMapper
            ->expects(self::exactly(2))
            ->method('createLocationFromRow')
            ->willReturnOnConsecutiveCalls(
                ...array_map(
                    static function (array $trashedElement): Trashed {
                        return new Trashed(
                            [
                                'id' => $trashedElement['node_id'],
                                'contentId' => $trashedElement['contentobject_id'],
                                'pathString' => $trashedElement['path_string'],
                            ]
                        );
                    },
                    $expectedTrashed
                )
            );

        $this->contentHandler
            ->expects(self::exactly(2))
            ->method('loadReverseRelations')
            ->willReturnCallback(self::recorder($contentCalls, 'loadReverseRelations', [], 1));

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('removeElementFromTrash')
            ->willReturnCallback(self::recorder($locationCalls, 'removeElementFromTrash', null));

        $this->locationGateway
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(self::recorder($locationCalls, 'countLocationsByContentId', 0));

        $this->contentHandler
            ->expects(self::exactly(2))
            ->method('deleteContent')
            ->willReturnCallback(self::recorder($contentCalls, 'deleteContent', null));

        $returnValue = $handler->emptyTrash();

        self::assertSame(
            [
                ['countTrashed'],
                ['listTrashed'],
                ['removeElementFromTrash', 69],
                ['countLocationsByContentId', 67],
                ['removeElementFromTrash', 70],
                ['countLocationsByContentId', 68],
            ],
            $locationCalls
        );
        self::assertSame(
            [
                ['loadReverseRelations', 67],
                ['deleteContent', 67],
                ['loadReverseRelations', 68],
                ['deleteContent', 68],
            ],
            $contentCalls
        );

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
            ->expects($this->once())
            ->method('loadTrashByLocation')
            ->with($trashItemId)
            ->will(
                $this->returnValue(
                    [
                        'node_id' => $trashItemId,
                        'contentobject_id' => $contentId,
                        'path_string' => '/1/2/69',
                    ]
                )
            );

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->will(
                $this->returnValue(
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
            ->expects($this->once())
            ->method('removeElementFromTrash')
            ->with($trashItemId);

        $this->locationGateway
            ->expects($this->once())
            ->method('countLocationsByContentId')
            ->with($contentId)
            ->will($this->returnValue(1));

        $this->contentHandler
            ->expects($this->never())
            ->method('deleteContent');

        $trashItemDeleteResult = $handler->deleteTrashItem($trashItemId);

        $this->assertInstanceOf(TrashItemDeleteResult::class, $trashItemDeleteResult);
        $this->assertEquals($trashItemId, $trashItemDeleteResult->trashItemId);
        $this->assertEquals($contentId, $trashItemDeleteResult->contentId);
        $this->assertFalse($trashItemDeleteResult->contentRemoved);
    }

    public function testFindTrashItemsWhenEmpty()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('countTrashed')
            ->willReturn(0);

        $this->locationGateway
            ->expects($this->never())
            ->method('listTrashed');

        $this->locationMapper
            ->expects($this->never())
            ->method($this->anything());

        $trashResult = $handler->findTrashItems();

        $this->assertInstanceOf(TrashResult::class, $trashResult);
        $this->assertEquals(0, $trashResult->totalCount);
        $this->assertIsArray($trashResult->items);
        $this->assertEmpty($trashResult->items);
        $this->assertIsIterable($trashResult);
        $this->assertCount(0, $trashResult);// Can't assert as empty, however we can count it.
    }

    public function testFindTrashItemsWithLimits()
    {
        $handler = $this->getTrashHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('countTrashed')
            ->willReturn(2);

        $this->locationGateway
            ->expects($this->once())
            ->method('listTrashed')
            ->with(1, 1, null)
            ->willReturn([['fake data']]);

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->with(['fake data'])
            ->willReturn(new \stdClass());

        $trashResult = $handler->findTrashItems(null, 1, 1);

        $this->assertInstanceOf(TrashResult::class, $trashResult);
        $this->assertEquals(2, $trashResult->totalCount);
        $this->assertIsArray($trashResult->items);
        $this->assertCount(1, $trashResult->items);
        $this->assertIsIterable($trashResult);
        $this->assertCount(1, $trashResult);
    }
}

class_alias(TrashHandlerTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\Location\TrashHandlerTest');
