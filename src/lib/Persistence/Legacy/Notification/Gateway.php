<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification;

use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;

abstract class Gateway
{
    /**
     * Store Notification ValueObject in persistent storage.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Notification\CreateStruct $notification
     *
     * @return int
     */
    abstract public function insert(CreateStruct $notification): int;

    /**
     * Get Notification by its id.
     *
     * @param int $notificationId
     *
     * @return array
     */
    abstract public function getNotificationById(int $notificationId): array;

    /**
     * Update Notification ValueObject in persistent storage.
     * There's no edit feature but it's essential to mark Notification as read.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Notification\Notification $notification
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    abstract public function updateNotification(Notification $notification): void;

    /**
     * @phpstan-return int<0, max>
     */
    abstract public function countUserNotifications(int $userId, ?NotificationQuery $query = null): int;

    /**
     * Count users unread Notifications.
     *
     * @param int $userId
     *
     * @return int
     */
    abstract public function countUserPendingNotifications(int $userId): int;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function loadUserNotifications(int $userId, int $offset = 0, int $limit = -1): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract public function findUserNotifications(
        int $userId,
        ?NotificationQuery $query = null
    ): array;

    /**
     * @param int $notificationId
     */
    abstract public function delete(int $notificationId): void;
}
