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
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Type;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;

/**
 * Test case for the NotificationService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\NotificationService
 */
class NotificationServiceTest extends BaseTest
{
    public function testLoadNotifications(): void
    {
        $repository = $this->getRepository();

        $notificationService = $repository->getNotificationService();
        $notificationList = $notificationService->loadNotifications(0, 25);

        $this->assertIsArray($notificationList->items);
        $this->assertIsInt($notificationList->totalCount);
        $this->assertEquals(5, $notificationList->totalCount);
    }

    public function testFindNotifications(): void
    {
        $repository = $this->getRepository();

        $notificationService = $repository->getNotificationService();
        $query = new NotificationQuery(
            [],
            0,
            25
        );
        $query->addCriterion(new Type('Workflow:Review'));

        $notificationList = $notificationService->findNotifications($query);

        $this->assertIsArray($notificationList->items);
        $this->assertIsInt($notificationList->totalCount);

        $expectedCount = 3;
        $this->assertEquals($expectedCount, $notificationList->totalCount);
    }

    public function testGetNotification(): void
    {
        $repository = $this->getRepository();

        $notificationId = $this->generateId('notification', 5);

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        // $notificationId is the ID of an existing notification
        $notification = $notificationService->getNotification($notificationId);
        /* END: Use Case */

        $this->assertEquals($notificationId, $notification->id);
    }

    public function testMarkNotificationAsRead(): void
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

    public function testMarkNotificationAsUnread(): void
    {
        $repository = $this->getRepository();

        $notificationId = $this->generateId('notification', 5);
        $notificationService = $repository->getNotificationService();

        $notification = $notificationService->getNotification($notificationId);
        $notificationService->markNotificationAsRead($notification);

        $notification = $notificationService->getNotification($notificationId);
        self::assertFalse($notification->isPending);

        $notificationService->markNotificationAsUnread($notification);
        $notification = $notificationService->getNotification($notificationId);

        self::assertTrue($notification->isPending);
    }

    public function testGetPendingNotificationCount(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notificationPendingCount = $notificationService->getPendingNotificationCount();
        /* END: Use Case */

        $this->assertEquals(3, $notificationPendingCount);
    }

    public function testGetNotificationCount(): void
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $notificationService = $repository->getNotificationService();
        $notificationCount = $notificationService->getNotificationCount();
        /* END: Use Case */

        $this->assertEquals(5, $notificationCount);
    }

    public function testDeleteNotification(): void
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

    public function testCreateNotification(): void
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

        $this->assertGreaterThan(0, $notification->id);
    }

    /**
     * @depends testCreateNotification
     */
    public function testCreateNotificationThrowsInvalidArgumentExceptionOnMissingOwner(): void
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
     * @depends testCreateNotification
     */
    public function testCreateNotificationThrowsInvalidArgumentExceptionOnMissingType(): void
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
