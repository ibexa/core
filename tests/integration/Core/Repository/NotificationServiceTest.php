<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;
use Ibexa\Contracts\Core\Repository\Values\Notification\NotificationList;

/**
 * Test case for the NotificationService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\NotificationService
 */
class NotificationServiceTest extends BaseTest
{
    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::loadNotifications()
     */
    public function testLoadNotifications()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notificationList = $notificationService->loadNotifications(0, 25);
        /* END: Use Case */

        $this->assertInstanceOf(NotificationList::class, $notificationList);
        $this->assertIsArray($notificationList->items);
        $this->assertIsInt($notificationList->totalCount);
        $this->assertEquals(5, $notificationList->totalCount);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::getNotification()
     */
    public function testGetNotification()
    {
        $repository = $this->getRepository();

        $notificationId = $this->generateId('notification', 5);

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        // $notificationId is the ID of an existing notification
        $notification = $notificationService->getNotification($notificationId);
        /* END: Use Case */

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($notificationId, $notification->id);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::markNotificationAsRead()
     */
    public function testMarkNotificationAsRead()
    {
        $repository = $this->getRepository();

        $notificationId = $this->generateId('notification', 5);
        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();

        $notification = $notificationService->getNotification($notificationId);
        $notificationService->markNotificationAsRead($notification);
        $notification = $notificationService->getNotification($notificationId);
        /* END: Use Case */

        $this->assertFalse($notification->isPending);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::getPendingNotificationCount()
     */
    public function testGetPendingNotificationCount()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notificationPendingCount = $notificationService->getPendingNotificationCount();
        /* END: Use Case */

        $this->assertEquals(3, $notificationPendingCount);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::getNotificationCount()
     */
    public function testGetNotificationCount()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notificationCount = $notificationService->getNotificationCount();
        /* END: Use Case */

        $this->assertEquals(5, $notificationCount);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::deleteNotification()
     */
    public function testDeleteNotification()
    {
        $repository = $this->getRepository();

        $notificationId = $this->generateId('notification', 5);
        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notification = $notificationService->getNotification($notificationId);
        $notificationService->deleteNotification($notification);
        /* END: Use Case */

        try {
            $notificationService->getNotification($notificationId);
            $this->fail('Notification ' . $notificationId . ' not deleted.');
        } catch (NotFoundException $e) {
        }
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::createNotification()
     */
    public function testCreateNotification()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $user = $repository->getUserService()->loadUser(14);

        $createStruct = new CreateStruct([
            'ownerId' => $user->id,
            'type' => 'TEST',
            'data' => [
                'foo' => 'Foo',
                'bar' => 'Bar',
                'baz' => 'Baz',
            ],
        ]);

        $notification = $notificationService->createNotification($createStruct);
        /* END: Use Case */

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertGreaterThan(0, $notification->id);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::createNotification()
     * @depends testCreateNotification
     */
    public function testCreateNotificationThrowsInvalidArgumentExceptionOnMissingOwner()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();

        $createStruct = new CreateStruct([
            'type' => 'TEST',
        ]);

        // This call will fail because notification owner is not specified
        $notificationService->createNotification($createStruct);
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\NotificationService::createNotification()
     * @depends testCreateNotification
     */
    public function testCreateNotificationThrowsInvalidArgumentExceptionOnMissingType()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();

        $createStruct = new CreateStruct([
            'ownerId' => 14,
        ]);

        // This call will fail because notification type is not specified
        $notificationService->createNotification($createStruct);
        /* END: Use Case */
    }
}

class_alias(NotificationServiceTest::class, 'eZ\Publish\API\Repository\Tests\NotificationServiceTest');
