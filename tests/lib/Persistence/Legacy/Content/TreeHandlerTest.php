<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\FieldHandler;
use Ibexa\Core\Persistence\Legacy\Content\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper;
use Ibexa\Core\Persistence\Legacy\Content\TreeHandler;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Test case for Tree Handler.
 */
class TreeHandlerTest extends TestCase
{
    public function testLoadContentInfoByRemoteId()
    {
        $contentInfoData = [new ContentInfo()];

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with(42)
            ->will(self::returnValue([42]));

        $this->getContentMapperMock()
            ->expects(self::once())
            ->method('extractContentInfoFromRow')
            ->with(self::equalTo([42]))
            ->will(self::returnValue($contentInfoData));

        self::assertSame(
            $contentInfoData,
            $this->getTreeHandler()->loadContentInfo(42)
        );
    }

    public function testListVersions()
    {
        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('listVersions')
            ->with(self::equalTo(23))
            ->will(self::returnValue([['content_version_version' => 2]]));

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('loadVersionedNameData')
            ->with(self::equalTo([['id' => 23, 'version' => 2]]))
            ->will(self::returnValue([]));

        $this->getContentMapperMock()
            ->expects(self::once())
            ->method('extractVersionInfoListFromRows')
            ->with(self::equalTo([['content_version_version' => 2]]), [])
            ->will(self::returnValue([new VersionInfo()]));

        $versions = $this->getTreeHandler()->listVersions(23);

        self::assertEquals(
            [new VersionInfo()],
            $versions
        );
    }

