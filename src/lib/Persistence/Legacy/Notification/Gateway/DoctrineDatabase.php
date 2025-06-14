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
    public const TABLE_NOTIFICATION = 'ibexa_notification';
    public const COLUMN_ID = 'id';
    public const COLUMN_OWNER_ID = 'owner_id';
    public const COLUMN_IS_PENDING = 'is_pending';
    public const COLUMN_TYPE = 'type';
    public const COLUMN_CREATED = 'created';
    public const COLUMN_DATA = 'data';

    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
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
            ->setParameter('is_pending', $createStruct->isPending, PDO::PARAM_BOOL)
            ->setParameter('user_id', $createStruct->ownerId, PDO::PARAM_INT)
            ->setParameter('created', $createStruct->created, PDO::PARAM_INT)
            ->setParameter('type', $createStruct->type, PDO::PARAM_STR)
            ->setParameter('data', json_encode($createStruct->data), PDO::PARAM_STR);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function getNotificationById(int $notificationId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'));

        $query->setParameter('id', $notificationId, PDO::PARAM_INT);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
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
            ->setParameter('is_pending', $notification->isPending, PDO::PARAM_BOOL)
            ->setParameter('id', $notification->id, PDO::PARAM_INT);

        $query->executeStatement();
    }

    public function countUserNotifications(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter('user_id', $userId, PDO::PARAM_INT);

        /** @phpstan-var int<0, max> */
        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * {@inheritdoc}
     */
    public function countUserPendingNotifications(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($expr->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->andWhere($expr->eq(self::COLUMN_IS_PENDING, ':is_pending'))
            ->setParameter('is_pending', true, PDO::PARAM_BOOL)
            ->setParameter('user_id', $userId, PDO::PARAM_INT);

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserNotifications(int $userId, int $offset = 0, int $limit = -1): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setFirstResult($offset);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy(self::COLUMN_ID, 'DESC');
        $query->setParameter('user_id', $userId, PDO::PARAM_INT);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $notificationId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $notificationId, PDO::PARAM_INT);

        $query->executeStatement();
    }

    /**
     * @return array
     */
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
