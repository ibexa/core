<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Notification\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Notification\Notification;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\DateCreated;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\NotificationQuery;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Status;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Type;
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

        return $query->execute()->fetchAllAssociative();
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

    public function countUserNotifications(int $userId, ?NotificationQuery $query = null): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter(':user_id', $userId, PDO::PARAM_INT);

        if (($query !== null) && !empty($query->criteria)) {
            $this->applyFilters($queryBuilder, $query->criteria);
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

    /**
     * @return array<int, array<string, mixed>>
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
        $query->setParameter(':user_id', $userId, PDO::PARAM_INT);

        return $query->execute()->fetchAllAssociative();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findUserNotifications(int $userId, ?NotificationQuery $query = null): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->where($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter(':user_id', $userId, PDO::PARAM_INT)
            ->orderBy(self::COLUMN_ID, 'DESC');

        if ($query !== null) {
            if (!empty($query->criteria)) {
                $this->applyFilters($queryBuilder, $query->criteria);
            }

            if ($query->offset > 0) {
                $queryBuilder->setFirstResult($query->offset);
            }

            if ($query->limit > 0) {
                $queryBuilder->setMaxResults($query->limit);
            }
        }

        return $queryBuilder->execute()->fetchAllAssociative();
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion[] $criteria
     */
    private function applyFilters(QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $criterion) {
            $this->applyCriterion($qb, $criterion);
        }
    }

    private function applyCriterion(QueryBuilder $qb, Criterion $criterion): void
    {
        switch (true) {
            case $criterion instanceof Type:
                $qb->andWhere($qb->expr()->eq(self::COLUMN_TYPE, ':type'));
                $qb->setParameter(':type', $criterion->value);
                break;

            case $criterion instanceof Status:
                $qb->andWhere($qb->expr()->in(self::COLUMN_IS_PENDING, ':status'));
                $qb->setParameter(':status', $criterion->statuses, Connection::PARAM_STR_ARRAY);
                break;

            case $criterion instanceof DateCreated:
                if ($criterion->from !== null) {
                    $qb->andWhere($qb->expr()->gte(self::COLUMN_CREATED, ':created_from'));
                    $qb->setParameter(':created_from', $criterion->from->getTimestamp());
                }
                if ($criterion->to !== null) {
                    $qb->andWhere($qb->expr()->lte(self::COLUMN_CREATED, ':created_to'));
                    $qb->setParameter(':created_to', $criterion->to->getTimestamp());
                }
                break;

            case $criterion instanceof LogicalAnd:
                $this->applyLogicalOperator($qb, $criterion->criteria, 'and');
                break;

            default:
                throw new InvalidArgumentException(get_class($criterion), 'Unknown criterion');
        }
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion[] $criteria
     */
    private function applyLogicalOperator(QueryBuilder $qb, array $criteria, string $type): void
    {
        $expr = $qb->expr();
        $parts = [];

        foreach ($criteria as $index => $criterion) {
            $subQb = $this->connection->createQueryBuilder();
            $this->applyCriterion($subQb, $criterion);
            $parts[] = '(' . $subQb->getSQL() . ')';
        }

        if (!empty($parts)) {
            $logicalExpr = $type === 'and' ? $expr->and(...$parts) : $expr->or(...$parts);
            $qb->andWhere($logicalExpr);
        }
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
