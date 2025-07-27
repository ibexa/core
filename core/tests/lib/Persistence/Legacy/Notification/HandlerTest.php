<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Notification;

use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Contracts\Core\Persistence\Notification\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification as APINotification;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Type;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway;
use Ibexa\Core\Persistence\Legacy\Notification\Handler;
use Ibexa\Core\Persistence\Legacy\Notification\Mapper;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Notification\Handler
 */
class HandlerTest extends TestCase
{
    public const NOTIFICATION_ID = 1;

    /** @var \Ibexa\Core\Persistence\Legacy\Notification\Gateway|\PHPUnit\Framework\MockObject\MockObject */
    private $gateway;

    /** @var \Ibexa\Core\Persistence\Legacy\Notification\Mapper|\PHPUnit\Framework\MockObject\MockObject */
    private $mapper;

    /** @var \Ibexa\Core\Persistence\Legacy\Notification\Handler */
    private $handler;

    protected function setUp(): void
    {
        $this->gateway = $this->createMock(Gateway::class);
        $this->mapper = $this->createMock(Mapper::class);
        $this->handler = new Handler($this->gateway, $this->mapper);
    }

    public function testCreateNotification()
    {
        $createStruct = new CreateStruct([
            'ownerId' => 5,
            'type' => 'TEST',
            'isPending' => true,
            'data' => [],
            'created' => 0,
        ]);

        $this->gateway
            ->expects(self::once())
            ->method('insert')
            ->with($createStruct)
            ->willReturn(self::NOTIFICATION_ID);

        $this->mapper
            ->expects(self::once())
            ->method('extractNotificationsFromRows')
            ->willReturn([new Notification([
                'id' => self::NOTIFICATION_ID,
            ])]);

        $notification = $this->handler->createNotification($createStruct);

        self::assertEquals($notification->id, self::NOTIFICATION_ID);
    }

    public function testCountPendingNotifications()
    {
        $ownerId = 10;
        $expectedCount = 12;

        $this->gateway
            ->expects(self::once())
            ->method('countUserPendingNotifications')
            ->with($ownerId)
            ->willReturn($expectedCount);

        self::assertEquals($expectedCount, $this->handler->countPendingNotifications($ownerId));
    }

    public function testGetNotificationById()
    {
        $rows = [
            [
                'id' => 1, /* ... */
            ],
        ];

        $object = new Notification([
            'id' => 1, /* ... */
        ]);

        $this->gateway
            ->expects(self::once())
            ->method('getNotificationById')
            ->with(self::NOTIFICATION_ID)
            ->willReturn($rows);

        $this->mapper
            ->expects(self::once())
            ->method('extractNotificationsFromRows')
            ->with($rows)
            ->willReturn([$object]);

        self::assertEquals($object, $this->handler->getNotificationById(self::NOTIFICATION_ID));
    }

    public function testUpdateNotification()
    {
        $updateStruct = new UpdateStruct([
            'isPending' => false,
        ]);

        $data = [
            'id' => self::NOTIFICATION_ID,
            'ownerId' => null,
            'isPending' => true,
            'type' => null,
            'created' => null,
            'data' => [],
        ];

        $apiNotification = new APINotification($data);
        $spiNotification = new Notification($data);

        $this->mapper
            ->expects(self::once())
            ->method('createNotificationFromUpdateStruct')
            ->with($updateStruct)
            ->willReturn($spiNotification);

        $this->gateway
            ->expects(self::once())
            ->method('updateNotification')
            ->with($spiNotification);

        $this->mapper
            ->expects(self::once())
            ->method('extractNotificationsFromRows')
            ->willReturn([new Notification([
                'id' => self::NOTIFICATION_ID,
            ])]);

        $this->handler->updateNotification($apiNotification, $updateStruct);
    }

    public function testCountNotifications()
    {
        $ownerId = 10;
        $expectedCount = 12;

        $this->gateway
            ->expects(self::once())
            ->method('countUserNotifications')
            ->with($ownerId)
            ->willReturn($expectedCount);

        self::assertEquals($expectedCount, $this->handler->countNotifications($ownerId));
    }

    public function testLoadUserNotifications(): void
    {
        $ownerId = 9;
        $limit = 5;
        $offset = 0;

        $rows = [
            ['id' => 1/* ... */],
            ['id' => 2/* ... */],
            ['id' => 3/* ... */],
        ];

        $objects = [
            new Notification(['id' => 1/* ... */]),
            new Notification(['id' => 2/* ... */]),
            new Notification(['id' => 3/* ... */]),
        ];

        $this->gateway
            ->expects(self::once())
            ->method('loadUserNotifications')
            ->with($ownerId, $offset, $limit)
            ->willReturn($rows);

        $this->mapper
            ->expects(self::once())
            ->method('extractNotificationsFromRows')
            ->with($rows)
            ->willReturn($objects);

        self::assertEquals($objects, $this->handler->loadUserNotifications($ownerId, $offset, $limit));
    }

    public function testFindUserNotifications(): void
    {
        $ownerId = 9;
        $limit = 5;
        $offset = 0;
        $query = new NotificationQuery([new Type('Workflow:Review')], $offset, $limit);

        $rows = [
            ['id' => 1, 'owner_id' => 9, 'is_pending' => 1, 'type' => 'Workflow:Review', 'created' => '1530005852', 'data' => null],
            ['id' => 2, 'owner_id' => 9, 'is_pending' => 0, 'type' => 'Workflow:Reject', 'created' => '1530002252', 'data' => null],
            ['id' => 3, 'owner_id' => 9, 'is_pending' => 0, 'type' => 'Workflow:Approve', 'created' => '1529998652', 'data' => null],
        ];

        $objects = [
            new Notification(['id' => 1, 'ownerId' => 9, 'isPending' => 1, 'type' => 'Workflow:Review', 'created' => 1530005852, 'data' => null]),
            new Notification(['id' => 2, 'ownerId' => 9, 'isPending' => 0, 'type' => 'Workflow:Reject', 'created' => 1530002252, 'data' => null]),
            new Notification(['id' => 3, 'ownerId' => 9, 'isPending' => 0, 'type' => 'Workflow:Approve', 'created' => 1529998652, 'data' => null]),
        ];

        $this->gateway
            ->expects(self::exactly(2))
            ->method('findUserNotifications')
            ->with(
                self::equalTo($ownerId),
                self::logicalOr(
                    self::equalTo(new NotificationQuery([], $offset, $limit)),
                    self::equalTo($query)
                )
            )
            ->willReturn($rows);

        $this->mapper
            ->expects(self::exactly(2))
            ->method('extractNotificationsFromRows')
            ->with($rows)
            ->willReturn($objects);

        self::assertEquals($objects, $this->handler->findUserNotifications($ownerId, new NotificationQuery([], $offset, $limit)));

        self::assertEquals($objects, $this->handler->findUserNotifications($ownerId, $query));
    }

    public function testDelete()
    {
        $notification = new APINotification([
            'id' => self::NOTIFICATION_ID, /* ... */
        ]);

        $this->gateway
            ->expects(self::once())
            ->method('delete')
            ->with($notification->id);

        $this->handler->delete($notification);
    }
}
