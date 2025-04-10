<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Notification\Gateway;

use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase::insert
 */
class DoctrineDatabaseTest extends TestCase
{
    public const int EXISTING_NOTIFICATION_ID = 1;

    /** @var array<string, int|string|null> */
    public const array EXISTING_NOTIFICATION_DATA = [
        'id' => 1,
        'owner_id' => 14,
        'is_pending' => 1,
        'type' => 'Workflow:Review',
        'created' => 1529995052,
        'data' => null,
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->insertDatabaseFixture(
            __DIR__ . '/../_fixtures/notifications.php'
        );
    }

    /**
     * @throws \JsonException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testInsert(): void
    {
        $id = $this->getGateway()->insert(new CreateStruct([
            'ownerId' => 14,
            'isPending' => true,
            'type' => 'Workflow:Review',
            'created' => 1529995052,
            'data' => null,
        ]));

        $data = $this->loadNotification($id);

        self::assertEquals([
            'id' => $id,
            'owner_id' => '14',
            'is_pending' => 1,
            'type' => 'Workflow:Review',
            'created' => '1529995052',
            'data' => 'null',
        ], $data);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testGetNotificationById(): void
    {
        $data = $this->getGateway()->getNotificationById(self::EXISTING_NOTIFICATION_ID);

        self::assertEquals([
            self::EXISTING_NOTIFICATION_DATA,
        ], $data);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Doctrine\DBAL\Exception
     */
    public function testUpdateNotification(): void
    {
        $notification = new Notification([
            'id' => self::EXISTING_NOTIFICATION_ID,
            'ownerId' => 14,
            'isPending' => false,
            'type' => 'Workflow:Review',
            'created' => 1529995052,
            'data' => null,
        ]);

        $this->getGateway()->updateNotification($notification);

        self::assertEquals([
            'id' => (string) self::EXISTING_NOTIFICATION_ID,
            'owner_id' => '14',
            'is_pending' => '0',
            'type' => 'Workflow:Review',
            'created' => '1529995052',
            'data' => null,
        ], $this->loadNotification(self::EXISTING_NOTIFICATION_ID));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCountUserNotifications(): void
    {
        self::assertEquals(5, $this->getGateway()->countUserNotifications(
            self::EXISTING_NOTIFICATION_DATA['owner_id']
        ));
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testCountUserPendingNotifications(): void
    {
        self::assertEquals(
            3,
            $this->getGateway()->countUserPendingNotifications(
                self::EXISTING_NOTIFICATION_DATA['owner_id']
            )
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testLoadUserNotifications(): void
    {
        $userId = 14;
        $offset = 1;
        $limit = 3;

        $results = $this->getGateway()->loadUserNotifications($userId, $offset, $limit);

        self::assertEquals([
            [
                'id' => '4',
                'owner_id' => '14',
                'is_pending' => 1,
                'type' => 'Workflow:Review',
                'created' => '1530005852',
                'data' => null,
            ],
            [
                'id' => '3',
                'owner_id' => '14',
                'is_pending' => 0,
                'type' => 'Workflow:Reject',
                'created' => '1530002252',
                'data' => null,
            ],
            [
                'id' => '2',
                'owner_id' => '14',
                'is_pending' => 0,
                'type' => 'Workflow:Approve',
                'created' => '1529998652',
                'data' => null,
            ],
        ], $results);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function testDelete(): void
    {
        $this->getGateway()->delete(self::EXISTING_NOTIFICATION_ID);

        self::assertEmpty($this->loadNotification(self::EXISTING_NOTIFICATION_ID));
    }

    /**
     * Return a ready to test DoctrineStorage gateway.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase
     */
    protected function getGateway(): DoctrineDatabase
    {
        return new DoctrineDatabase(
            $this->getDatabaseConnection()
        );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadNotification(int $id): array
    {
        $data = $this->connection
            ->executeQuery('SELECT * FROM eznotification WHERE id = :id', ['id' => $id])
            ->fetchAssociative();

        return is_array($data) ? $data : [];
    }
}
