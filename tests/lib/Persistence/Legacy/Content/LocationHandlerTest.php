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

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Handler
 */
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
            $this->createMock(ObjectStateHandler::class),
            $this->treeHandler
        );
    }

    public function testLoadLocation()
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

    public function testLoadLocationSubtree()
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

    public function testLoadLocationByRemoteId()
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

    public function testLoadLocationsByContent()
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

    public function testMoveSubtree()
    {
        $handler = $this->getLocationHandler();

        $sourceData = [
            'node_id' => 69,
            'path_string' => '/1/2/69/',
            'parent_node_id' => 2,
            'contentobject_id' => 67,
        ];
        $this->locationGateway
            ->expects(self::at(0))
            ->method('getBasicNodeData')
            ->with(69)
            ->will(self::returnValue($sourceData));

        $destinationData = [
            'node_id' => 77,
            'path_string' => '/1/2/77/',
            'contentobject_id' => 68,
        ];
        $this->locationGateway
            ->expects(self::at(1))
            ->method('getBasicNodeData')
            ->with(77)
            ->will(self::returnValue($destinationData));

        $this->locationGateway
            ->expects(self::once())
            ->method('moveSubtreeNodes')
            ->with($sourceData, $destinationData);

        $this->locationGateway
            ->expects(self::once())
            ->method('updateNodeAssignment')
            ->with(67, 2, 77, 5);

        $this->treeHandler
            ->expects(self::at(0))
            ->method('loadLocation')
            ->with($sourceData['node_id'])
            ->will(self::returnValue(
                new Location(
                    [
                        'id' => $sourceData['node_id'],
                        'contentId' => $sourceData['contentobject_id'],
                    ]
                )
            ));

        $this->treeHandler
            ->expects(self::at(1))
            ->method('loadLocation')
            ->with($destinationData['node_id'])
            ->will(self::returnValue(new Location(['contentId' => $destinationData['contentobject_id']])));

        $this->contentHandler
            ->expects(self::at(0))
            ->method('loadContentInfo')
            ->with($destinationData['contentobject_id'])
            ->will(self::returnValue(new ContentInfo(['sectionId' => 12345])));

        $this->contentHandler
            ->expects(self::at(1))
            ->method('loadContentInfo')
            ->with($sourceData['contentobject_id'])
            ->will(self::returnValue(new ContentInfo(['mainLocationId' => 69])));

        $this->treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(69, 12345);

        $handler->move(69, 77);
    }

    public function testHideUpdateHidden()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::at(0))
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

    /**
     * @depends testHideUpdateHidden
     */
    public function testHideUnhideUpdateHidden()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::at(0))
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

    public function testSwapLocations()
    {
        $handler = $this->getLocationHandler();

        $this->locationGateway
            ->expects(self::once())
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

    public function testUpdateLocation()
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

    public function testSetSectionForSubtree()
    {
        $handler = $this->getLocationHandler();

        $this->treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(69, 3);

        $handler->setSectionForSubtree(69, 3);
    }

    public function testChangeMainLocation()
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
    public function testRemoveSubtree()
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
            ->expects(self::once())
            ->method('getSubtreeContent')
            ->with($subtreeContentRows[0]['node_id'])
            ->will(self::returnValue($subtreeContentRows));
        $this->locationGateway
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with($destinationData['node_id'])
            ->will(self::returnValue($destinationData));

        $objectStateHandlerCall = 0;
        $this->objectStateHandler->expects(self::at($objectStateHandlerCall++))
            ->method('loadAllGroups')
            ->will(
                self::returnValue(
                    [
                        new ObjectStateGroup(['id' => 10]),
                        new ObjectStateGroup(['id' => 20]),
                    ]
                )
            );
        $this->objectStateHandler->expects(self::at($objectStateHandlerCall++))
            ->method('loadObjectStates')
            ->with(self::equalTo(10))
            ->will(
                self::returnValue(
                    [
                        new ObjectState(['id' => 11, 'groupId' => 10]),
                        new ObjectState(['id' => 12, 'groupId' => 10]),
                    ]
                )
            );
        $this->objectStateHandler->expects(self::at($objectStateHandlerCall++))
            ->method('loadObjectStates')
            ->with(self::equalTo(20))
            ->will(
                self::returnValue(
                    [
                        new ObjectState(['id' => 21, 'groupId' => 20]),
                        new ObjectState(['id' => 22, 'groupId' => 20]),
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
        foreach ($contentIds as $index => $contentId) {
            $this->contentHandler
                ->expects(self::at($index * 2))
                ->method('copy')
                ->with($contentId, 1)
                ->will(
                    self::returnValue(
                        new Content(
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
                        )
                    )
                );

            foreach ($defaultObjectStates as $objectState) {
                $this->objectStateHandler->expects(self::at($objectStateHandlerCall++))
                    ->method('setContentState')
                    ->with(
                        $contentId + $offset,
                        $objectState->groupId,
                        $objectState->id
                    );
            }

            $this->contentHandler
                ->expects(self::at($index * 2 + 1))
                ->method('publish')
                ->with(
                    $contentId + $offset,
                    1,
                    self::isInstanceOf(Content\MetadataUpdateStruct::class)
                )
                ->will(
                    self::returnValue(
                        new Content(
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
                        )
                    )
                );
        }
        $lastContentHandlerIndex = $index * 2 + 1;

        foreach ($subtreeContentRows as $index => $row) {
            $mapper = new Mapper();
            $createStruct = $mapper->getLocationCreateStruct($row);
            $this->locationMapper
                ->expects(self::at($index))
                ->method('getLocationCreateStruct')
                ->with($row)
                ->will(self::returnValue($createStruct));

            $createStruct = clone $createStruct;
            $createStruct->contentId = $createStruct->contentId + $offset;
            $createStruct->parentId = $index === 0 ? $destinationData['node_id'] : $createStruct->parentId + $offset;
            $createStruct->invisible = true;
            $createStruct->mainLocationId = $mainLocationsMap[$index];
            $handler
                ->expects(self::at($index))
                ->method('create')
                ->with($createStruct)
                ->will(
                    self::returnValue(
                        new Location(
                            [
                                'id' => $row['node_id'] + $offset,
                                'contentId' => $row['contentobject_id'],
                                'hidden' => false,
                                'invisible' => true,
                            ]
                        )
                    )
                );
        }

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
            ->expects(self::at($lastContentHandlerIndex + 1))
            ->method('loadContentInfo')
            ->with($destinationData['contentobject_id'])
            ->will(self::returnValue(new ContentInfo(['sectionId' => 12345])));

        $this->contentHandler
            ->expects(self::at($lastContentHandlerIndex + 2))
            ->method('loadContentInfo')
            ->with(21)
            ->will(self::returnValue(new ContentInfo(['mainLocationId' => 1010])));

        $handler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with($subtreeContentRows[0]['node_id'] + $offset, 12345);

        $handler->copySubtree(
            $subtreeContentRows[0]['node_id'],
            $destinationData['node_id']
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
     * @return \Ibexa\Core\Persistence\Legacy\Content\Location\Handler
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
