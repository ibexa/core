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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;

#[CoversClass(Handler::class)]
class LocationHandlerTest extends TestCase
{
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
     * @var \Ibexa\Core\Persistence\Legacy\Content\Handler
     */
    protected $contentHandler;

    /**
     * Mocked object state handler instance.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\ObjectState\Handler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $objectStateHandler;

    /**
     * Mocked Tree handler instance.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\TreeHandler|\PHPUnit\Framework\MockObject\MockObject
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
            self::createStub(ObjectStateHandler::class),
            $this->treeHandler
        );
    }

    public function testLoadLocation(): void
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
            ->method('loadLocation')
            ->with(77)
            ->willReturn(new Location());

        $location = $handler->load(77);

        self::assertInstanceOf(Location::class, $location);
    }

    public function testLoadLocationSubtree(): void
    {
        $this->locationGateway
            ->expects(self::once())
            ->method('getSubtreeNodeIdToContentIdMap')
            ->with(77)
            ->willReturn(
                [
                    [77 => 100],
                    [78 => 101],
                ]
            );

        self::assertCount(2, $this->getLocationHandler()->loadSubtreeIds(77));
    }

    public function testLoadLocationByRemoteId(): void
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeDataByRemoteId')
            ->with('abc123')
            ->willReturn(
                [
                    'node_id' => 77,
                ]
            );

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with(['node_id' => 77])
            ->willReturn(new Location());

        $location = $handler->loadByRemoteId('abc123');

        self::assertInstanceOf(Location::class, $location);
    }

    public function testLoadLocationsByContent(): void
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('loadLocationDataByContent')
            ->with(23, 42)
            ->will(
                self::returnValue(
                    []
                )
            );

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationsFromRows')
            ->with([])
            ->will(self::returnValue(['a', 'b']));

        $locations = $handler->loadLocationsByContent(23, 42);

        self::assertIsArray($locations);
    }

    public function loadParentLocationsForDraftContent()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('loadParentLocationsDataForDraftContent')
            ->with(23)
            ->will(
                self::returnValue(
                    []
                )
            );

        $this->locationMapper
            ->expects(self::once())
            ->method('createLocationsFromRows')
            ->with([])
            ->will(self::returnValue(['a', 'b']));

        $locations = $handler->loadParentLocationsForDraftContent(23);

        self::assertIsArray($locations);
    }

    public function testMoveSubtree(): void
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

        $getBasicNodeDataMatcher = self::exactly(2);
        $this->locationGateway
            ->expects($getBasicNodeDataMatcher)
            ->method('getBasicNodeData')
            ->willReturnCallback(static function (int $nodeId) use ($getBasicNodeDataMatcher, $sourceData, $destinationData): array {
                $expectedArgs = [$sourceData['node_id'], $destinationData['node_id']];
                $returnValues = [$sourceData, $destinationData];
                $index = $getBasicNodeDataMatcher->numberOfInvocations() - 1;
                self::assertSame($expectedArgs[$index], $nodeId);

                return $returnValues[$index];
            });

        $this->locationGateway
            ->expects(self::once())
            ->method('moveSubtreeNodes')
            ->with($sourceData, $destinationData);

        $this->locationGateway
            ->expects(self::once())
            ->method('updateNodeAssignment')
            ->with(67, 2, 77, 5);

        $loadLocationMatcher = self::exactly(2);
        $this->treeHandler
            ->expects($loadLocationMatcher)
            ->method('loadLocation')
            ->willReturnCallback(static function (int $nodeId) use ($loadLocationMatcher, $sourceData, $destinationData): Location {
                $expectedArgs = [$sourceData['node_id'], $destinationData['node_id']];
                $returnValues = [
                    new Location([
                        'id' => $sourceData['node_id'],
                        'contentId' => $sourceData['contentobject_id'],
                    ]),
                    new Location(['contentId' => $destinationData['contentobject_id']]),
                ];
                $index = $loadLocationMatcher->numberOfInvocations() - 1;
                self::assertSame($expectedArgs[$index], $nodeId);

                return $returnValues[$index];
            });

        $loadContentInfoMatcher = self::exactly(2);
        $this->contentHandler
            ->expects($loadContentInfoMatcher)
            ->method('loadContentInfo')
            ->willReturnCallback(static function (int $contentId) use ($loadContentInfoMatcher, $sourceData, $destinationData): ContentInfo {
                $expectedArgs = [$destinationData['contentobject_id'], $sourceData['contentobject_id']];
                $returnValues = [
                    new ContentInfo(['sectionId' => 12345]),
                    new ContentInfo(['mainLocationId' => 69]),
                ];
                $index = $loadContentInfoMatcher->numberOfInvocations() - 1;
                self::assertSame($expectedArgs[$index], $contentId);

                return $returnValues[$index];
            });

        $this->treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(69, 12345);

        $handler->move(69, 77);
    }

    public function testHideUpdateHidden(): void
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with(69)
            ->will(
                self::returnValue(
                    [
                        'node_id' => 69,
                        'path_string' => '/1/2/69/',
                        'contentobject_id' => 67,
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::once())
            ->method('hideSubtree')
            ->with('/1/2/69/');

        $handler->hide(69);
    }

    #[Depends('testHideUpdateHidden')]
    public function testHideUnhideUpdateHidden(): void
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with(69)
            ->will(
                self::returnValue(
                    [
                        'node_id' => 69,
                        'path_string' => '/1/2/69/',
                        'contentobject_id' => 67,
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::once())
            ->method('unhideSubtree')
            ->with('/1/2/69/');

        $handler->unhide(69);
    }

    public function testSwapLocations(): void
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
            ->method('swap')
            ->with(70, 78);

        $handler->swap(70, 78);
    }

    public function testCreateLocation(): void
    {
        $handler = $this->getLocationHandler();

        $createStruct = new CreateStruct();
        $createStruct->parentId = 77;
        $spiLocation = new Location();
        $spiLocation->id = 78;
        $spiLocation->parentId = 77;
        $spiLocation->pathString = '/1/2/77/78/';

        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with(77)
            ->will(
                self::returnValue(
                    $parentInfo = [
                        'node_id' => 77,
                        'path_string' => '/1/2/77/',
                    ]
                )
            );

        $this->locationGateway
            ->expects(self::once())
            ->method('create')
            ->with($createStruct, $parentInfo)
            ->will(self::returnValue($spiLocation));

        $this->locationGateway
            ->expects(self::once())
            ->method('createNodeAssignment')
            ->with($createStruct, 77, 2);

        $handler->create($createStruct);
    }

    public function testUpdateLocation(): void
    {
        $handler = $this->getLocationHandler();

        $updateStruct = new UpdateStruct();
        $updateStruct->priority = 77;

        $this->locationGateway
            ->expects(self::once())
            ->method('update')
            ->with($updateStruct, 23);

        $handler->update($updateStruct, 23);
    }

    public function testSetSectionForSubtree(): void
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(69, 3);

        $handler->setSectionForSubtree(69, 3);
    }

    public function testChangeMainLocation(): void
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34);

        $handler->changeMainLocation(12, 34);
    }

    /**
     * Test for the removeSubtree() method.
     */
    public function testRemoveSubtree(): void
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
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
     * Test for the copySubtree() method.
     */
    public function testCopySubtree(): void
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
            ->expects(self::once())
            ->method('getSubtreeContent')
            ->with($subtreeContentRows[0]['node_id'])
            ->will(self::returnValue($subtreeContentRows));
        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with($destinationData['node_id'])
            ->will(self::returnValue($destinationData));

