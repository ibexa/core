<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway;
use PDO;

class DoctrineDatabase extends Gateway
{
    public const TABLE_NOTIFICATION = 'eznotification';
    public const COLUMN_ID = 'id';
    public const COLUMN_OWNER_ID = 'owner_id';
    public const COLUMN_IS_PENDING = 'is_pending';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_CREATED = 'created';
    public const COLUMN_DATA = 'data';

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function insert(CreateStruct $createStruct): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_NOTIFICATION)
            ->values([
                self::COLUMN_IS_PENDING => ':is_pending',
                self::COLUMN_OWNER_ID => ':user_id',
                self::COLUMN_CREATED => ':created',
                self::COLUMN_TYPE => ':type',
                self::COLUMN_DATA => ':data',
            ])
            ->setParameter(':is_pending', $createStruct->isPending, PDO::PARAM_BOOL)
            ->setParameter(':user_id', $createStruct->ownerId, PDO::PARAM_INT)
            ->setParameter(':created', $createStruct->created, PDO::PARAM_INT)
            ->setParameter(':type', $createStruct->type, PDO::PARAM_STR)
            ->setParameter(':data', json_encode($createStruct->data), PDO::PARAM_STR);

        $query->execute();

        return (int) $this->connection->lastInsertId();
    }

    public function getNotificationById(int $notificationId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'));

        $query->setParameter(':id', $notificationId, PDO::PARAM_INT);

        return $query->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateNotification(Notification $notification): void
    {
        if (!isset($notification->id) || !is_numeric($notification->id)) {
            throw new InvalidArgumentException(self::COLUMN_ID, 'Cannot update the notification');
        }

        $query = $this->connection->createQueryBuilder();

        $query
            ->update(self::TABLE_NOTIFICATION)
            ->set(self::COLUMN_IS_PENDING, ':is_pending')
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter(':is_pending', $notification->isPending, PDO::PARAM_BOOL)
            ->setParameter(':id', $notification->id, PDO::PARAM_INT);

        $query->execute();
    }

    public function countUserNotifications(int $userId, ?string $query = null): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter(':user_id', $userId, PDO::PARAM_INT);

        if ($query !== null) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->like(self::COLUMN_TYPE, ':query'),
                        $queryBuilder->expr()->like(self::COLUMN_DATA, ':query')
                    )
                )
                ->setParameter(':query', '%' . $query . '%');
        }

        return (int)$queryBuilder->execute()->fetchColumn();
    }

    public function countUserPendingNotifications(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($expr->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->andWhere($expr->eq(self::COLUMN_IS_PENDING, ':is_pending'))
            ->setParameter(':is_pending', true, PDO::PARAM_BOOL)
            ->setParameter(':user_id', $userId, PDO::PARAM_INT);

        return (int)$query->execute()->fetchColumn();
    }

    public function loadUserNotifications(int $userId, int $offset = 0, int $limit = -1, ?string $query = null): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setFirstResult($offset);

        if ($query !== null) {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->or(
                        $queryBuilder->expr()->like(self::COLUMN_TYPE, ':query'),
                        $queryBuilder->expr()->like(self::COLUMN_DATA, ':query')
                    )
                )
                ->setParameter(':query', '%' . $query . '%');
        }

        if ($limit > 0) {
            $queryBuilder->setMaxResults($limit);
        }

        $queryBuilder->orderBy(self::COLUMN_ID, 'DESC');
        $queryBuilder->setParameter(':user_id', $userId, PDO::PARAM_INT);

        return $queryBuilder->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete(int $notificationId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter(':id', $notificationId, PDO::PARAM_INT);

        $query->execute();
    }

    private function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_OWNER_ID,
            self::COLUMN_IS_PENDING,
            self::COLUMN_TYPE,
            self::COLUMN_CREATED,
            self::COLUMN_DATA,
        ];
    }
}

class_alias(DoctrineDatabase::class, 'eZ\Publish\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase');
