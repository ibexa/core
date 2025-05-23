<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\Content\Section\Gateway;

use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Section\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Content\Section\Gateway\DoctrineDatabase::insertSection
 */
class DoctrineDatabaseTest extends TestCase
{
    /**
     * Database gateway to test.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Content\Section\Gateway\DoctrineDatabase
     */
    protected $databaseGateway;

    /**
     * Inserts DB fixture.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/sections.php'
        );
    }

    public function testInsertSection()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->insertSection('New Section', 'new_section');
        $query = $this->getDatabaseConnection()->createQueryBuilder();

        $this->assertQueryResult(
            [
                [
                    'id' => '7',
                    'identifier' => 'new_section',
                    'name' => 'New Section',
                    'locale' => '',
                ],
            ],
            $query
                ->select('id', 'identifier', 'name', 'locale')
                ->from(Gateway::CONTENT_SECTION_TABLE)
                ->where(
                    $query->expr()->eq(
                        'identifier',
                        $query->createPositionalParameter('new_section')
                    )
                )
        );
    }

    public function testUpdateSection()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->updateSection(2, 'New Section', 'new_section');

        $this->assertQueryResult(
            [
                [
                    'id' => '2',
                    'identifier' => 'new_section',
                    'name' => 'New Section',
                    'locale' => '',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('id', 'identifier', 'name', 'locale')
                ->from(Gateway::CONTENT_SECTION_TABLE)
                ->where('id=2')
        );
    }

    public function testLoadSectionData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadSectionData(2);

        self::assertEquals(
            [
                [
                    'id' => '2',
                    'identifier' => 'users',
                    'name' => 'Users',
                ],
            ],
            $result
        );
    }

    public function testLoadAllSectionData()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadAllSectionData();

        $expected = [
            [
                'id' => '1',
                'identifier' => 'standard',
                'name' => 'Standard',
            ],

            [
                'id' => '2',
                'identifier' => 'users',
                'name' => 'Users',
            ],

            [
                'id' => '3',
                'identifier' => 'media',
                'name' => 'Media',
            ],

            [
                'id' => '4',
                'identifier' => 'setup',
                'name' => 'Setup',
            ],

            [
                'id' => '5',
                'identifier' => 'design',
                'name' => 'Design',
            ],

            [
                'id' => '6',
                'identifier' => '',
                'name' => 'Restricted',
            ],
        ];
        self::assertEquals(
            $expected,
            $result
        );
    }

    public function testLoadSectionDataByIdentifier()
    {
        $gateway = $this->getDatabaseGateway();

        $result = $gateway->loadSectionDataByIdentifier('users');

        self::assertEquals(
            [
                [
                    'id' => '2',
                    'identifier' => 'users',
                    'name' => 'Users',
                ],
            ],
            $result
        );
    }

    public function testCountContentObjectsInSection()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $result = $gateway->countContentObjectsInSection(2);

        self::assertSame(
            7,
            $result
        );
    }

    public function testCountRoleAssignmentsUsingSection()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../../../User/_fixtures/roles.php'
        );

        $gateway = $this->getDatabaseGateway();

        $result = $gateway->countRoleAssignmentsUsingSection(2);

        self::assertSame(
            1,
            $result
        );
    }

    public function testDeleteSection()
    {
        $gateway = $this->getDatabaseGateway();

        $gateway->deleteSection(2);

        $this->assertQueryResult(
            [
                [
                    'count' => '5',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from(Gateway::CONTENT_SECTION_TABLE)
        );

        $this->assertQueryResult(
            [
                [
                    'count' => '0',
                ],
            ],
            $this->getDatabaseConnection()->createQueryBuilder()
                ->select('COUNT( * ) AS count')
                ->from(Gateway::CONTENT_SECTION_TABLE)
                ->where('id=2')
        );
    }

    /**
     * @depends testCountContentObjectsInSection
     */
    public function testAssignSectionToContent()
    {
        $this->insertDatabaseFixture(
            __DIR__ . '/../../_fixtures/contentobjects.php'
        );

        $gateway = $this->getDatabaseGateway();

        $beforeCount = $gateway->countContentObjectsInSection(4);

        $result = $gateway->assignSectionToContent(4, 10);

        self::assertSame(
            $beforeCount + 1,
            $gateway->countContentObjectsInSection(4)
        );
    }

    /**
     * Returns a ready to test DoctrineDatabase gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Section\Gateway\DoctrineDatabase
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getDatabaseGateway(): Gateway
    {
        if (!isset($this->databaseGateway)) {
            $this->databaseGateway = new DoctrineDatabase($this->getDatabaseConnection());
        }

        return $this->databaseGateway;
    }
}
