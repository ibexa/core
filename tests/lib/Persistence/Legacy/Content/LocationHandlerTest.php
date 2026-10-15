<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\Location\UpdateStruct;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState;
use Ibexa\Contracts\Core\Persistence\Content\ObjectState\Group as ObjectStateGroup;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\Handler as ContentHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Handler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Handler as LocationHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler as ObjectStateHandler;
use Ibexa\Core\Persistence\Legacy\Content\TreeHandler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\Constraint\Constraint;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Handler
 */
class LocationHandlerTest extends TestCase
{
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
     * @var ContentHandler
     */
    protected $contentHandler;

    /**
     * Mocked object state handler instance.
     *
     * @var ObjectStateHandler|MockObject
     */
    protected $objectStateHandler;

    /**
     * Mocked Tree handler instance.
     *
     * @var TreeHandler|MockObject
     */
    protected $treeHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locationGateway = $this->createMock(Gateway::class);
        $this->locationMapper = $this->createMock(Mapper::class);
        $this->treeHandler = $this->createMock(TreeHandler::class);
        $this->contentHandler = $this->createMock(ContentHandler::class);
    }

    protected function getLocationHandler()
    {
        return new Handler(
            $this->locationGateway,
            $this->locationMapper,
            $this->contentHandler,
            $this->createMock(ObjectStateHandler::class),
            $this->treeHandler
        );
    }

    public function testLoadLocation()
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects($this->once())
            ->method('loadLocation')
            ->with(77)
            ->willReturn(new Location());

        $location = $handler->load(77);

        self::assertInstanceOf(Location::class, $location);
    }

    public function testLoadLocationSubtree()
    {
        $this->locationGateway
            ->expects($this->once())
            ->method('getSubtreeContent')
            ->with(77, true)
            ->will(
                $this->returnValue(
                    [
                        [77 => 100],
                        [78 => 101],
                    ]
                )
            );

        $this->assertCount(2, $this->getLocationHandler()->loadSubtreeIds(77));
    }

    public function testLoadLocationByRemoteId()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeDataByRemoteId')
            ->with('abc123')
            ->willReturn(
                [
                    'node_id' => 77,
                ]
            );

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->with(['node_id' => 77])
            ->willReturn(new Location());

        $location = $handler->loadByRemoteId('abc123');

        self::assertInstanceOf(Location::class, $location);
    }

    public function testLoadLocationsByContent()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('loadLocationDataByContent')
            ->with(23, 42)
            ->will(
                $this->returnValue(
                    []
                )
            );

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationsFromRows')
            ->with([])
            ->will($this->returnValue(['a', 'b']));

        $locations = $handler->loadLocationsByContent(23, 42);

        $this->assertIsArray($locations);
    }

    public function loadParentLocationsForDraftContent()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('loadParentLocationsDataForDraftContent')
            ->with(23)
            ->will(
                $this->returnValue(
                    []
                )
            );

        $this->locationMapper
            ->expects($this->once())
            ->method('createLocationsFromRows')
            ->with([])
            ->will($this->returnValue(['a', 'b']));

        $locations = $handler->loadParentLocationsForDraftContent(23);

        $this->assertIsArray($locations);
    }

    public function testMoveSubtree()
    {
        $handler = $this->getLocationHandler();

        $sourceData = [
            'node_id' => 69,
            'path_string' => '/1/2/69/',
            'parent_node_id' => 2,
            'contentobject_id' => 67,
        ];
        $destinationData = [
            'node_id' => 77,
            'path_string' => '/1/2/77/',
            'contentobject_id' => 68,
        ];
        $this->locationGateway
            ->expects($this->exactly(2))
            ->method('getBasicNodeData')
            ->withConsecutive([69], [77])
            ->willReturnOnConsecutiveCalls($sourceData, $destinationData);

        $this->locationGateway
            ->expects($this->once())
            ->method('moveSubtreeNodes')
            ->with($sourceData, $destinationData);

        $this->locationGateway
            ->expects($this->once())
            ->method('updateNodeAssignment')
            ->with(67, 2, 77, 5);

        $this->treeHandler
            ->expects($this->exactly(2))
            ->method('loadLocation')
            ->withConsecutive([$sourceData['node_id']], [$destinationData['node_id']])
            ->willReturnOnConsecutiveCalls(
                new Location(
                    [
                        'id' => $sourceData['node_id'],
                        'contentId' => $sourceData['contentobject_id'],
                    ]
                ),
                new Location(['contentId' => $destinationData['contentobject_id']])
            );

        $this->contentHandler
            ->expects($this->exactly(2))
            ->method('loadContentInfo')
            ->withConsecutive([$destinationData['contentobject_id']], [$sourceData['contentobject_id']])
            ->willReturnOnConsecutiveCalls(
                new ContentInfo(['sectionId' => 12345]),
                new ContentInfo(['mainLocationId' => 69])
            );

        $this->treeHandler
            ->expects($this->once())
            ->method('setSectionForSubtree')
            ->with(69, 12345);

        $handler->move(69, 77);
    }

    public function testHideUpdateHidden()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with(69)
            ->will(
                $this->returnValue(
                    [
                        'node_id' => 69,
                        'path_string' => '/1/2/69/',
                        'contentobject_id' => 67,
                    ]
                )
            );

        $this->locationGateway
            ->expects($this->once())
            ->method('hideSubtree')
            ->with('/1/2/69/');

        $handler->hide(69);
    }

    /**
     * @depends testHideUpdateHidden
     */
    public function testHideUnhideUpdateHidden()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with(69)
            ->will(
                $this->returnValue(
                    [
                        'node_id' => 69,
                        'path_string' => '/1/2/69/',
                        'contentobject_id' => 67,
                    ]
                )
            );

        $this->locationGateway
            ->expects($this->once())
            ->method('unhideSubtree')
            ->with('/1/2/69/');

        $handler->unhide(69);
    }

    public function testSwapLocations()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects($this->once())
            ->method('swap')
            ->with(70, 78);

        $handler->swap(70, 78);
    }

    public function testCreateLocation()
    {
        $handler = $this->getLocationHandler();

        $createStruct = new CreateStruct();
        $createStruct->parentId = 77;
        $spiLocation = new Location();
        $spiLocation->id = 78;
        $spiLocation->parentId = 77;
        $spiLocation->pathString = '/1/2/77/78/';

        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with(77)
            ->will(
                $this->returnValue(
                    $parentInfo = [
                        'node_id' => 77,
                        'path_string' => '/1/2/77/',
                    ]
                )
            );

        $this->locationGateway
            ->expects($this->once())
            ->method('create')
            ->with($createStruct, $parentInfo)
            ->will($this->returnValue($spiLocation));

        $this->locationGateway
            ->expects($this->once())
            ->method('createNodeAssignment')
            ->with($createStruct, 77, 2);

        $handler->create($createStruct);
    }

    public function testUpdateLocation()
    {
        $handler = $this->getLocationHandler();

        $updateStruct = new UpdateStruct();
        $updateStruct->priority = 77;

        $this->locationGateway
            ->expects($this->once())
            ->method('update')
            ->with($updateStruct, 23);

        $handler->update($updateStruct, 23);
    }

    public function testSetSectionForSubtree()
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects($this->once())
            ->method('setSectionForSubtree')
            ->with(69, 3);

        $handler->setSectionForSubtree(69, 3);
    }

    public function testMarkSubtreeModified()
    {
        $handler = $this->getLocationHandler();
        $calls = [];

        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with(69)
            ->willReturnCallback(
                static function (int $nodeId) use (&$calls): array {
                    $calls[] = ['getBasicNodeData', $nodeId];

                    return [
                        'node_id' => 69,
                        'path_string' => '/1/2/69/',
                        'contentobject_id' => 67,
                    ];
                }
            );

        $this->locationGateway
            ->expects($this->once())
            ->method('updateSubtreeModificationTime')
            ->with('/1/2/69/')
            ->willReturnCallback(
                static function (string $path) use (&$calls): void {
                    $calls[] = ['updateSubtreeModificationTime', $path];
                }
            );

        $handler->markSubtreeModified(69);

        self::assertSame(
            [
                ['getBasicNodeData', 69],
                ['updateSubtreeModificationTime', '/1/2/69/'],
            ],
            $calls
        );
    }

    public function testChangeMainLocation()
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects($this->once())
            ->method('changeMainLocation')
            ->with(12, 34);

        $handler->changeMainLocation(12, 34);
    }

    /**
     * Test for the removeSubtree() method.
     */
    public function testRemoveSubtree()
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects($this->once())
            ->method('removeSubtree')
            ->with(42);

        $handler->removeSubtree(42);
    }

    public function testDeleteChildrenDrafts(): void
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
            ->method('deleteChildrenDrafts')
            ->with(42);

        $handler->deleteChildrenDrafts(42);
    }

    /**
     * Returns a callback verifying the arguments of consecutive calls of $method and returning the matching result.
     * Every call is appended to $calls, so the order between different methods of one mock can be asserted.
     *
     * @param list<string> $calls
     * @param array<int, array<int, mixed>> $expectedArguments per call, constraints or values compared with equalTo
     * @param array<int, mixed> $results per call
     */
    private static function sequence(
        array &$calls,
        string $method,
        array $expectedArguments,
        array $results = []
    ): \Closure {
        $callNo = 0;

        return static function (...$arguments) use (&$calls, &$callNo, $method, $expectedArguments, $results) {
            $calls[] = $method;
            foreach ($expectedArguments[$callNo] ?? [] as $position => $expected) {
                self::assertThat(
                    $arguments[$position],
                    $expected instanceof Constraint ? $expected : new IsEqual($expected)
                );
            }

            return $results[$callNo++] ?? null;
        };
    }

    /**
     * Test for the copySubtree() method.
     */
    public function testCopySubtree()
    {
        $handler = $this->getPartlyMockedHandler(
            [
                'load',
                'changeMainLocation',
                'setSectionForSubtree',
                'create',
            ]
        );
        $subtreeContentRows = [
            ['node_id' => 10, 'main_node_id' => 1, 'parent_node_id' => 3, 'contentobject_id' => 21, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_10', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 11, 'main_node_id' => 11, 'parent_node_id' => 10, 'contentobject_id' => 211, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_11', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 12, 'main_node_id' => 15, 'parent_node_id' => 10, 'contentobject_id' => 215, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_12', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 13, 'main_node_id' => 2, 'parent_node_id' => 10, 'contentobject_id' => 22, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_13', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 14, 'main_node_id' => 11, 'parent_node_id' => 13, 'contentobject_id' => 211, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_14', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 15, 'main_node_id' => 15, 'parent_node_id' => 13, 'contentobject_id' => 215, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_15', 'sort_field' => 2, 'sort_order' => 1],
            ['node_id' => 16, 'main_node_id' => 16, 'parent_node_id' => 15, 'contentobject_id' => 216, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 0, 'priority' => 0, 'path_identification_string' => 'test_16', 'sort_field' => 2, 'sort_order' => 1],
        ];
        $destinationData = ['node_id' => 5, 'main_node_id' => 5, 'parent_node_id' => 4, 'contentobject_id' => 200, 'contentobject_version' => 1, 'is_hidden' => 0, 'is_invisible' => 1, 'path_identification_string' => 'test_destination'];
        $mainLocationsMap = [true, true, true, true, 1011, 1012, true];
        $updateMainLocationsMap = [1215 => 1015];
        $offset = 1000;

        $this->locationGateway
            ->expects($this->once())
            ->method('getSubtreeContent')
            ->with($subtreeContentRows[0]['node_id'])
            ->will($this->returnValue($subtreeContentRows));
        $this->locationGateway
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with($destinationData['node_id'])
            ->will($this->returnValue($destinationData));

        $stateCalls = [];
        $contentCalls = [];
        $handlerCalls = [];

        $this->objectStateHandler
            ->expects($this->once())
            ->method('loadAllGroups')
            ->willReturnCallback(
                self::sequence(
                    $stateCalls,
                    'loadAllGroups',
                    [[]],
                    [
                        [
                            new ObjectStateGroup(['id' => 10]),
                            new ObjectStateGroup(['id' => 20]),
                        ],
                    ]
                )
            );
        $this->objectStateHandler
            ->expects($this->exactly(2))
            ->method('loadObjectStates')
            ->willReturnCallback(
                self::sequence(
                    $stateCalls,
                    'loadObjectStates',
                    [[$this->equalTo(10)], [$this->equalTo(20)]],
                    [
                        [
                            new ObjectState(['id' => 11, 'groupId' => 10]),
                            new ObjectState(['id' => 12, 'groupId' => 10]),
                        ],
                        [
                            new ObjectState(['id' => 21, 'groupId' => 20]),
                            new ObjectState(['id' => 22, 'groupId' => 20]),
                        ],
                    ]
                )
            );
        $defaultObjectStates = [
            new ObjectState(['id' => 11, 'groupId' => 10]),
            new ObjectState(['id' => 21, 'groupId' => 20]),
        ];

        $contentIds = array_values(
            array_unique(
                array_column($subtreeContentRows, 'contentobject_id')
            )
        );
        $copyArguments = [];
        $copyResults = [];
        $setContentStateArguments = [];
        $publishArguments = [];
        $publishResults = [];
        foreach ($contentIds as $contentId) {
            $copyArguments[] = [$contentId, 1];
            $copyResults[] = new Content(
                [
                    'versionInfo' => new VersionInfo(
                        [
                            'contentInfo' => new ContentInfo(
                                [
                                    'id' => $contentId + $offset,
                                    'currentVersionNo' => 1,
                                ]
                            ),
                        ]
                    ),
                ]
            );

            foreach ($defaultObjectStates as $objectState) {
                $setContentStateArguments[] = [
                    $contentId + $offset,
                    $objectState->groupId,
                    $objectState->id,
                ];
            }

            $publishArguments[] = [
                $contentId + $offset,
                1,
                $this->isInstanceOf(Content\MetadataUpdateStruct::class),
            ];
            $publishResults[] = new Content(
                [
                    'versionInfo' => new VersionInfo(
                        [
                            'contentInfo' => new ContentInfo(
                                [
                                    'id' => ($contentId + $offset),
                                ]
                            ),
                        ]
                    ),
                ]
            );
        }

        $this->contentHandler
            ->expects($this->exactly(count($contentIds)))
            ->method('copy')
            ->willReturnCallback(self::sequence($contentCalls, 'copy', $copyArguments, $copyResults));

        $this->objectStateHandler
            ->expects($this->exactly(count($setContentStateArguments)))
            ->method('setContentState')
            ->willReturnCallback(self::sequence($stateCalls, 'setContentState', $setContentStateArguments));

        $this->contentHandler
            ->expects($this->exactly(count($contentIds)))
            ->method('publish')
            ->willReturnCallback(self::sequence($contentCalls, 'publish', $publishArguments, $publishResults));

        $pathStrings = [$destinationData['node_id'] => $destinationData['path_identification_string']];
        $createStructArguments = [];
        $createStructResults = [];
        $createArguments = [];
        $createResults = [];
        foreach ($subtreeContentRows as $index => $row) {
            $mapper = new Mapper();
            $createStruct = $mapper->getLocationCreateStruct($row);
            $createStructArguments[] = [$row];
            $createStructResults[] = $createStruct;

            $createStruct = clone $createStruct;
            $createStruct->contentId = $createStruct->contentId + $offset;
            $createStruct->parentId = $index === 0 ? $destinationData['node_id'] : $createStruct->parentId + $offset;
            $createStruct->invisible = true;
            $createStruct->mainLocationId = $mainLocationsMap[$index];
            $createStruct->pathIdentificationString = $pathStrings[$createStruct->parentId] . '/' . $row['path_identification_string'];
            $pathStrings[$row['node_id'] + $offset] = $createStruct->pathIdentificationString;
            $createArguments[] = [$createStruct];
            $createResults[] = new Location(
                [
                    'id' => $row['node_id'] + $offset,
                    'contentId' => $row['contentobject_id'],
                    'hidden' => false,
                    'invisible' => true,
                    'pathIdentificationString' => $createStruct->pathIdentificationString,
                ]
            );
        }

        $this->locationMapper
            ->expects($this->exactly(count($subtreeContentRows)))
            ->method('getLocationCreateStruct')
            ->withConsecutive(...$createStructArguments)
            ->willReturnOnConsecutiveCalls(...$createStructResults);

        $handler
            ->expects($this->exactly(count($subtreeContentRows)))
            ->method('create')
            ->willReturnCallback(self::sequence($handlerCalls, 'create', $createArguments, $createResults));

        foreach ($updateMainLocationsMap as $contentId => $locationId) {
            $handler
                ->expects($this->any())
                ->method('changeMainLocation')
                ->with($contentId, $locationId)
                ->willReturnCallback(self::sequence($handlerCalls, 'changeMainLocation', [[$contentId, $locationId]]));
        }

        $handler
            ->expects($this->once())
            ->method('load')
            ->with($destinationData['node_id'])
            ->willReturnCallback(
                self::sequence(
                    $handlerCalls,
                    'load',
                    [[$destinationData['node_id']]],
                    [new Location(['contentId' => $destinationData['contentobject_id']])]
                )
            );

        $this->contentHandler
            ->expects($this->exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(
                self::sequence(
                    $contentCalls,
                    'loadContentInfo',
                    [[$destinationData['contentobject_id']], [21]],
                    [
                        new ContentInfo(['sectionId' => 12345]),
                        new ContentInfo(['mainLocationId' => 1010]),
                    ]
                )
            );

        $handler
            ->expects($this->once())
            ->method('setSectionForSubtree')
            ->with($subtreeContentRows[0]['node_id'] + $offset, 12345)
            ->willReturnCallback(self::sequence($handlerCalls, 'setSectionForSubtree', [[]]));

        $handler->copySubtree(
            $subtreeContentRows[0]['node_id'],
            $destinationData['node_id']
        );

        $expectedContentCalls = [];
        foreach ($contentIds as $contentId) {
            array_push($expectedContentCalls, 'copy', 'publish');
        }
        array_push($expectedContentCalls, 'loadContentInfo', 'loadContentInfo');
        self::assertSame($expectedContentCalls, $contentCalls);
        self::assertSame(
            array_merge(
                ['loadAllGroups', 'loadObjectStates', 'loadObjectStates'],
                array_fill(0, count($setContentStateArguments), 'setContentState')
            ),
            $stateCalls
        );
        self::assertSame(
            array_merge(
                array_fill(0, count($subtreeContentRows), 'create'),
                ['changeMainLocation', 'load', 'setSectionForSubtree']
            ),
            $handlerCalls
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Persistence\Content\Location\Handler::countLocationsByContent
     */
    public function testCountLocationsByContent(): void
    {
        $handler = $this->getLocationHandler();

        $contentId = 41;

        $this->locationGateway
            ->expects(self::once())
            ->method('countLocationsByContentId')
            ->with($contentId);

        $handler->countLocationsByContent($contentId);
    }

    /**
     * Returns the handler to test with $methods mocked.
     *
     * @param string[] $methods
     *
     * @return LocationHandler
     */
    protected function getPartlyMockedHandler(array $methods)
    {
        return $this->getMockBuilder(LocationHandler::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->locationGateway = $this->createMock(Gateway::class),
                    $this->locationMapper = $this->createMock(Mapper::class),
                    $this->contentHandler = $this->createMock(ContentHandler::class),
                    $this->objectStateHandler = $this->createMock(ObjectStateHandler::class),
                    $this->treeHandler = $this->createMock(TreeHandler::class),
                ]
            )
            ->getMock();
    }
}

class_alias(LocationHandlerTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\LocationHandlerTest');
