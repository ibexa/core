<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTrashTest extends LanguageAwareTestCase
{
    protected function getLocationGateway()
    {
        return new DoctrineDatabase(
            $this->getDatabaseConnection(),
            $this->getLanguageMaskGenerator(),
            $this->getTrashCriteriaConverterDependency(),
            $this->getTrashSortClauseConverterDependency()
        );
    }

    /**
     * @todo test updated content status
     */
    public function testTrashLocation()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [
                [1, 0],
                [2, 0],
                [69, 0],
                [70, 0],
            ],
            $query
                ->select('node_id', 'priority')
                ->from(Gateway::CONTENT_TREE_TABLE)
                ->where($query->expr()->in('node_id', [1, 2, 69, 70, 71]))
        );
    }

    public function testTrashLocationUpdateTrashTable()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [
                [71, '/1/2/69/70/71/'],
            ],
            $query
                ->select('node_id', 'path_string')
                ->from(Gateway::TRASH_TABLE)
        );
    }

    public static function getUntrashedLocationValues()
    {
        return [
            ['contentobject_is_published', 1],
            ['contentobject_version', 1],
            ['depth', 4],
            ['is_hidden', 0],
            ['is_invisible', 0],
            ['main_node_id', 228],
            ['node_id', 228],
            ['parent_node_id', 70],
            ['path_identification_string', ''],
            ['path_string', '/1/2/69/70/228/'],
            ['priority', 0],
            ['remote_id', '087adb763245e0cdcac593fb4a5996cf'],
            ['sort_field', 1],
            ['sort_order', 1],
        ];
    }

    /**
     * @dataProvider getUntrashedLocationValues
     */
    public function testUntrashLocationDefault($property, $value)
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [[$value]],
            $query
                ->select($property)
                ->from(Gateway::CONTENT_TREE_TABLE)
                ->where($query->expr()->in('contentobject_id', [69]))
        );
    }

    public function testUntrashLocationNewParent()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71, 1);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [['228', '1', '/1/228/']],
            $query
                ->select('node_id', 'parent_node_id', 'path_string')
                ->from(Gateway::CONTENT_TREE_TABLE)
                ->where($query->expr()->in('contentobject_id', [69]))
        );
    }

    public function testUntrashInvalidLocation()
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();

        $handler->untrashLocation(23);
    }

    public function testUntrashLocationInvalidParent()
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71, 1337);
    }

    public function testUntrashLocationInvalidOldParent()
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);
        $handler->trashLocation(70);

        $handler->untrashLocation(70);
        $handler->untrashLocation(71);
    }

    public static function getLoadTrashValues()
    {
        return [
            ['node_id', 71],
            ['priority', 0],
            ['is_hidden', 0],
            ['is_invisible', 0],
            ['remote_id', '087adb763245e0cdcac593fb4a5996cf'],
            ['contentobject_id', 69],
            ['parent_node_id', 70],
            ['path_identification_string', 'products/software/os_type_i'],
            ['path_string', '/1/2/69/70/71/'],
            ['modified_subnode', 1311065013],
            ['main_node_id', 71],
            ['depth', 4],
            ['sort_field', 1],
            ['sort_order', 1],
        ];
    }

    /**
     * @dataProvider getLoadTrashValues
     */
    public function testLoadTrashByLocationId($field, $value)
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $data = $handler->loadTrashByLocation(71);

        self::assertEquals(
            $value,
            $data[$field],
            "Value in property $field not as expected."
        );
    }

    public function testCountTrashed()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();

        self::assertEquals(
            0,
            $handler->countTrashed()
        );

        $this->trashSubtree();

        self::assertEquals(
            8,
            $handler->countTrashed()
        );
    }

    public function testListEmptyTrash()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();

        self::assertEquals(
            [],
            $handler->listTrashed(0, null, [])
        );
    }

    protected function trashSubtree()
    {
        $handler = $this->getLocationGateway();
        $handler->trashLocation(69);
        $handler->trashLocation(70);
        $handler->trashLocation(71);
        $handler->trashLocation(72);
        $handler->trashLocation(73);
        $handler->trashLocation(74);
        $handler->trashLocation(75);
        $handler->trashLocation(76);
    }

    public function testListFullTrash()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertCount(
            8,
            $handler->listTrashed(0, null, [])
        );
    }

    public function testListTrashLimited()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertCount(
            5,
            $handler->listTrashed(0, 5, [])
        );
    }

    public static function getTrashValues()
    {
        return [
            ['contentobject_id', 67],
            ['contentobject_version', 1],
            ['depth', 2],
            ['is_hidden', 0],
            ['is_invisible', 0],
            ['main_node_id', 69],
            ['modified_subnode', 1311065014],
            ['node_id', 69],
            ['parent_node_id', 2],
            ['path_identification_string', 'products'],
            ['path_string', '/1/2/69/'],
            ['priority', 0],
            ['remote_id', '9cec85d730eec7578190ee95ce5a36f5'],
            ['sort_field', 2],
            ['sort_order', 1],
        ];
    }

    /**
     * @dataProvider getTrashValues
     */
    public function testListTrashItem($key, $value)
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        $trashList = $handler->listTrashed(0, 1, []);
        self::assertEquals($value, $trashList[0][$key]);
    }

    public function testListTrashSortedPathStringDesc()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertEquals(
            [
                '/1/2/69/76/',
                '/1/2/69/72/75/',
                '/1/2/69/72/74/',
                '/1/2/69/72/73/',
                '/1/2/69/72/',
                '/1/2/69/70/71/',
                '/1/2/69/70/',
                '/1/2/69/',
            ],
            array_map(
                static function ($trashItem) {
                    return $trashItem['path_string'];
                },
                $trashList = $handler->listTrashed(
                    0,
                    null,
                    [
                        new SortClause\Location\Path(Query::SORT_DESC),
                    ]
                )
            )
        );
    }

    public function testListTrashSortedDepth()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertEquals(
            [
                '/1/2/69/',
                '/1/2/69/76/',
                '/1/2/69/72/',
                '/1/2/69/70/',
                '/1/2/69/72/75/',
                '/1/2/69/72/74/',
                '/1/2/69/72/73/',
                '/1/2/69/70/71/',
            ],
            array_map(
                static function ($trashItem) {
                    return $trashItem['path_string'];
                },
                $trashList = $handler->listTrashed(
                    0,
                    null,
                    [
                        new SortClause\Location\Depth(),
                        new SortClause\Location\Path(Query::SORT_DESC),
                    ]
                )
            )
        );
    }

    public function testCleanupTrash()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();
        $handler->cleanupTrash();

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [],
            $query
                ->select('*')
                ->from(Gateway::TRASH_TABLE)
        );
    }

    public function testRemoveElementFromTrash()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();
        $this->trashSubtree();
        $handler->removeElementFromTrash(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $this->assertQueryResult(
            [],
            $query
                ->select('*')
                ->from(Gateway::TRASH_TABLE)
                ->where($query->expr()->eq('node_id', 71))
        );
    }

    public function testCountLocationsByContentId()
    {
        $this->insertDatabaseFixture(__DIR__ . '/_fixtures/full_example_tree.php');
        $handler = $this->getLocationGateway();

        self::assertSame(0, $handler->countLocationsByContentId(123456789));
        self::assertSame(1, $handler->countLocationsByContentId(67));

        // Insert a new node and count again
        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $query
            ->insert(Gateway::CONTENT_TREE_TABLE)
            ->values(
                [
                    'contentobject_id' => $query->createPositionalParameter(
                        67,
                        ParameterType::INTEGER
                    ),
                    'contentobject_version' => $query->createPositionalParameter(
                        1,
                        ParameterType::INTEGER
                    ),
                    'path_string' => $query->createPositionalParameter(
                        '/1/2/96',
                        ParameterType::INTEGER
                    ),
                    'parent_node_id' => $query->createPositionalParameter(
                        96,
                        ParameterType::INTEGER
                    ),
                    'remote_id' => $query->createPositionalParameter(
                        'some_remote_id',
                        ParameterType::STRING
                    ),
                ]
            );
        $query->executeStatement();
        self::assertSame(2, $handler->countLocationsByContentId(67));
    }
}
