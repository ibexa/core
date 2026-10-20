<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification;

use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Handler as HandlerInterface;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Contracts\Core\Persistence\Notification\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Notification as APINotification;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;

class Handler implements HandlerInterface
{
    /** @var Gateway */
    protected $gateway;

    /** @var Mapper */
    protected $mapper;

    /**
     * @param Gateway $gateway
     * @param Mapper $mapper
     */
    public function __construct(
        Gateway $gateway,
        Mapper $mapper
    ) {
        $this->gateway = $gateway;
        $this->mapper = $mapper;
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundException
     */
    public function createNotification(CreateStruct $createStruct): Notification
    {
        $id = $this->gateway->insert($createStruct);

        return $this->getNotificationById($id);
    }

    /**
     * {@inheritdoc}
     */
    public function countPendingNotifications(int $ownerId): int
    {
        return $this->gateway->countUserPendingNotifications($ownerId);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundException
     */
    public function getNotificationById(int $notificationId): Notification
    {
        $notification = $this->mapper->extractNotificationsFromRows(
            $this->gateway->getNotificationById($notificationId)
        );

        if (count($notification) < 1) {
            throw new NotFoundException('Notification', $notificationId);
        }

        return reset($notification);
    }

    public function bulkUpdateUserNotifications(
        int $ownerId,
        UpdateStruct $updateStruct,
        bool $pendingOnly = false,
        array $notificationIds = []
    ): array {
        return $this->gateway->bulkUpdateUserNotifications($ownerId, $updateStruct, $pendingOnly, $notificationIds);
    }

    /**
     * {@inheritdoc}
     *
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function updateNotification(
        APINotification $apiNotification,
        UpdateStruct $updateStruct
    ): Notification {
        $notification = $this->mapper->createNotificationFromUpdateStruct(
            $updateStruct
        );
        $notification->id = $apiNotification->id;

        $this->gateway->updateNotification($notification);

        return $this->getNotificationById($notification->id);
    }

    public function countNotifications(
        int $userId,
        ?NotificationQuery $query = null
    ): int {
        return $this->gateway->countUserNotifications($userId, $query);
    }

    public function loadUserNotifications(
        int $userId,
        int $offset,
        int $limit
    ): array {
        return $this->mapper->extractNotificationsFromRows(
            $this->gateway->loadUserNotifications($userId, $offset, $limit)
        );
    }

    public function findUserNotifications(
        int $userId,
        ?NotificationQuery $query = null
    ): array {
        return $this->mapper->extractNotificationsFromRows(
            $this->gateway->findUserNotifications($userId, $query)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete(APINotification $notification): void
    {
        $this->gateway->delete($notification->id);
    }
}
