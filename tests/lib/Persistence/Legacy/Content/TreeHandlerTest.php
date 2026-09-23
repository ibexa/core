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

/**
 * Test case for Tree Handler.
 */
class TreeHandlerTest extends TestCase
{
    public function testLoadContentInfoByRemoteId(): void
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

    public function testListVersions(): void
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

    public function testRemoveRawContent(): void
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

    public function testRemoveSubtree(): void
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'changeMainLocation',
                'removeRawContent',
            ]
        );

        // Calls to getBasicNodeData(), getChildren(), countLocationsByContentId(), removeLocation()
        // and deleteNodeAssignment() interleave across the original call and both recursive calls,
        // so a single shared counter tracks the global invocation order across all of them.
        $invocationOrder = 0;

        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('getBasicNodeData')
            ->willReturnCallback(static function (int $nodeId) use (&$invocationOrder): array {
                $order = $invocationOrder++;
                switch ($order) {
                    case 0:
                        self::assertSame(42, $nodeId);

                        return [
                            'contentobject_id' => 100,
                            'main_node_id' => 200,
                        ];
                    case 2:
                        self::assertSame(201, $nodeId);

                        return [
                            'contentobject_id' => 101,
                            'main_node_id' => 201,
                        ];
                    case 7:
                        self::assertSame(202, $nodeId);

                        return [
                            'contentobject_id' => 102,
                            'main_node_id' => 202,
                        ];
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for getBasicNodeData()', $order));
                }
            });

        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('getChildren')
            ->willReturnCallback(static function (int $nodeId) use (&$invocationOrder): array {
                $order = $invocationOrder++;
                switch ($order) {
                    case 1:
                        self::assertSame(42, $nodeId);

                        return [
                            ['node_id' => 201],
                            ['node_id' => 202],
                        ];
                    case 3:
                        self::assertSame(201, $nodeId);

                        return [];
                    case 8:
                        self::assertSame(202, $nodeId);

                        return [];
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for getChildren()', $order));
                }
            });

        $this->getLocationGatewayMock()
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(static function (int $contentId) use (&$invocationOrder): int {
                $order = $invocationOrder++;
                switch ($order) {
                    case 4:
                        self::assertSame(101, $contentId);

                        return 1;
                    case 9:
                        self::assertSame(102, $contentId);

                        return 2;
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for countLocationsByContentId()', $order));
                }
            });

        $treeHandler
            ->expects(self::once())
            ->method('removeRawContent')
            ->with(101);

        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('removeLocation')
            ->willReturnCallback(static function (int $nodeId) use (&$invocationOrder): void {
                $order = $invocationOrder++;
                switch ($order) {
                    case 5:
                        self::assertSame(201, $nodeId);
                        break;
                    case 11:
                        self::assertSame(202, $nodeId);
                        break;
                    case 13:
                        self::assertSame(42, $nodeId);
                        break;
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for removeLocation()', $order));
                }
            });

        $this->getLocationGatewayMock()
            ->expects(self::exactly(3))
            ->method('deleteNodeAssignment')
            ->willReturnCallback(static function (int $contentId) use (&$invocationOrder): void {
                $order = $invocationOrder++;
                switch ($order) {
                    case 6:
                        self::assertSame(101, $contentId);
                        break;
                    case 12:
                        self::assertSame(102, $contentId);
                        break;
                    case 14:
                        self::assertSame(100, $contentId);
                        break;
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for deleteNodeAssignment()', $order));
                }
            });

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('getFallbackMainNodeData')
            ->with(102, 202)
            ->willReturnCallback(static function () use (&$invocationOrder): array {
                self::assertSame(10, $invocationOrder++);

                return [
                    'node_id' => 203,
                    'contentobject_version' => 1,
                    'parent_node_id' => 204,
                ];
            });
        $treeHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(102, 203);

        // Start
        $treeHandler->removeSubtree(42);
    }

    public function testSetSectionForSubtree(): void
    {
        $treeHandler = $this->getTreeHandler();

        $this->getLocationGatewayMock()
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

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with('/1/2/69/', 3);

        $treeHandler->setSectionForSubtree(69, 3);
    }

    public function testChangeMainLocation(): void
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadLocation',
                'setSectionForSubtree',
                'loadContentInfo',
            ]
        );

        // loadLocation() and loadContentInfo() are called on the same partial mock in an
        // interleaved order, so a shared counter tracks the global invocation order.
        $invocationOrder = 0;

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(static function (int $locationId) use (&$invocationOrder): Location {
                $order = $invocationOrder++;
                switch ($order) {
                    case 0:
                        self::assertSame(34, $locationId);

                        return new Location(['parentId' => 42]);
                    case 2:
                        self::assertSame(42, $locationId);

                        return new Location(['contentId' => 84]);
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadLocation()', $order));
                }
            });

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(static function ($contentId) use (&$invocationOrder): ContentInfo {
                $order = $invocationOrder++;
                switch ($order) {
                    case 1:
                        self::assertEquals('12', $contentId);

                        return new ContentInfo(['currentVersionNo' => 1]);
                    case 3:
                        self::assertEquals('84', $contentId);

                        return new ContentInfo(['sectionId' => 4]);
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadContentInfo()', $order));
                }
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

    public function testChangeMainLocationToLocationWithoutContentInfo(): void
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadLocation',
                'setSectionForSubtree',
                'loadContentInfo',
            ]
        );

        // loadLocation() and loadContentInfo() are called on the same partial mock in an
        // interleaved order, so a shared counter tracks the global invocation order.
        $invocationOrder = 0;

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(static function (int $locationId) use (&$invocationOrder): Location {
                $order = $invocationOrder++;
                switch ($order) {
                    case 0:
                        self::assertSame(34, $locationId);

                        return new Location(['parentId' => 1]);
                    case 2:
                        self::assertSame(1, $locationId);

                        return new Location(['contentId' => 84]);
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadLocation()', $order));
                }
            });

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(static function ($contentId) use (&$invocationOrder): ContentInfo {
                $order = $invocationOrder++;
                switch ($order) {
                    case 1:
                        self::assertEquals('12', $contentId);

                        return new ContentInfo(['currentVersionNo' => 1]);
                    case 3:
                        self::assertEquals('84', $contentId);

                        return new ContentInfo(['sectionId' => 4]);
                    default:
                        self::fail(sprintf('Unexpected invocation order %d for loadContentInfo()', $order));
                }
            });

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 1);

        $treeHandler->changeMainLocation(12, 34);
    }

    public function testLoadLocation(): void
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

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Location\Gateway */
    protected $locationGatewayMock;

    /**
     * Returns Location Gateway mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Location\Gateway
     */
    protected function getLocationGatewayMock()
    {
        if (!isset($this->locationGatewayMock)) {
            $this->locationGatewayMock = $this->getMockForAbstractClass(LocationGateway::class);
        }

        return $this->locationGatewayMock;
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Location\Mapper */
    protected $locationMapperMock;

    /**
     * Returns a Location Mapper mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Location\Mapper
     */
    protected function getLocationMapperMock()
    {
        if (!isset($this->locationMapperMock)) {
            $this->locationMapperMock = $this->createMock(LocationMapper::class);
        }

        return $this->locationMapperMock;
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Gateway */
    protected $contentGatewayMock;

    /**
     * Returns Content Gateway mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Gateway
     */
    protected function getContentGatewayMock()
    {
        if (!isset($this->contentGatewayMock)) {
            $this->contentGatewayMock = $this->getMockForAbstractClass(Gateway::class);
        }

        return $this->contentGatewayMock;
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Mapper */
    protected $contentMapper;

    /**
     * Returns a Content Mapper mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\Mapper
     */
    protected function getContentMapperMock()
    {
        if (!isset($this->contentMapper)) {
            $this->contentMapper = $this->createMock(Mapper::class);
        }

        return $this->contentMapper;
    }

    /** @var \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\FieldHandler */
    protected $fieldHandlerMock;

    /**
     * Returns a FieldHandler mock.
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\FieldHandler
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
     * @return \PHPUnit\Framework\MockObject\MockObject|\Ibexa\Core\Persistence\Legacy\Content\TreeHandler
     */
    protected function getPartlyMockedTreeHandler(array $methods)
    {
        return $this->getMockBuilder(TreeHandler::class)
            ->setConstructorArgs(
                [
                    $this->getLocationGatewayMock(),
                    $this->getLocationMapperMock(),
                    $this->getContentGatewayMock(),
                    $this->getContentMapperMock(),
                    $this->getFieldHandlerMock(),
                ]
            )
            ->onlyMethods(array_values($methods))
            ->getMock();
    }

    /**
     * @return \Ibexa\Core\Persistence\Legacy\Content\TreeHandler
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
