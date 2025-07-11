<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\UserPreference\Gateway;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\UserPreference\UserPreferenceSetStruct;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway;
use Ibexa\Core\Persistence\Legacy\UserPreference\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\UserPreference\Gateway
 */
class DoctrineDatabaseTest extends TestCase
{
    public const EXISTING_USER_PREFERENCE_ID = 1;
    public const EXISTING_USER_PREFERENCE_DATA = [
        'id' => 1,
        'user_id' => 14,
        'name' => 'timezone',
        'value' => 'America/New_York',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/user_preferences.php'
        );
    }

    public function testInsert()
    {
        $id = $this->getGateway()->setUserPreference(new UserPreferenceSetStruct([
            'userId' => 14,
            'name' => 'setting_3',
            'value' => 'value_3',
        ]));

        $data = $this->loadUserPreference($id);

        self::assertEquals([
            'id' => $id,
            'user_id' => '14',
            'name' => 'setting_3',
            'value' => 'value_3',
        ], $data);
    }

    public function testUpdateUserPreference()
    {
        $userPreference = new UserPreferenceSetStruct([
            'userId' => 14,
            'name' => 'timezone',
            'value' => 'Europe/Warsaw',
        ]);

        $this->getGateway()->setUserPreference($userPreference);

        self::assertEquals([
            'id' => (string) self::EXISTING_USER_PREFERENCE_ID,
            'user_id' => '14',
            'name' => 'timezone',
            'value' => 'Europe/Warsaw',
        ], $this->loadUserPreference(self::EXISTING_USER_PREFERENCE_ID));
    }

    public function testCountUserPreferences()
    {
        self::assertEquals(3, $this->getGateway()->countUserPreferences(
            self::EXISTING_USER_PREFERENCE_DATA['user_id']
        ));
    }

    public function testLoadUserPreferences()
    {
        $userId = 14;
        $offset = 1;
        $limit = 2;

        $results = $this->getGateway()->loadUserPreferences($userId, $offset, $limit);

        self::assertEquals([
            [
                'id' => '2',
                'user_id' => '14',
                'name' => 'setting_1',
                'value' => 'value_1',
            ],
            [
                'id' => '3',
                'user_id' => '14',
                'name' => 'setting_2',
                'value' => 'value_2',
            ],
        ], $results);
    }

    /**
     * Return a ready to test DoctrineStorage gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\UserPreference\Gateway
     */
    protected function getGateway(): Gateway
    {
        return new DoctrineDatabase(
            $this->getDatabaseConnection()
        );
    }

    /**
     * @param int $id
     *
     * @return array
     */
    private function loadUserPreference(int $id): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('id', 'user_id', 'name', 'value')
            ->from(DoctrineDatabase::TABLE_USER_PREFERENCES, 'p')
            ->where(
                $queryBuilder->expr()->eq(
                    'p.id',
                    $queryBuilder->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        return reset($result);
    }
}
