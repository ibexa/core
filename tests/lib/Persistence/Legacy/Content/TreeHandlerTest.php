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
            ->expects($this->once())
            ->method('loadContentInfo')
            ->with(42)
            ->will($this->returnValue([42]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractContentInfoFromRow')
            ->with($this->equalTo([42]))
            ->will($this->returnValue($contentInfoData));

        $this->assertSame(
            $contentInfoData,
            $this->getTreeHandler()->loadContentInfo(42)
        );
    }

    public function testListVersions()
    {
        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('listVersions')
            ->with($this->equalTo(23))
            ->will($this->returnValue([['ezcontentobject_version_version' => 2]]));

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('loadVersionedNameData')
            ->with($this->equalTo([['id' => 23, 'version' => 2]]))
            ->will($this->returnValue([]));

        $this->getContentMapperMock()
            ->expects($this->once())
            ->method('extractVersionInfoListFromRows')
            ->with($this->equalTo([['ezcontentobject_version_version' => 2]]), [])
            ->will($this->returnValue([new VersionInfo()]));

        $versions = $this->getTreeHandler()->listVersions(23);

        $this->assertEquals(
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
            ->expects($this->once())
            ->method('listVersions')
            ->will($this->returnValue([new VersionInfo(), new VersionInfo()]));
        $treeHandler
            ->expects($this->once())
            ->method('loadContentInfo')
            ->with(23)
            ->will($this->returnValue(new ContentInfo(['mainLocationId' => 42])));

        $this->getFieldHandlerMock()
            ->expects($this->exactly(2))
            ->method('deleteFields')
            ->with(
                $this->equalTo(23),
                $this->isInstanceOf(VersionInfo::class)
            );

        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteRelations')
            ->with($this->equalTo(23));
        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteVersions')
            ->with($this->equalTo(23));
        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteNames')
            ->with($this->equalTo(23));
        $this->getContentGatewayMock()
            ->expects($this->once())
            ->method('deleteContent')
            ->with($this->equalTo(23));

        $this->getLocationGatewayMock()
            ->expects($this->once())
            ->method('removeElementFromTrash')
            ->with($this->equalTo(42));

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

        $this->getLocationGatewayMock()
            ->expects($this->exactly(3))
            ->method('getBasicNodeData')
            ->withConsecutive([42], [201], [202])
            ->willReturnOnConsecutiveCalls(
                ['contentobject_id' => 100, 'main_node_id' => 200],
                ['contentobject_id' => 101, 'main_node_id' => 201],
                ['contentobject_id' => 102, 'main_node_id' => 202]
            );
        $this->getLocationGatewayMock()
            ->expects($this->exactly(3))
            ->method('getChildren')
            ->withConsecutive([42], [201], [202])
            ->willReturnOnConsecutiveCalls(
                [
                    ['node_id' => 201],
                    ['node_id' => 202],
                ],
                [],
                []
            );

        $this->getLocationGatewayMock()
            ->expects($this->exactly(2))
            ->method('countLocationsByContentId')
            ->withConsecutive([101], [102])
            ->willReturnOnConsecutiveCalls(1, 2);
        $treeHandler
            ->expects($this->once())
            ->method('removeRawContent')
            ->with(101);
        $this->getLocationGatewayMock()
            ->expects($this->once())
            ->method('getFallbackMainNodeData')
            ->with(102, 202)
            ->will(
                $this->returnValue(
                    [
                        'node_id' => 203,
                        'contentobject_version' => 1,
                        'parent_node_id' => 204,
                    ]
                )
            );
        $treeHandler
            ->expects($this->once())
            ->method('changeMainLocation')
            ->with(102, 203);
        $this->getLocationGatewayMock()
            ->expects($this->exactly(3))
            ->method('removeLocation')
            ->withConsecutive([201], [202], [42]);
        $this->getLocationGatewayMock()
            ->expects($this->exactly(3))
            ->method('deleteNodeAssignment')
            ->withConsecutive([101], [102], [100]);

        // Start
        $treeHandler->removeSubtree(42);
    }

    public function testSetSectionForSubtree()
    {
        $treeHandler = $this->getTreeHandler();

        $this->getLocationGatewayMock()
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

        $this->getLocationGatewayMock()
            ->expects($this->once())
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
            ->expects($this->exactly(2))
            ->method('loadLocation')
            ->withConsecutive([34], [42])
            ->willReturnOnConsecutiveCalls(
                new Location(['parentId' => 42]),
                new Location(['contentId' => 84])
            );

        $treeHandler
            ->expects($this->exactly(2))
            ->method('loadContentInfo')
            ->withConsecutive(['12'], ['84'])
            ->willReturnOnConsecutiveCalls(
                new ContentInfo(['currentVersionNo' => 1]),
                new ContentInfo(['sectionId' => 4])
            );

        $this->getLocationGatewayMock()
            ->expects($this->once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 42);

        $treeHandler
            ->expects($this->once())
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
            ->expects($this->exactly(2))
            ->method('loadLocation')
            ->withConsecutive([34], [1])
            ->willReturnOnConsecutiveCalls(
                new Location(['parentId' => 1]),
                new Location(['contentId' => 84])
            );

        $treeHandler
            ->expects($this->exactly(2))
            ->method('loadContentInfo')
            ->withConsecutive(['12'], ['84'])
            ->willReturnOnConsecutiveCalls(
                new ContentInfo(['currentVersionNo' => 1]),
                new ContentInfo(['sectionId' => 4])
            );

        $this->getLocationGatewayMock()
            ->expects($this->once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 1);

        $treeHandler
            ->expects($this->once())
            ->method('setSectionForSubtree')
            ->with(34, 4);

        $treeHandler->changeMainLocation(12, 34);
    }

    public function testLoadLocation()
    {
        $treeHandler = $this->getTreeHandler();

        $this->getLocationGatewayMock()
            ->expects($this->once())
            ->method('getBasicNodeData')
            ->with(77)
            ->will(
                $this->returnValue(
                    [
                        'node_id' => 77,
                    ]
                )
            );

        $this->getLocationMapperMock()
            ->expects($this->once())
            ->method('createLocationFromRow')
            ->with(['node_id' => 77])
            ->will($this->returnValue(new Location()));

        $location = $treeHandler->loadLocation(77);

        $this->assertTrue($location instanceof Location);
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

class_alias(TreeHandlerTest::class, 'eZ\Publish\Core\Persistence\Legacy\Tests\Content\TreeHandlerTest');
