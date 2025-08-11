<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\NotificationService;
use Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;
use Ibexa\Contracts\Core\Repository\Values\Notification\NotificationList;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;

abstract class NotificationServiceDecorator implements NotificationService
{
    protected NotificationService $innerService;

    public function __construct(NotificationService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function loadNotifications(
        int $offset,
        int $limit
    ): NotificationList {
        return $this->innerService->loadNotifications($offset, $limit);
    }

    public function findNotifications(?NotificationQuery $query = null): NotificationList
    {
        return $this->innerService->findNotifications($query);
    }

    public function getNotification(int $notificationId): Notification
    {
        return $this->innerService->getNotification($notificationId);
    }

    public function markUserNotificationsAsRead(array $notificationIds = []): void
    {
        $this->innerService->markUserNotificationsAsRead($notificationIds);
    }

    public function markNotificationAsRead(Notification $notification): void
    {
        $this->innerService->markNotificationAsRead($notification);
    }

    public function markNotificationAsUnread(Notification $notification): void
    {
        $this->innerService->markNotificationAsUnread($notification);
    }

    public function getPendingNotificationCount(): int
    {
        return $this->innerService->getPendingNotificationCount();
    }

    public function getNotificationCount(?NotificationQuery $query = null): int
    {
        return $this->innerService->getNotificationCount($query);
    }

    public function createNotification(CreateStruct $createStruct): Notification
    {
        return $this->innerService->createNotification($createStruct);
    }

    public function deleteNotification(Notification $notification): void
    {
        $this->innerService->deleteNotification($notification);
    }
}

class_alias(NotificationServiceDecorator::class, 'eZ\Publish\SPI\Repository\Decorator\NotificationServiceDecorator');
