<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway;

class DoctrineDatabase extends Gateway
{
    public const string TABLE_NOTIFICATION = 'eznotification';
    public const string COLUMN_ID = 'id';
    public const string COLUMN_OWNER_ID = 'owner_id';
    public const string COLUMN_IS_PENDING = 'is_pending';
    public const string COLUMN_TYPE = 'type';
    public const string COLUMN_CREATED = 'created';
    public const string COLUMN_DATA = 'data';
    private const string IS_PENDING_PARAM_NAME = 'is_pending';
    private const string USER_ID_PARAM_NAME = 'user_id';
    private const string CREATED_PARAM_NAME = 'created';
    private const string TYPE_PARAM_NAME = 'type';
    private const string DATA_PARAM_NAME = 'data';

    private Connection $connection;

    /**
     * @param \Doctrine\DBAL\Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \JsonException
     */
    public function insert(CreateStruct $notification): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_NOTIFICATION)
            ->values([
                self::COLUMN_IS_PENDING => ':' . self::IS_PENDING_PARAM_NAME,
                self::COLUMN_OWNER_ID => ':' . self::USER_ID_PARAM_NAME,
                self::COLUMN_CREATED => ':' . self::CREATED_PARAM_NAME,
                self::COLUMN_TYPE => ':' . self::TYPE_PARAM_NAME,
                self::COLUMN_DATA => ':' . self::DATA_PARAM_NAME,
            ])
            ->setParameter(self::IS_PENDING_PARAM_NAME, $notification->isPending, ParameterType::BOOLEAN)
            ->setParameter(self::USER_ID_PARAM_NAME, $notification->ownerId, ParameterType::INTEGER)
            ->setParameter(self::CREATED_PARAM_NAME, $notification->created, ParameterType::INTEGER)
            ->setParameter(self::TYPE_PARAM_NAME, $notification->type)
            ->setParameter(self::DATA_PARAM_NAME, json_encode($notification->data, JSON_THROW_ON_ERROR));

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @phpstan-return list<array<string, mixed>>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getNotificationById(int $notificationId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'));

        $query->setParameter('id', $notificationId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function updateNotification(Notification $notification): void
    {
        if (!isset($notification->id) || !is_numeric($notification->id)) {
            throw new InvalidArgumentException(self::COLUMN_ID, 'Cannot update the notification');
        }

        $query = $this->connection->createQueryBuilder();

        $query
            ->update(self::TABLE_NOTIFICATION)
            ->set(self::COLUMN_IS_PENDING, ':' . self::IS_PENDING_PARAM_NAME)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter(self::IS_PENDING_PARAM_NAME, $notification->isPending, ParameterType::BOOLEAN)
            ->setParameter('id', $notification->id, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countUserNotifications(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_OWNER_ID, ':' . self::USER_ID_PARAM_NAME))
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        /** @phpstan-var int<0, max> */
        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countUserPendingNotifications(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($expr->eq(self::COLUMN_OWNER_ID, ':' . self::USER_ID_PARAM_NAME))
            ->andWhere($expr->eq(self::COLUMN_IS_PENDING, ':' . self::IS_PENDING_PARAM_NAME))
            ->setParameter(self::IS_PENDING_PARAM_NAME, true, ParameterType::BOOLEAN)
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUserNotifications(int $userId, int $offset = 0, int $limit = -1): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_OWNER_ID, self::USER_ID_PARAM_NAME))
            ->setFirstResult($offset);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        $query->orderBy(self::COLUMN_ID, 'DESC');
        $query->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(int $notificationId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $notificationId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @return string[]
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
