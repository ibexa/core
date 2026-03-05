<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway;

use Doctrine\DBAL\DBALException;
use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Contracts\Core\Persistence\Notification\UpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway;
use PDOException;
use Throwable;

class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var \Ibexa\Core\Persistence\Legacy\Notification\Gateway
     */
    protected $innerGateway;

    /**
     * ExceptionConversion constructor.
     *
     * @param \Ibexa\Core\Persistence\Legacy\Notification\Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function getNotificationById(int $notificationId): array
    {
        try {
            return $this->innerGateway->getNotificationById($notificationId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function bulkUpdateUserNotifications(
        int $ownerId,
        UpdateStruct $updateStruct,
        bool $pendingOnly = false,
        array $notificationIds = []
    ): array {
        try {
            return $this->innerGateway->bulkUpdateUserNotifications($ownerId, $updateStruct, $pendingOnly, $notificationIds);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function updateNotification(Notification $notification): void
    {
        try {
            $this->innerGateway->updateNotification($notification);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function countUserNotifications(int $userId, ?NotificationQuery $query = null): int
    {
        try {
            return $this->innerGateway->countUserNotifications($userId, $query);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function countUserPendingNotifications(int $userId): int
    {
        try {
            return $this->innerGateway->countUserPendingNotifications($userId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function loadUserNotifications(int $userId, int $offset = 0, int $limit = -1): array
    {
        try {
            return $this->innerGateway->loadUserNotifications($userId, $offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function findUserNotifications(int $userId, ?NotificationQuery $query = null): array
    {
        try {
            return $this->innerGateway->findUserNotifications($userId, $query);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function insert(CreateStruct $notification): int
    {
        try {
            return $this->innerGateway->insert($notification);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }

    public function delete(int $notificationId): void
    {
        try {
            $this->innerGateway->delete($notificationId);
        } catch (Throwable $e) {
            if ($e instanceof DBALException || $e instanceof PDOException) {
                throw DatabaseException::wrap($e);
            }

            throw $e;
        }
    }
}

class_alias(ExceptionConversion::class, 'eZ\Publish\Core\Persistence\Legacy\Notification\Gateway\ExceptionConversion');