        $defaultObjectStates = [
            new ObjectState(['id' => 11, 'groupId' => 10]),
            new ObjectState(['id' => 21, 'groupId' => 20]),
        ];

        $contentIds = array_values(
            array_unique(
                array_column($subtreeContentRows, 'contentobject_id')
            )
        );

        // loadAllGroups(), loadObjectStates() and setContentState() are all called on the same
        // mock in a single global order: loadAllGroups() once, loadObjectStates() twice (once per
        // object state group), then setContentState() once per content id per default object state.
        $objectStateHandlerInvocationOrder = 0;

        $setContentStateExpectedArgs = [];
        foreach ($contentIds as $contentId) {
            foreach ($defaultObjectStates as $objectState) {
                $setContentStateExpectedArgs[] = [$contentId + $offset, $objectState->groupId, $objectState->id];
            }
        }

        $this->objectStateHandler
            ->expects(self::once())
            ->method('loadAllGroups')
            ->willReturnCallback(static function () use (&$objectStateHandlerInvocationOrder): array {
                self::assertSame(0, $objectStateHandlerInvocationOrder++);

                return [
                    new ObjectStateGroup(['id' => 10]),
                    new ObjectStateGroup(['id' => 20]),
                ];
            });

        $this->objectStateHandler
            ->expects(self::exactly(2))
            ->method('loadObjectStates')
            ->willReturnCallback(static function (int $groupId) use (&$objectStateHandlerInvocationOrder): array {
                $order = $objectStateHandlerInvocationOrder++;
                switch ($order) {
                    case 1:
                        self::assertSame(10, $groupId);

                        return [
                            new ObjectState(['id' => 11, 'groupId' => 10]),
                            new ObjectState(['id' => 12, 'groupId' => 10]),
                        ];
                    case 2:
                        self::assertSame(20, $groupId);

                        return [
                            new ObjectState(['id' => 21, 'groupId' => 20]),
                            new ObjectState(['id' => 22, 'groupId' => 20]),
                        ];
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadObjectStates()', $order));
                }
            });

