<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification;
use Ibexa\Contracts\Core\Repository\Values\Notification\NotificationList;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;

/**
 * Service to manager user notifications. It works in the context of a current User (obtained from
 * the PermissionResolver).
 */
interface NotificationService
{
    public function loadNotifications(int $offset, int $limit): NotificationList;

    public function findNotifications(?NotificationQuery $query = null): NotificationList;

    /**
     * Load single notification (by ID).
     *
     * @param int $notificationId Notification ID
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Notification\Notification
     */
    public function getNotification(int $notificationId): Notification;

    /**
     * @param int[] $notificationIds
     */
    public function markUserNotificationsAsRead(array $notificationIds = []): void;

    /**
     * Mark notification as read so it no longer bother the user.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Notification $notification
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function markNotificationAsRead(Notification $notification): void;

    /**
     * Marks the given notification as unread so it is shown again as new to the user.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function markNotificationAsUnread(Notification $notification): void;

    /**
     * Get count of unread users notifications.
     *
     * @return int
     */
    public function getPendingNotificationCount(): int;

    public function getNotificationCount(?NotificationQuery $query = null): int;

    /**
     * Creates a new notification.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\CreateStruct $createStruct
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Notification\Notification
     */
    public function createNotification(CreateStruct $createStruct): Notification;

    /**
     * Deletes a notification.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Notification $notification
     */
    public function deleteNotification(Notification $notification): void;
}

class_alias(NotificationService::class, 'eZ\Publish\API\Repository\NotificationService');