    public function testRemoveRawContent()
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadContentInfo',
                'listVersions',
            ]
        );

        $treeHandler
            ->expects(self::once())
            ->method('listVersions')
            ->will(self::returnValue([new VersionInfo(), new VersionInfo()]));
        $treeHandler
            ->expects(self::once())
            ->method('loadContentInfo')
            ->with(23)
            ->will(self::returnValue(new ContentInfo(['mainLocationId' => 42])));

        $this->getFieldHandlerMock()
            ->expects(self::exactly(2))
            ->method('deleteFields')
            ->with(
                self::equalTo(23),
                self::isInstanceOf(VersionInfo::class)
            );

        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteRelations')
            ->with(self::equalTo(23));
        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteVersions')
            ->with(self::equalTo(23));
        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteNames')
            ->with(self::equalTo(23));
        $this->getContentGatewayMock()
            ->expects(self::once())
            ->method('deleteContent')
            ->with(self::equalTo(23));

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('removeElementFromTrash')
            ->with(self::equalTo(42));

        $treeHandler->removeRawContent(23);
    }

    public function testRemoveSubtree()
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'changeMainLocation',
                'removeRawContent',
            ]
        );

        // Setup getBasicNodeData expectations
        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('getBasicNodeData')
            ->willReturnCallback(static function ($nodeId) {
                return match ($nodeId) {
                    42 => ['contentobject_id' => 100, 'main_node_id' => 200],
                    201 => ['contentobject_id' => 101, 'main_node_id' => 201],
                    202 => ['contentobject_id' => 102, 'main_node_id' => 202],
                    default => throw new \InvalidArgumentException("Unexpected nodeId: $nodeId"),
                };
            });

        // Setup getChildren expectations
        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('getChildren')
            ->willReturnCallback(static function ($nodeId) {
                return match ($nodeId) {
                    42 => [['node_id' => 201], ['node_id' => 202]],
                    201, 202 => [],
                    default => throw new \InvalidArgumentException("Unexpected nodeId: $nodeId"),
                };
            });

        // Setup countLocationsByContentId expectations
        $this->getLocationGatewayMock()
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    101 => 1,
                    102 => 2,
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $treeHandler
            ->expects(self::once())
            ->method('removeRawContent')
            ->with(101);

        // Setup removeLocation expectations (called for nodes 201, 202, and 42)
        $removeLocationCalls = [];
        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('removeLocation')
            ->willReturnCallback(static function ($nodeId) use (&$removeLocationCalls) {
                $removeLocationCalls[] = $nodeId;
            });

        // Setup deleteNodeAssignment expectations (called for content ids 101, 102, and 100)
        $deleteNodeAssignmentCalls = [];
        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('deleteNodeAssignment')
            ->willReturnCallback(static function ($contentId) use (&$deleteNodeAssignmentCalls) {
                $deleteNodeAssignmentCalls[] = $contentId;
            });

        // Setup getFallbackMainNodeData expectation for content 102
        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('getFallbackMainNodeData')
            ->with(102, 202)
            ->willReturn([
                'node_id' => 203,
                'contentobject_version' => 1,
                'parent_node_id' => 204,
            ]);

        $treeHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(102, 203);

        // Start
        $treeHandler->removeSubtree(42);

        // Verify the order of removeLocation calls
        self::assertEquals([201, 202, 42], $removeLocationCalls);

        // Verify the order of deleteNodeAssignment calls
        self::assertEquals([101, 102, 100], $deleteNodeAssignmentCalls);
    }

    public function testSetSectionForSubtree()
    {
        $treeHandler = $this->getTreeHandler();

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with(69)
            ->willReturn([
                'node_id' => 69,
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
            ]);

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with('/1/2/69/', 3);

        $treeHandler->setSectionForSubtree(69, 3);
    }

    public function testChangeMainLocation()
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadLocation',
                'setSectionForSubtree',
                'loadContentInfo',
            ]
        );

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(static function ($locationId) {
                return match ($locationId) {
                    34 => new Location(['parentId' => 42]),
                    42 => new Location(['contentId' => 84]),
                    default => throw new \InvalidArgumentException("Unexpected locationId: $locationId"),
                };
            });

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    '12', 12 => new ContentInfo(['currentVersionNo' => 1]),
                    '84', 84 => new ContentInfo(['sectionId' => 4]),
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 42);

        $treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(34, 4);

        $treeHandler->changeMainLocation(12, 34);
    }

    public function testChangeMainLocationToLocationWithoutContentInfo()
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadLocation',
                'setSectionForSubtree',
                'loadContentInfo',
            ]
        );

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(static function ($locationId) {
                return match ($locationId) {
                    34 => new Location(['parentId' => 1]),
                    1 => new Location(['contentId' => 84]),
                    default => throw new \InvalidArgumentException("Unexpected locationId: $locationId"),
                };
            });

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(static function ($contentId) {
                return match ($contentId) {
                    '12', 12 => new ContentInfo(['currentVersionNo' => 1]),
                    '84', 84 => new ContentInfo(['sectionId' => 4]),
                    default => throw new \InvalidArgumentException("Unexpected contentId: $contentId"),
                };
            });

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 1);

        $treeHandler->changeMainLocation(12, 34);
    }

    public function testLoadLocation()
    {
        $treeHandler = $this->getTreeHandler();

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('getBasicNodeData')
            ->with(77)
            ->will(
                self::returnValue(
                    [
                        'node_id' => 77,
                    ]
                )
            );

        $this->getLocationMapperMock()
            ->expects(self::once())
            ->method('createLocationFromRow')
            ->with(['node_id' => 77])
            ->will(self::returnValue(new Location()));

        $location = $treeHandler->loadLocation(77);

        self::assertTrue($location instanceof Location);
    }

    public function testDeleteChildrenDraftsRecursive(): void
    {
        $locationGatewayMock = $this->getLocationGatewayMock();
        $contentGatewayMock = $this->getContentGatewayMock();
        $contentMapperMock = $this->getContentMapperMock();

        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('getChildren')
            ->willReturnMap([
                [42, [
                    ['node_id' => 201],
                    ['node_id' => 202],
                ]],
                [201, []],
                [202, []],
            ]);

        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('getSubtreeChildrenDraftContentIds')
            ->willReturnMap([
                [201, [101]],
                [202, [102]],
                [42, [99]],
            ]);

        $contentGatewayMock
            ->expects(self::exactly(3))
            ->method('loadContentInfo')
            ->willReturnMap([
                [101, ['main_node_id' => 201]],
                [102, ['main_node_id' => 202]],
                [99, ['main_node_id' => 42]],
            ]);

        $contentMapperMock
            ->expects(self::exactly(3))
            ->method('extractContentInfoFromRow')
            ->willReturnCallback(static function (array $row): ContentInfo {
                return new ContentInfo(['mainLocationId' => $row['main_node_id']]);
            });

        $contentGatewayMock
            ->expects(self::exactly(3))
            ->method('deleteContent')
            ->willReturnCallback(static function (int $contentId): void {
                self::assertContains($contentId, [99, 101, 102]);
            });

        $treeHandler = $this->getTreeHandler();

        $treeHandler->deleteChildrenDrafts(42);
    }

    /** @var MockObject|LocationGateway */
    protected $locationGatewayMock;

    /**
     * Returns Location Gateway mock.
     *
     * @return MockObject|LocationGateway
     */
    protected function getLocationGatewayMock()
    {
        if (!isset($this->locationGatewayMock)) {
            $this->locationGatewayMock = $this->getMockForAbstractClass(LocationGateway::class);
        }

        return $this->locationGatewayMock;
    }

    /** @var MockObject|LocationMapper */
    protected $locationMapperMock;

    /**
     * Returns a Location Mapper mock.
     *
     * @return MockObject|LocationMapper
     */
    protected function getLocationMapperMock()
    {
        if (!isset($this->locationMapperMock)) {
            $this->locationMapperMock = $this->createMock(LocationMapper::class);
        }

        return $this->locationMapperMock;
    }

    /** @var MockObject|Gateway */
    protected $contentGatewayMock;

    /**
     * Returns Content Gateway mock.
     *
     * @return MockObject|Gateway
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /** @var MockObject|Mapper */
    protected $contentMapper;

    /**
     * Returns a Content Mapper mock.
     *
     * @return MockObject|Mapper
     */
    protected function getContentMapperMock()
    {
        if (!isset($this->contentMapper)) {
            $this->contentMapper = $this->createMock(Mapper::class);
        }

        return $this->contentMapper;
    }

    /** @var MockObject|FieldHandler */
    protected $fieldHandlerMock;

    /**
     * Returns a FieldHandler mock.
     *
     * @return MockObject|FieldHandler
     */
    protected function getFieldHandlerMock()
    {
        if (!isset($this->fieldHandlerMock)) {
            $this->fieldHandlerMock = $this->createMock(FieldHandler::class);
        }

        return $this->fieldHandlerMock;
    }

    /**
     * @param array $methods
     *
     * @return MockObject|TreeHandler
     */
    protected function getPartlyMockedTreeHandler(array $methods)
    {
        return $this->getMockBuilder(TreeHandler::class)
            ->setMethods($methods)
            ->setConstructorArgs(
                [
                    $this->getLocationGatewayMock(),
                    $this->getLocationMapperMock(),
                    $this->getContentGatewayMock(),
                    $this->getContentMapperMock(),
                    $this->getFieldHandlerMock(),
                ]
            )
            ->getMock();
    }

    /**
     * @return TreeHandler
     */
    protected function getTreeHandler()
    {
        return new TreeHandler(
            $this->getLocationGatewayMock(),
            $this->getLocationMapperMock(),
            $this->getContentGatewayMock(),
            $this->getContentMapperMock(),
            $this->getFieldHandlerMock()
        );
    }
}
