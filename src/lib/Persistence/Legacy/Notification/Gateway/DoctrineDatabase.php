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
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\NotificationQuery;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway;
use function Ibexa\PolyfillPhp82\iterator_to_array;
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

    private Connection $connection;

    /**
     * @var \Ibexa\Contracts\Core\Repository\Values\Notification\CriterionHandlerInterface[]
     */
    private array $criterionHandlers;

    /**
     * @param iterable<\Ibexa\Contracts\Core\Repository\Values\Notification\CriterionHandlerInterface> $criterionHandlers
     */
    public function __construct(Connection $connection, iterable $criterionHandlers)
    {
        $this->connection = $connection;
        $this->criterionHandlers = iterator_to_array($criterionHandlers);
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
            ->setParameter('is_pending', $createStruct->isPending, PDO::PARAM_BOOL)
            ->setParameter('user_id', $createStruct->ownerId, PDO::PARAM_INT)
            ->setParameter('created', $createStruct->created, PDO::PARAM_INT)
            ->setParameter('type', $createStruct->type, PDO::PARAM_STR)
            ->setParameter('data', json_encode($createStruct->data), PDO::PARAM_STR);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

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

    public function countUserNotifications(int $userId, ?NotificationQuery $query = null): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_NOTIFICATION)
            ->where($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter('user_id', $userId, PDO::PARAM_INT);

        if ($query !== null && !empty($query->getCriteria())) {
            $this->applyFilters($queryBuilder, $query->getCriteria());
        }

        /** @phpstan-var int<0, max> */
        return (int)$queryBuilder->executeQuery()->fetchOne();
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
            ->setParameter('is_pending', true, PDO::PARAM_BOOL)
            ->setParameter('user_id', $userId, PDO::PARAM_INT);

        return (int)$query->executeQuery()->fetchOne();
    }

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

    public function findUserNotifications(int $userId, ?NotificationQuery $query = null): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(...$this->getColumns())
            ->from(self::TABLE_NOTIFICATION)
            ->andWhere($queryBuilder->expr()->eq(self::COLUMN_OWNER_ID, ':user_id'))
            ->setParameter('user_id', $userId, PDO::PARAM_INT)
            ->orderBy(self::COLUMN_ID, 'DESC');

        if ($query === null) {
            return $queryBuilder->executeQuery()->fetchAllAssociative();
        }

        if (!empty($query->getCriteria())) {
            $this->applyFilters($queryBuilder, $query->getCriteria());
        }

        if ($query->getOffset() > 0) {
            $queryBuilder->setFirstResult($query->getOffset());
        }

        if ($query->getLimit() > 0) {
            $queryBuilder->setMaxResults($query->getLimit());
        }

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Notification\Query\CriterionInterface[] $criteria
     */
    private function applyFilters(QueryBuilder $qb, array $criteria): void
    {
        foreach ($criteria as $criterion) {
            $this->applyCriterion($qb, $criterion);
        }
    }

    private function applyCriterion(QueryBuilder $qb, CriterionInterface $criterion): void
    {
        foreach ($this->criterionHandlers as $handler) {
            if ($handler->supports($criterion)) {
                $handler->apply($qb, $criterion);

                return;
            }
        }

        throw new InvalidArgumentException(
            get_class($criterion),
            'No handler found for criterion of type. Make sure the handler service is registered and tagged with "ibexa.notification.criterion_handler".'
        );
    }

    public function delete(int $notificationId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_NOTIFICATION)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $notificationId, PDO::PARAM_INT);

        $query->executeStatement();
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
