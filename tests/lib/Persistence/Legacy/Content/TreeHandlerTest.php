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
     * Records only the first argument (the lookup key), later ones are defaults the test does not care about.
     *
     * @param list<mixed> $calls
     * @param array<int, mixed> $returnMap return value keyed by the first argument
     */
    private static function mapRecorder(
        array &$calls,
        string $method,
        array $returnMap
    ): \Closure {
        return static function (...$arguments) use (&$calls, $method, $returnMap) {
            $calls[] = [$method, $arguments[0]];

            return $returnMap[$arguments[0]];
        };
    }

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

        $calls = [];
        $locationGatewayMock = $this->getLocationGatewayMock();
        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('getBasicNodeData')
            ->willReturnCallback(self::mapRecorder($calls, 'getBasicNodeData', [
                42 => ['contentobject_id' => 100, 'main_node_id' => 200],
                201 => ['contentobject_id' => 101, 'main_node_id' => 201],
                202 => ['contentobject_id' => 102, 'main_node_id' => 202],
            ]));
        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('getChildren')
            ->willReturnCallback(self::mapRecorder($calls, 'getChildren', [
                42 => [
                    ['node_id' => 201],
                    ['node_id' => 202],
                ],
                201 => [],
                202 => [],
            ]));
        $locationGatewayMock
            ->expects(self::exactly(2))
            ->method('countLocationsByContentId')
            ->willReturnCallback(self::mapRecorder($calls, 'countLocationsByContentId', [101 => 1, 102 => 2]));
        $treeHandler
            ->expects(self::once())
            ->method('removeRawContent')
            ->with(101);
        $locationGatewayMock
            ->expects(self::once())
            ->method('getFallbackMainNodeData')
            ->with(102, 202)
            ->willReturnCallback(self::recorder($calls, 'getFallbackMainNodeData', [
                'node_id' => 203,
                'contentobject_version' => 1,
                'parent_node_id' => 204,
            ]));
        $treeHandler
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(102, 203);
        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('removeLocation')
            ->willReturnCallback(self::recorder($calls, 'removeLocation', null, 1));
        $locationGatewayMock
            ->expects(self::exactly(3))
            ->method('deleteNodeAssignment')
            ->willReturnCallback(self::recorder($calls, 'deleteNodeAssignment', null, 1));

        // Start
        $treeHandler->removeSubtree(42);

        self::assertSame(
            [
                ['getBasicNodeData', 42],
                ['getChildren', 42],
                // First recursive call
                ['getBasicNodeData', 201],
                ['getChildren', 201],
                ['countLocationsByContentId', 101],
                ['removeLocation', 201],
                ['deleteNodeAssignment', 101],
                // Second recursive call
                ['getBasicNodeData', 202],
                ['getChildren', 202],
                ['countLocationsByContentId', 102],
                ['getFallbackMainNodeData', 102, 202],
                ['removeLocation', 202],
                ['deleteNodeAssignment', 102],
                // Continuation of the original call
                ['removeLocation', 42],
                ['deleteNodeAssignment', 100],
            ],
            $calls
        );
    }

    public function testSetSectionForSubtree()
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

    public function testChangeMainLocation()
    {
        $treeHandler = $this->getPartlyMockedTreeHandler(
            [
                'loadLocation',
                'setSectionForSubtree',
                'loadContentInfo',
            ]
        );

        $calls = [];
        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(self::mapRecorder($calls, 'loadLocation', [
                34 => new Location(['parentId' => 42]),
                42 => new Location(['contentId' => 84]),
            ]));

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(self::mapRecorder($calls, 'loadContentInfo', [
                12 => new ContentInfo(['currentVersionNo' => 1]),
                84 => new ContentInfo(['sectionId' => 4]),
            ]));

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 42);

        $treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(34, 4);

        $treeHandler->changeMainLocation(12, 34);

        self::assertSame(
            [
                ['loadLocation', 34],
                ['loadContentInfo', 12],
                ['loadLocation', 42],
                ['loadContentInfo', 84],
            ],
            $calls
        );
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

        $calls = [];
        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadLocation')
            ->willReturnCallback(self::mapRecorder($calls, 'loadLocation', [
                34 => new Location(['parentId' => 1]),
                1 => new Location(['contentId' => 84]),
            ]));

        $treeHandler
            ->expects(self::exactly(2))
            ->method('loadContentInfo')
            ->willReturnCallback(self::mapRecorder($calls, 'loadContentInfo', [
                12 => new ContentInfo(['currentVersionNo' => 1]),
                84 => new ContentInfo(['sectionId' => 4]),
            ]));

        $this->getLocationGatewayMock()
            ->expects(self::once())
            ->method('changeMainLocation')
            ->with(12, 34, 1, 1);

        $treeHandler
            ->expects(self::once())
            ->method('setSectionForSubtree')
            ->with(34, 4);

        $treeHandler->changeMainLocation(12, 34);

        self::assertSame(
            [
                ['loadLocation', 34],
                ['loadContentInfo', 12],
                ['loadLocation', 1],
                ['loadContentInfo', 84],
            ],
            $calls
        );
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
