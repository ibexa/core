<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;
use PDO;

class DoctrineDatabase extends Gateway
{
    public const TABLE_BOOKMARKS = 'ibexa_content_bookmark';

    public const COLUMN_ID = 'id';
    public const COLUMN_USER_ID = 'user_id';
    public const COLUMN_LOCATION_ID = 'node_id';
    public const COLUMN_NAME = 'name';

    /** @var Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * {@inheritdoc}
     */
    public function insertBookmark(Bookmark $bookmark): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_BOOKMARKS)
            ->values([
                self::COLUMN_USER_ID => ':user_id',
                self::COLUMN_LOCATION_ID => ':location_id',
            ])
            ->setParameter('user_id', $bookmark->userId, PDO::PARAM_INT)
            ->setParameter('location_id', $bookmark->locationId, PDO::PARAM_INT);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteBookmark(int $id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $id, PDO::PARAM_INT);

        $query->executeStatement();
    }

    /**
     * {@inheritdoc}
     */
    public function loadBookmarkDataById(int $id): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $id, PDO::PARAM_INT);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function loadBookmarkDataByUserIdAndLocationId(
        int $userId,
        array $locationIds
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->and(
                $query->expr()->eq(self::COLUMN_USER_ID, ':user_id'),
                $query->expr()->in(self::COLUMN_LOCATION_ID, ':location_id')
            ))
            ->setParameter('user_id', $userId, PDO::PARAM_INT)
            ->setParameter('location_id', $locationIds, Connection::PARAM_INT_ARRAY);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadUserIdsByLocation(Location $location): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(self::COLUMN_USER_ID)
            ->from(self::TABLE_BOOKMARKS)
            ->andWhere(
                $queryBuilder
                    ->expr()
                    ->eq(
                        self::COLUMN_LOCATION_ID,
                        $queryBuilder->createNamedParameter(
                            $location->id,
                            ParameterType::INTEGER
                        )
                    )
            );

        return $queryBuilder->executeQuery()->fetchFirstColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserBookmarks(
        int $userId,
        int $offset = 0,
        int $limit = -1
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':user_id'))
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
    public function countUserBookmarks(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':user_id'))
            ->setParameter('user_id', $userId, PDO::PARAM_INT);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * {@inheritdoc}
     */
    public function locationSwapped(
        int $location1Id,
        int $location2Id
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::TABLE_BOOKMARKS)
            ->set(self::COLUMN_LOCATION_ID, '(CASE WHEN node_id = :source_id THEN :target_id ELSE :source_id END)')
            ->where($query->expr()->or(
                $query->expr()->eq(self::COLUMN_LOCATION_ID, ':source_id'),
                $query->expr()->eq(self::COLUMN_LOCATION_ID, ':target_id')
            ));

        $stmt = $this->connection->prepare($query->getSQL());
        $stmt->bindValue('source_id', $location1Id, PDO::PARAM_INT);
        $stmt->bindValue('target_id', $location2Id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_NAME,
            self::COLUMN_USER_ID,
            self::COLUMN_LOCATION_ID,
        ];
    }
}
