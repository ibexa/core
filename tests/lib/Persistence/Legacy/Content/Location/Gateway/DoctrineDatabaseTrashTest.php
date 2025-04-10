<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTrashTest extends LanguageAwareTestCase
{
    private const string PATH_STRING_OF_LOCATION_TO_BE_TRASHED = '/1/2/69/70/71/';
    private const string PATH_STRING_OF_TRASHED_LOCATION = '/1/2/69/';

    protected function getLocationGateway(): DoctrineDatabase
    {
        return new DoctrineDatabase(
            $this->getDatabaseConnection(),
            $this->getLanguageMaskGenerator(),
            $this->getTrashCriteriaConverterDependency(),
            $this->getTrashSortClauseConverterDependency()
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @todo test updated content status
     */
    public function testTrashLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [1, 0],
                [2, 0],
                [69, 0],
                [70, 0],
            ],
            $query
                ->select('node_id', 'priority')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [1, 2, 69, 70, 71],
                            ArrayParameterType::INTEGER,
                            ':node_ids'
                        )
                    )
                )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testTrashLocationUpdateTrashTable(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [71, self::PATH_STRING_OF_LOCATION_TO_BE_TRASHED],
            ],
            $query
                ->select('node_id', 'path_string')
                ->from('ezcontentobject_trash')
        );
    }

    /**
     * @phpstan-return list<array{string, int|string}>
     */
    public static function getUntrashedLocationValues(): array
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
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testUntrashLocationDefault(string $property, int|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[$value]],
            $query
                ->select($property)
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'contentobject_id',
                        $query->createNamedParameter(
                            [69],
                            ArrayParameterType::INTEGER,
                            ':contentobject_ids'
                        )
                    )
                )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUntrashLocationNewParent(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71, 1);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [['228', '1', '/1/228/']],
            $query
                ->select('node_id', 'parent_node_id', 'path_string')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'contentobject_id',
                        $query->createNamedParameter(
                            [69],
                            ArrayParameterType::INTEGER,
                            ':contentobject_ids'
                        )
                    )
                )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUntrashInvalidLocation(): void
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();

        $handler->untrashLocation(23);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUntrashLocationInvalidParent(): void
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $handler->untrashLocation(71, 1337);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUntrashLocationInvalidOldParent(): void
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);
        $handler->trashLocation(70);

        $handler->untrashLocation(70);
        $handler->untrashLocation(71);
    }

    /**
     * @phpstan-return list<array{string, int|string}>
     */
    public static function getLoadTrashValues(): array
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
            ['path_string', self::PATH_STRING_OF_LOCATION_TO_BE_TRASHED],
            ['modified_subnode', 1311065013],
            ['main_node_id', 71],
            ['depth', 4],
            ['sort_field', 1],
            ['sort_order', 1],
        ];
    }

    /**
     * @dataProvider getLoadTrashValues
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadTrashByLocationId(string $field, int|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $handler->trashLocation(71);

        $data = $handler->loadTrashByLocation(71);

        self::assertEquals(
            $value,
            $data[$field],
            "Value in property $field not as expected."
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCountTrashed(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
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

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testListEmptyTrash(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();

        self::assertEquals(
            [],
            $handler->listTrashed(0, null, [])
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    protected function trashSubtree(): void
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

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testListFullTrash(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertCount(
            8,
            $handler->listTrashed(0, null, [])
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testListTrashLimited(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertCount(
            5,
            $handler->listTrashed(0, 5, [])
        );
    }

    /**
     * @phpstan-return list<array{string, int|string}>
     */
    public static function getTrashValues(): array
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
            ['path_string', self::PATH_STRING_OF_TRASHED_LOCATION],
            ['priority', 0],
            ['remote_id', '9cec85d730eec7578190ee95ce5a36f5'],
            ['sort_field', 2],
            ['sort_order', 1],
        ];
    }

    /**
     * @dataProvider getTrashValues
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function testListTrashItem(string $key, int|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        $trashList = $handler->listTrashed(0, 1, []);
        self::assertEquals($value, $trashList[0][$key]);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testListTrashSortedPathStringDesc(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertEquals(
            [
                '/1/2/69/76/',
                '/1/2/69/72/75/',
                '/1/2/69/72/74/',
                '/1/2/69/72/73/',
                '/1/2/69/72/',
                self::PATH_STRING_OF_LOCATION_TO_BE_TRASHED,
                '/1/2/69/70/',
                self::PATH_STRING_OF_TRASHED_LOCATION,
            ],
            array_map(
                static function (array $trashItem) {
                    return $trashItem['path_string'];
                },
                $handler->listTrashed(
                    0,
                    null,
                    [
                        new SortClause\Location\Path(Query::SORT_DESC),
                    ]
                )
            )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testListTrashSortedDepth(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();

        self::assertEquals(
            [
                self::PATH_STRING_OF_TRASHED_LOCATION,
                '/1/2/69/76/',
                '/1/2/69/72/',
                '/1/2/69/70/',
                '/1/2/69/72/75/',
                '/1/2/69/72/74/',
                '/1/2/69/72/73/',
                self::PATH_STRING_OF_LOCATION_TO_BE_TRASHED,
            ],
            array_map(
                static function (array $trashItem) {
                    return $trashItem['path_string'];
                },
                $handler->listTrashed(
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

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCleanupTrash(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();
        $handler->cleanupTrash();

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [],
            $query
                ->select('*')
                ->from('ezcontentobject_trash')
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testRemoveElementFromTrash(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();
        $this->trashSubtree();
        $handler->removeElementFromTrash(71);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [],
            $query
                ->select('*')
                ->from('ezcontentobject_trash')
                ->where($query->expr()->eq('node_id', 71))
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCountLocationsByContentId(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $handler = $this->getLocationGateway();

        self::assertSame(0, $handler->countLocationsByContentId(123456789));
        self::assertSame(1, $handler->countLocationsByContentId(67));

        // Insert a new node and count again
        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $query
            ->insert('ezcontentobject_tree')
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
                        'some_remote_id'
                    ),
                ]
            );
        $query->executeStatement();
        self::assertSame(2, $handler->countLocationsByContentId(67));
    }
}