        $this->objectStateHandler
            ->expects(self::exactly(count($setContentStateExpectedArgs)))
            ->method('setContentState')
            ->willReturnCallback(static function (int $contentId, int $groupId, int $stateId) use (&$objectStateHandlerInvocationOrder, $setContentStateExpectedArgs): bool {
                $order = $objectStateHandlerInvocationOrder++;
                $index = $order - 3;
                self::assertArrayHasKey($index, $setContentStateExpectedArgs);
                self::assertSame($setContentStateExpectedArgs[$index], [$contentId, $groupId, $stateId]);

                return true;
            });

        // copy(), publish() and (later) loadContentInfo() are all called on the content handler
        // mock in a single global order: copy()/publish() alternate per content id, then
        // loadContentInfo() is called twice at the end.
        $contentHandlerInvocationOrder = 0;

        $copyExpectedArgs = [];
        $copyReturnValues = [];
        $publishExpectedArgs = [];
        $publishReturnValues = [];
        foreach ($contentIds as $contentId) {
            $copyExpectedArgs[] = [$contentId, 1];
            $copyReturnValues[] = new Content(
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
            $publishExpectedArgs[] = [$contentId + $offset, 1];
            $publishReturnValues[] = new Content(
                [
                    'versionInfo' => new VersionInfo(
                        [
                            'contentInfo' => new ContentInfo(
                                [
                                    'id' => $contentId + $offset,
                                ]
                            ),
                        ]
                    ),
                ]
            );
        }

        $this->contentHandler
            ->expects(self::exactly(count($contentIds)))
            ->method('copy')
            ->willReturnCallback(static function (int $contentId, int $versionNo) use (&$contentHandlerInvocationOrder, $copyExpectedArgs, $copyReturnValues): Content {
                $order = $contentHandlerInvocationOrder++;
                self::assertSame(0, $order % 2, sprintf('Expected copy() at an even invocation order, got %d', $order));
                $index = intdiv($order, 2);
                self::assertSame($copyExpectedArgs[$index], [$contentId, $versionNo]);

                return $copyReturnValues[$index];
            });

        $this->contentHandler
            ->expects(self::exactly(count($contentIds)))
            ->method('publish')
            ->willReturnCallback(static function (int $contentId, int $versionNo, Content\MetadataUpdateStruct $metadataUpdateStruct) use (&$contentHandlerInvocationOrder, $publishExpectedArgs, $publishReturnValues): Content {
                $order = $contentHandlerInvocationOrder++;
                self::assertSame(1, $order % 2, sprintf('Expected publish() at an odd invocation order, got %d', $order));
                $index = intdiv($order, 2);
                self::assertSame($publishExpectedArgs[$index], [$contentId, $versionNo]);

                return $publishReturnValues[$index];
            });

        $locationCreateStructReturnValues = [];
        $expectedCreateStructArgs = [];
        $createReturnValues = [];
        foreach ($subtreeContentRows as $index => $row) {
            $mapper = new Mapper();
            $createStruct = $mapper->getLocationCreateStruct($row);
            $locationCreateStructReturnValues[$index] = $createStruct;

            $expectedCreateStruct = clone $createStruct;
            $expectedCreateStruct->contentId = $expectedCreateStruct->contentId + $offset;
            $expectedCreateStruct->parentId = $index === 0 ? $destinationData['node_id'] : $expectedCreateStruct->parentId + $offset;
            $expectedCreateStruct->invisible = true;
            $expectedCreateStruct->mainLocationId = $mainLocationsMap[$index];
            $expectedCreateStructArgs[$index] = $expectedCreateStruct;

            $createReturnValues[$index] = new Location(
                [
                    'id' => $row['node_id'] + $offset,
                    'contentId' => $row['contentobject_id'],
                    'hidden' => false,
                    'invisible' => true,
                ]
            );
        }

        $getLocationCreateStructMatcher = self::exactly(count($subtreeContentRows));
        $this->locationMapper
            ->expects($getLocationCreateStructMatcher)
            ->method('getLocationCreateStruct')
            ->willReturnCallback(static function (array $row) use ($getLocationCreateStructMatcher, $subtreeContentRows, $locationCreateStructReturnValues): CreateStruct {
                $index = $getLocationCreateStructMatcher->numberOfInvocations() - 1;
                self::assertSame($subtreeContentRows[$index], $row);

                return $locationCreateStructReturnValues[$index];
            });

        $createMatcher = self::exactly(count($subtreeContentRows));
        $handler
            ->expects($createMatcher)
            ->method('create')
            ->willReturnCallback(static function (CreateStruct $createStruct) use ($createMatcher, $expectedCreateStructArgs, $createReturnValues): Location {
                $index = $createMatcher->numberOfInvocations() - 1;
                self::assertEquals($expectedCreateStructArgs[$index], $createStruct);

                return $createReturnValues[$index];
            });

        foreach ($updateMainLocationsMap as $contentId => $locationId) {
            $handler
                ->expects(self::any())
                ->method('changeMainLocation')
                ->with($contentId, $locationId);
        }

        $handler
            ->expects(self::once())
            ->method('load')
            ->with($destinationData['node_id'])
            ->will(self::returnValue(new Location(['contentId' => $destinationData['contentobject_id']])));

        $this->contentHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(static function (int $contentId) use (&$contentHandlerInvocationOrder, $contentIds, $destinationData): ContentInfo {
                $order = $contentHandlerInvocationOrder++;
                switch ($order) {
                    case count($contentIds) * 2:
                        self::assertSame($destinationData['contentobject_id'], $contentId);

                        return new ContentInfo(['sectionId' => 12345]);
                    case count($contentIds) * 2 + 1:
                        self::assertSame(21, $contentId);

                        return new ContentInfo(['mainLocationId' => 1010]);
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadContentInfo()', $order));
                }
            });

        $handler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with($subtreeContentRows[0]['node_id'] + $offset, 12345);

        $handler->copySubtree(
            $subtreeContentRows[0]['node_id'],
            $destinationData['node_id']
        );
    }

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
     * @return \Ibexa\Core\Persistence\Legacy\Content\Location\Handler
     */
    protected function getPartlyMockedHandler(array $methods)
    {
        return $this->getMockBuilder(LocationHandler::class)
            ->setConstructorArgs(
                [
                    $this->locationGateway = $this->createMock(Gateway::class),
                    $this->locationMapper = $this->createMock(Mapper::class),
                    $this->contentHandler = $this->createMock(ContentHandler::class),
                    $this->objectStateHandler = $this->createMock(ObjectStateHandler::class),
                    $this->treeHandler = $this->createMock(TreeHandler::class),
                ]
            )
            ->onlyMethods(array_values($methods))
            ->getMock();
    }
}
