<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Contracts\Core\Persistence\Content\Location\CreateStruct;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\Content\LanguageAwareTestCase;
use ReflectionObject;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Location\Gateway\DoctrineDatabase
 */
class DoctrineDatabaseTest extends LanguageAwareTestCase
{
    private const string NODE_IDS_PARAM_NAME = 'node_ids';

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
     * @return array<string, int|string>
     */
    private static function getLoadLocationValues(): array
    {
        return [
            'node_id' => 77,
            'priority' => 0,
            'is_hidden' => 0,
            'is_invisible' => 0,
            'remote_id' => 'dbc2f3c8716c12f32c379dbf0b1cb133',
            'contentobject_id' => 75,
            'parent_node_id' => 2,
            'path_identification_string' => 'solutions',
            'path_string' => '/1/2/77/',
            'modified_subnode' => 1311065017,
            'main_node_id' => 77,
            'depth' => 2,
            'sort_field' => 2,
            'sort_order' => 1,
        ];
    }

    /**
     * @param array<string, int|string> $locationData
     */
    private function assertLoadLocationProperties(array $locationData): void
    {
        foreach (self::getLoadLocationValues() as $field => $expectedValue) {
            self::assertEquals(
                $expectedValue,
                $locationData[$field],
                "Value in property $field not as expected."
            );
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadLocationByRemoteId(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $data = $gateway->getBasicNodeDataByRemoteId('dbc2f3c8716c12f32c379dbf0b1cb133');

        $this->assertLoadLocationProperties($data);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $data = $gateway->getBasicNodeData(77);

        $this->assertLoadLocationProperties($data);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadLocationList(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $locationsData = iterator_to_array($gateway->getNodeDataList([77]));

        self::assertCount(1, $locationsData);

        $locationRow = reset($locationsData);

        $this->assertLoadLocationProperties($locationRow);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadInvalidLocation(): void
    {
        $this->expectException(NotFoundException::class);

        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->getBasicNodeData(1337);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadLocationDataByContent(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();

        $locationsData = $gateway->loadLocationDataByContent(75);

        self::assertCount(1, $locationsData);

        $locationRow = reset($locationsData);

        $this->assertLoadLocationProperties($locationRow);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadParentLocationDataForDraftContentAll(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();

        $locationsData = $gateway->loadParentLocationsDataForDraftContent(226);

        self::assertCount(1, $locationsData);

        $locationRow = reset($locationsData);

        $this->assertLoadLocationProperties($locationRow);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadLocationDataByContentLimitSubtree(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();

        $locationsData = $gateway->loadLocationDataByContent(75, 3);

        self::assertCount(0, $locationsData);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testMoveSubtreePathUpdate(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->moveSubtreeNodes(
            [
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
                'path_identification_string' => 'products',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ],
            [
                'path_string' => '/1/2/77/',
                'path_identification_string' => 'solutions',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [65, '/1/2/', '', 1, 1, 0, 0],
                [67, '/1/2/77/69/', 'solutions/products', 77, 3, 0, 0],
                [69, '/1/2/77/69/70/71/', 'solutions/products/software/os_type_i', 70, 5, 0, 0],
                [73, '/1/2/77/69/72/75/', 'solutions/products/boxes/cd_dvd_box_iii', 72, 5, 0, 0],
                [75, '/1/2/77/', 'solutions', 2, 2, 0, 0],
            ],
            $query
                ->select(
                    'contentobject_id',
                    'path_string',
                    'path_identification_string',
                    'parent_node_id',
                    'depth',
                    'is_hidden',
                    'is_invisible'
                )
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [69, 71, 75, 77, 2],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('contentobject_id')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testMoveHiddenDestinationUpdate(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/77/');
        $gateway->moveSubtreeNodes(
            [
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
                'path_identification_string' => 'products',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ],
            [
                'path_string' => '/1/2/77/',
                'path_identification_string' => 'solutions',
                'is_hidden' => 1,
                'is_invisible' => 1,
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [65, '/1/2/', '', 1, 1, 0, 0],
                [67, '/1/2/77/69/', 'solutions/products', 77, 3, 0, 1],
                [69, '/1/2/77/69/70/71/', 'solutions/products/software/os_type_i', 70, 5, 0, 1],
                [73, '/1/2/77/69/72/75/', 'solutions/products/boxes/cd_dvd_box_iii', 72, 5, 0, 1],
                [75, '/1/2/77/', 'solutions', 2, 2, 1, 1],
            ],
            $query
                ->select(
                    'contentobject_id',
                    'path_string',
                    'path_identification_string',
                    'parent_node_id',
                    'depth',
                    'is_hidden',
                    'is_invisible'
                )
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [69, 71, 75, 77, 2],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('contentobject_id')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testMoveHiddenSourceUpdate(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/');
        $gateway->moveSubtreeNodes(
            [
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
                'path_identification_string' => 'products',
                'is_hidden' => 1,
                'is_invisible' => 1,
            ],
            [
                'path_string' => '/1/2/77/',
                'path_identification_string' => 'solutions',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [65, '/1/2/', '', 1, 1, 0, 0],
                [67, '/1/2/77/69/', 'solutions/products', 77, 3, 1, 1],
                [69, '/1/2/77/69/70/71/', 'solutions/products/software/os_type_i', 70, 5, 0, 1],
                [73, '/1/2/77/69/72/75/', 'solutions/products/boxes/cd_dvd_box_iii', 72, 5, 0, 1],
                [75, '/1/2/77/', 'solutions', 2, 2, 0, 0],
            ],
            $query
                ->select(
                    'contentobject_id',
                    'path_string',
                    'path_identification_string',
                    'parent_node_id',
                    'depth',
                    'is_hidden',
                    'is_invisible'
                )
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [69, 71, 75, 77, 2],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('contentobject_id')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testMoveHiddenSourceChildUpdate(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/70/');

        $gateway->moveSubtreeNodes(
            [
                'path_string' => '/1/2/69/',
                'contentobject_id' => 67,
                'path_identification_string' => 'products',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ],
            [
                'path_string' => '/1/2/77/',
                'path_identification_string' => 'solutions',
                'is_hidden' => 0,
                'is_invisible' => 0,
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [65, '/1/2/', '', 1, 1, 0, 0],
                [67, '/1/2/77/69/', 'solutions/products', 77, 3, 0, 0],
                [68, '/1/2/77/69/70/', 'solutions/products/software', 69, 4, 1, 1],
                [69, '/1/2/77/69/70/71/', 'solutions/products/software/os_type_i', 70, 5, 0, 1],
                [73, '/1/2/77/69/72/75/', 'solutions/products/boxes/cd_dvd_box_iii', 72, 5, 0, 0],
                [75, '/1/2/77/', 'solutions', 2, 2, 0, 0],
            ],
            $query
                ->select(
                    'contentobject_id',
                    'path_string',
                    'path_identification_string',
                    'parent_node_id',
                    'depth',
                    'is_hidden',
                    'is_invisible'
                )
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [69, 70, 71, 75, 77, 2],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('contentobject_id')
        );
    }

    /**
     * @throws \Exception
     */
    public function testMoveSubtreeAssignmentUpdate(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->updateNodeAssignment(67, 2, 77, 5);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [67, 1, 0, 53, 1, 5, 77, '9cec85d730eec7578190ee95ce5a36f5', 0, 2, 1, 0, 0],
            ],
            $query
                ->select(
                    'contentobject_id',
                    'contentobject_version',
                    'from_node_id',
                    'id',
                    'is_main',
                    'op_code',
                    'parent_node',
                    'parent_remote_id',
                    'remote_id',
                    'sort_field',
                    'sort_order',
                    'priority',
                    'is_hidden'
                )
                ->from('eznode_assignment')
                ->where($query->expr()->eq('contentobject_id', 67))
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testHideUpdateHidden(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/');

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [1, 0, 0],
                [2, 0, 0],
                [69, 1, 1],
                [75, 0, 1],
            ],
            $query
                ->select('node_id', 'is_hidden', 'is_invisible')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter([1, 2, 69, 75], ArrayParameterType::INTEGER, ':' . self::NODE_IDS_PARAM_NAME)
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @depends testHideUpdateHidden
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testHideUnhideUpdateHidden(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/');
        $gateway->unhideSubtree('/1/2/69/');

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [1, 0, 0],
                [2, 0, 0],
                [69, 0, 0],
                [75, 0, 0],
            ],
            $query
                ->select('node_id', 'is_hidden', 'is_invisible')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [1, 2, 69, 75],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @depends testHideUpdateHidden
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testHideUnhideParentTree(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/');
        $gateway->hideSubtree('/1/2/69/70/');
        $gateway->unhideSubtree('/1/2/69/');

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [1, 0, 0],
                [2, 0, 0],
                [69, 0, 0],
                [70, 1, 1],
                [71, 0, 1],
                [75, 0, 0],
            ],
            $query
                ->select('node_id', 'is_hidden', 'is_invisible')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter(
                            [1, 2, 69, 70, 71, 75],
                            ArrayParameterType::INTEGER,
                            ':' . self::NODE_IDS_PARAM_NAME
                        )
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @depends testHideUpdateHidden
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testHideUnhidePartialSubtree(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->hideSubtree('/1/2/69/');
        $gateway->hideSubtree('/1/2/69/70/');
        $gateway->unhideSubtree('/1/2/69/70/');

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [1, 0, 0],
                [2, 0, 0],
                [69, 1, 1],
                [70, 0, 1],
                [71, 0, 1],
                [75, 0, 1],
            ],
            $query
                ->select('node_id', 'is_hidden', 'is_invisible')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter([1, 2, 69, 70, 71, 75], ArrayParameterType::INTEGER, ':node_ids')
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testSwapLocations(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->swap(70, 78);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [70, 76],
                [78, 68],
            ],
            $query
                ->select('node_id', 'contentobject_id')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter([70, 78], ArrayParameterType::INTEGER, 'node_ids')
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCreateLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'remoteId' => 'some_id',
                ]
            ),
            [
                'node_id' => '77',
                'depth' => '2',
                'path_string' => '/1/2/77/',
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [70, '/1/2/69/70/'],
                [77, '/1/2/77/'],
                [228, '/1/2/77/228/'],
            ],
            $query
                ->select('node_id', 'path_string')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'contentobject_id',
                        $query->createNamedParameter(
                            [68, 75],
                            ArrayParameterType::INTEGER,
                            ':contentobject_ids'
                        )
                    )
                )
                ->orderBy('node_id')
        );
    }

    /**
     * @depends testCreateLocation
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \ReflectionException
     */
    public function testGetMainNodeId(): void
    {
        $gateway = $this->getLocationGateway();

        $parentLocationData = [
            'node_id' => '77',
            'depth' => '2',
            'path_string' => '/1/2/77/',
        ];

        // main location
        $mainLocation = $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'remoteId' => 'some_id',
                    'mainLocationId' => true,
                ]
            ),
            $parentLocationData
        );

        // secondary location
        $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'remoteId' => 'some_id',
                    'mainLocationId' => $mainLocation->id,
                ]
            ),
            $parentLocationData
        );

        $gatewayReflection = new ReflectionObject($gateway);
        $methodReflection = $gatewayReflection->getMethod('getMainNodeId');
        $methodReflection->setAccessible(true);
        self::assertEquals($mainLocation->id, $methodReflection->invoke($gateway, 68));
    }

    /**
     * @return array<array{string, int|string}>
     */
    public static function getCreateLocationValues(): array
    {
        return [
            ['contentobject_id', 68],
            ['contentobject_is_published', 1],
            ['contentobject_version', 1],
            ['depth', 3],
            ['is_hidden', 0],
            ['is_invisible', 0],
            ['main_node_id', 42],
            ['parent_node_id', 77],
            ['path_identification_string', ''],
            ['priority', 1],
            ['remote_id', 'some_id'],
            ['sort_field', 1],
            ['sort_order', 1],
        ];
    }

    /**
     * @depends      testCreateLocation
     *
     * @dataProvider getCreateLocationValues
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCreateLocationValues(string $field, int|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => 42,
                    'priority' => 1,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                ]
            ),
            [
                'node_id' => '77',
                'depth' => '2',
                'path_string' => '/1/2/77/',
            ]
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[$value]],
            $query
                ->select($field)
                ->from('ezcontentobject_tree')
                ->where($query->expr()->eq('node_id', 228))
        );
    }

    /**
     * @return iterable<array{string, int|string|bool}>
     */
    public static function getCreateLocationReturnValues(): iterable
    {
        yield ['id', 228];
        yield ['priority', 1];
        yield ['hidden', false];
        yield ['invisible', false];
        yield ['remoteId', 'some_id'];
        yield ['contentId', '68'];
        yield ['parentId', '77'];
        yield ['pathString', '/1/2/77/228/'];
        yield ['depth', 3];
        yield ['sortField', 1];
        yield ['sortOrder', 1];
    }

    /**
     * @depends      testCreateLocation
     *
     * @dataProvider getCreateLocationReturnValues
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCreateLocationReturnValues(string $field, int|bool|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $location = $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => true,
                    'priority' => 1,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                ]
            ),
            [
                'node_id' => '77',
                'depth' => '2',
                'path_string' => '/1/2/77/',
            ]
        );

        self::assertEquals($value, $location->$field);
    }

    /**
     * @return iterable<array{string, int|string}>
     */
    public static function getUpdateLocationData(): iterable
    {
        yield ['priority', 23];
        yield ['remote_id', 'someNewHash'];
        yield ['sort_field', 4];
        yield ['sort_order', 4];
    }

    /**
     * @dataProvider getUpdateLocationData
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateLocation(string $field, int|string $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->update(
            new Location\UpdateStruct(
                [
                    'priority' => 23,
                    'remoteId' => 'someNewHash',
                    'sortField' => 4,
                    'sortOrder' => 4,
                ]
            ),
            70
        );

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[$value]],
            $query
                ->select($field)
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->in(
                        'node_id',
                        $query->createNamedParameter([70], ArrayParameterType::INTEGER, ':' . self::NODE_IDS_PARAM_NAME)
                    )
                )
        );
    }

    /**
     * @return iterable<array{string, list<int|string>}>
     */
    public static function getNodeAssignmentValues(): iterable
    {
        yield ['contentobject_version', [1]];
        yield ['from_node_id', [0]];
        yield ['id', [215]];
        yield ['is_main', [0]];
        yield ['op_code', [3]];
        yield ['parent_node', [77]];
        yield ['parent_remote_id', ['some_id']];
        yield ['remote_id', ['0']];
        yield ['sort_field', [2]];
        yield ['sort_order', [0]];
        yield ['priority', [1]];
        yield ['is_hidden', [1]];
    }

    /**
     * @param string[] $fields
     */
    private function buildGenericNodeSelectContentWithParentQuery(
        int $contentId,
        int $parentLocationId,
        string $nodeTable,
        string $parentNodeIdColumnName,
        array $fields
    ): QueryBuilder {
        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(...$fields)
            ->from($nodeTable)
            ->where(
                $expr->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    $parentNodeIdColumnName,
                    $query->createPositionalParameter($parentLocationId, ParameterType::INTEGER)
                )
            );

        return $query;
    }

    /**
     * @param string[] $fields
     */
    private function buildNodeAssignmentSelectContentWithParentQuery(
        int $contentId,
        int $parentLocationId,
        array $fields
    ): QueryBuilder {
        return $this->buildGenericNodeSelectContentWithParentQuery(
            $contentId,
            $parentLocationId,
            'eznode_assignment',
            'parent_node',
            $fields
        );
    }

    /**
     * @param string[] $fields
     */
    private function buildContentTreeSelectContentWithParentQuery(
        int $contentId,
        int $parentLocationId,
        array $fields
    ): QueryBuilder {
        return $this->buildGenericNodeSelectContentWithParentQuery(
            $contentId,
            $parentLocationId,
            Gateway::CONTENT_TREE_TABLE,
            'parent_node_id',
            $fields
        );
    }

    /**
     * @depends      testCreateLocation
     *
     * @dataProvider getNodeAssignmentValues
     *
     * @param string $field
     * @param array<int|string> $expectedResult
     */
    public function testCreateLocationNodeAssignmentCreation(string $field, array $expectedResult): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => 1,
                    'priority' => 1,
                    'remoteId' => 'some_id',
                    'sortField' => 2,
                    'sortOrder' => 0,
                    'hidden' => 1,
                ]
            ),
            77,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        self::assertQueryResult(
            [$expectedResult],
            $this->buildNodeAssignmentSelectContentWithParentQuery(68, 77, [$field])
        );
    }

    /**
     * @depends testCreateLocation
     */
    public function testCreateLocationNodeAssignmentCreationMainLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => true,
                    'priority' => 1,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                ]
            ),
            77,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        self::assertQueryResult(
            [[1]],
            $this->buildNodeAssignmentSelectContentWithParentQuery(68, 77, ['is_main'])
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateLocationsContentVersionNo(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();

        $gateway->create(
            new CreateStruct(
                [
                    'contentId' => 4096,
                    'remoteId' => 'some_id',
                    'contentVersion' => 1,
                ]
            ),
            [
                'node_id' => '77',
                'depth' => '2',
                'path_string' => '/1/2/77/',
            ]
        );

        $gateway->updateLocationsContentVersionNo(4096, 2);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [
                [2],
            ],
            $query->select(
                'contentobject_version'
            )->from(
                'ezcontentobject_tree'
            )->where(
                $query->expr()->eq(
                    'contentobject_id',
                    4096
                )
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDeleteNodeAssignment(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();

        $gateway->deleteNodeAssignment(11);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[0]],
            $query
                ->select('count(*)')
                ->from('eznode_assignment')
                ->where(
                    $query->expr()->eq('contentobject_id', 11)
                )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDeleteNodeAssignmentWithSecondArgument(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        $gateway = $this->getLocationGateway();

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $query
            ->select('count(*)')
            ->from('eznode_assignment')
            ->where(
                $query->expr()->eq('contentobject_id', 11)
            );
        $statement = $query->executeQuery();
        $nodeAssignmentsCount = (int)$statement->fetchOne();

        $gateway->deleteNodeAssignment(11, 1);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[$nodeAssignmentsCount - 1]],
            $query
                ->select('count(*)')
                ->from('eznode_assignment')
                ->where(
                    $query->expr()->eq('contentobject_id', 11)
                )
        );
    }

    /**
     * @return iterable<array{string, int|string}>
     */
    public static function getConvertNodeAssignmentsLocationValues(): iterable
    {
        yield ['contentobject_id', '68'];
        yield ['contentobject_is_published', '1'];
        yield ['contentobject_version', '1'];
        yield ['depth', '3'];
        yield ['is_hidden', '1'];
        yield ['is_invisible', '1'];
        yield ['main_node_id', '70'];
        yield ['modified_subnode', time()];
        yield ['node_id', '228'];
        yield ['parent_node_id', '77'];
        yield ['path_string', '/1/2/77/228/'];
        yield ['priority', '101'];
        yield ['remote_id', 'some_id'];
        yield ['sort_field', '1'];
        yield ['sort_order', '1'];
    }

    /**
     * @depends      testCreateLocationNodeAssignmentCreation
     *
     * @dataProvider getConvertNodeAssignmentsLocationValues
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testConvertNodeAssignments(string $field, string|int $value): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => false,
                    'priority' => 101,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                    'hidden' => true,
                    // Note: not stored in node assignment, will be calculated from parent
                    // visibility upon Location creation from node assignment
                    'invisible' => false,
                ]
            ),
            77,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        $gateway->createLocationsFromNodeAssignments(68, 1);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select($field)
            ->from(Gateway::CONTENT_TREE_TABLE)
            ->where(
                $expr->eq(
                    'contentobject_id',
                    $query->createPositionalParameter(68, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'parent_node_id',
                    $query->createPositionalParameter(77, ParameterType::INTEGER)
                )
            );

        if ($field === 'modified_subnode') {
            $result = $query->executeQuery()->fetchAllAssociative();
            self::assertGreaterThanOrEqual($value, $result);
        } else {
            self::assertQueryResult(
                [[$value]],
                $query
            );
        }
    }

    /**
     * @depends testCreateLocationNodeAssignmentCreation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testConvertNodeAssignmentsMainLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => true,
                    'priority' => 101,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                    'hidden' => true,
                    // Note: not stored in node assignment, will be calculated from parent
                    // visibility upon Location creation from node assignment
                    'invisible' => false,
                ]
            ),
            77,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        $gateway->createLocationsFromNodeAssignments(68, 1);

        self::assertQueryResult(
            [[228]],
            $this->buildContentTreeSelectContentWithParentQuery(68, 77, ['main_node_id'])
        );
    }

    /**
     * @depends testCreateLocationNodeAssignmentCreation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testConvertNodeAssignmentsParentHidden(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => true,
                    'priority' => 101,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                    'hidden' => false,
                    // Note: not stored in node assignment, will be calculated from parent
                    // visibility upon Location creation from node assignment
                    'invisible' => false,
                ]
            ),
            224,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        $gateway->createLocationsFromNodeAssignments(68, 1);

        self::assertQueryResult(
            [[0, 1]],
            $this->buildContentTreeSelectContentWithParentQuery(
                68,
                224,
                ['is_hidden, is_invisible']
            )
        );
    }

    /**
     * @depends testCreateLocationNodeAssignmentCreation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testConvertNodeAssignmentsParentInvisible(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => true,
                    'priority' => 101,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                    'hidden' => false,
                    // Note: not stored in node assignment, will be calculated from parent
                    // visibility upon Location creation from node assignment
                    'invisible' => false,
                ]
            ),
            225,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        $gateway->createLocationsFromNodeAssignments(68, 1);

        self::assertQueryResult(
            [[0, 1]],
            $this->buildContentTreeSelectContentWithParentQuery(
                68,
                225,
                ['is_hidden, is_invisible']
            )
        );
    }

    /**
     * @depends testCreateLocationNodeAssignmentCreation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testConvertNodeAssignmentsUpdateAssignment(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->createNodeAssignment(
            new CreateStruct(
                [
                    'contentId' => 68,
                    'contentVersion' => 1,
                    'mainLocationId' => 1,
                    'priority' => 1,
                    'remoteId' => 'some_id',
                    'sortField' => 1,
                    'sortOrder' => 1,
                ]
            ),
            77,
            Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE
        );

        $gateway->createLocationsFromNodeAssignments(68, 1);

        self::assertQueryResult(
            [[Gateway::NODE_ASSIGNMENT_OP_CODE_CREATE_NOP]],
            $this->buildNodeAssignmentSelectContentWithParentQuery(68, 77, ['op_code'])
        );
    }

    /**
     * Test for the setSectionForSubtree() method.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testSetSectionForSubtree(): void
    {
        $this->insertDatabaseFixture(__DIR__ . '/../../_fixtures/contentobjects.php');
        $gateway = $this->getLocationGateway();
        $gateway->setSectionForSubtree('/1/2/69/70/', 23);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[68], [69]],
            $query
                ->select('id')
                ->from('ezcontentobject')
                ->where($query->expr()->eq('section_id', 23))
        );
    }

    /**
     * Test for the changeMainLocation() method.
     *
     *
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testChangeMainLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        // Create additional location and assignment for test purpose
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->insert('ezcontentobject_tree')
            ->values(
                [
                    'contentobject_id' => $query->createPositionalParameter(
                        10,
                        ParameterType::INTEGER
                    ),
                    'contentobject_version' => $query->createPositionalParameter(
                        2,
                        ParameterType::INTEGER
                    ),
                    'main_node_id' => $query->createPositionalParameter(15, ParameterType::INTEGER),
                    'node_id' => $query->createPositionalParameter(228, ParameterType::INTEGER),
                    'parent_node_id' => $query->createPositionalParameter(
                        227,
                        ParameterType::INTEGER
                    ),
                    'path_string' => $query->createPositionalParameter(
                        '/1/5/13/228/'
                    ),
                    'remote_id' => $query->createPositionalParameter(
                        'asdfg123437'
                    ),
                ]
            );
        $query->executeStatement();

        $query = $connection->createQueryBuilder();
        $query
            ->insert('eznode_assignment')
            ->values(
                [
                    'contentobject_id' => $query->createPositionalParameter(
                        10,
                        ParameterType::INTEGER
                    ),
                    'contentobject_version' => $query->createPositionalParameter(
                        2,
                        ParameterType::INTEGER
                    ),
                    'id' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'is_main' => $query->createPositionalParameter(0, ParameterType::INTEGER),
                    'parent_node' => $query->createPositionalParameter(227, ParameterType::INTEGER),
                    'parent_remote_id' => $query->createPositionalParameter(
                        '5238a276bf8231fbcf8a986cdc82a6a5'
                    ),
                ]
            );
        $query->executeStatement();

        $gateway = $this->getLocationGateway();

        $gateway->changeMainLocation(
            10, // content id
            228, // new main location id
            2, // content version number
            227 // new main location parent id
        );

        $query = $connection->createQueryBuilder();
        self::assertQueryResult(
            [[228], [228]],
            $query
                ->select('main_node_id')
                ->from('ezcontentobject_tree')
                ->where(
                    $query->expr()->eq(
                        'contentobject_id',
                        $query->createPositionalParameter(10, ParameterType::INTEGER)
                    )
                )
        );

        $query = $connection->createQueryBuilder();
        self::assertQueryResult(
            [[1]],
            $query
                ->select('is_main')
                ->from('eznode_assignment')
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter(10, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'contentobject_version',
                            $query->createPositionalParameter(2, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'parent_node',
                            $query->createPositionalParameter(227, ParameterType::INTEGER)
                        )
                    )
                )
        );

        $query = $connection->createQueryBuilder();
        self::assertQueryResult(
            [[0]],
            $query
                ->select('is_main')
                ->from('eznode_assignment')
                ->where(
                    $query->expr()->and(
                        $query->expr()->eq(
                            'contentobject_id',
                            $query->createPositionalParameter(10, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'contentobject_version',
                            $query->createPositionalParameter(2, ParameterType::INTEGER)
                        ),
                        $query->expr()->eq(
                            'parent_node',
                            $query->createPositionalParameter(44, ParameterType::INTEGER)
                        )
                    )
                )
        );
    }

    /**
     * Test for the getChildren() method.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function testGetChildren(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $childrenRows = $gateway->getChildren(213);

        self::assertCount(2, $childrenRows);
        self::assertCount(16, $childrenRows[0]);
        self::assertEquals(214, $childrenRows[0]['node_id']);
        self::assertCount(16, $childrenRows[1]);
        self::assertEquals(215, $childrenRows[1]['node_id']);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testGetFallbackMainNodeData(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);
        // Create additional location for test purpose
        $connection = $this->getDatabaseConnection();
        $query = $connection->createQueryBuilder();
        $query
            ->insert('ezcontentobject_tree')
            ->values(
                [
                    'contentobject_id' => $query->createPositionalParameter(
                        12,
                        ParameterType::INTEGER
                    ),
                    'contentobject_version' => $query->createPositionalParameter(
                        1,
                        ParameterType::INTEGER
                    ),
                    'main_node_id' => $query->createPositionalParameter(13, ParameterType::INTEGER),
                    'node_id' => $query->createPositionalParameter(228, ParameterType::INTEGER),
                    'parent_node_id' => $query->createPositionalParameter(
                        227,
                        ParameterType::INTEGER
                    ),
                    'path_string' => $query->createPositionalParameter(
                        '/1/5/13/228/'
                    ),
                    'remote_id' => $query->createPositionalParameter(
                        'asdfg123437'
                    ),
                ]
            );
        $query->executeStatement();

        $gateway = $this->getLocationGateway();
        $data = $gateway->getFallbackMainNodeData(12, 13);

        self::assertEquals(228, $data['node_id']);
        self::assertEquals(1, $data['contentobject_version']);
        self::assertEquals(227, $data['parent_node_id']);
    }

    /**
     * Test for the removeLocation() method.
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testRemoveLocation(): void
    {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->removeLocation(13);

        try {
            $gateway->getBasicNodeData(13);
            self::fail('Location was not deleted!');
        } catch (NotFoundException) {
            // Do nothing
        }
    }

    /**
     * @return iterable<array{int, int, string, string}>
     */
    public static function providerForTestUpdatePathIdentificationString(): iterable
    {
        yield [77, 2, 'new_solutions', 'new_solutions'];
        yield [75, 69, 'stylesheets', 'products/stylesheets'];
    }

    /**
     * @dataProvider providerForTestUpdatePathIdentificationString
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdatePathIdentificationString(
        int $locationId,
        int $parentLocationId,
        string $text,
        string $expected
    ): void {
        $this->insertDatabaseFixture(self::FIXTURE_PATH_FULL_EXAMPLE_TREE);

        $gateway = $this->getLocationGateway();
        $gateway->updatePathIdentificationString($locationId, $parentLocationId, $text);

        $query = $this->getDatabaseConnection()->createQueryBuilder();
        self::assertQueryResult(
            [[$expected]],
            $query->select(
                'path_identification_string'
            )->from(
                'ezcontentobject_tree'
            )->where(
                $query->expr()->eq('node_id', $locationId)
            )
        );
    }
}
