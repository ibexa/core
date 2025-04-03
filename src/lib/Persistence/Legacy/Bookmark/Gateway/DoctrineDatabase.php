<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;

class DoctrineDatabase extends Gateway
{
    public const string TABLE_BOOKMARKS = 'ezcontentbrowsebookmark';

    public const string COLUMN_ID = 'id';
    public const string COLUMN_USER_ID = 'user_id';
    public const string COLUMN_LOCATION_ID = 'node_id';
    public const string COLUMN_NAME = 'name';
    private const string USER_ID_PARAM_NAME = 'user_id';
    private const string LOCATION_ID_PARAM_NAME = 'location_id';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertBookmark(Bookmark $bookmark): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_BOOKMARKS)
            ->values([
                self::COLUMN_USER_ID => ':' . self::USER_ID_PARAM_NAME,
                self::COLUMN_LOCATION_ID => ':' . self::LOCATION_ID_PARAM_NAME,
            ])
            ->setParameter(self::USER_ID_PARAM_NAME, $bookmark->userId, ParameterType::INTEGER)
            ->setParameter(self::LOCATION_ID_PARAM_NAME, $bookmark->locationId, ParameterType::INTEGER);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteBookmark(int $id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $id, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     *
     * @phpstan-return list<array<string,mixed>>
     */
    public function loadBookmarkDataById(int $id): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('id', $id, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadBookmarkDataByUserIdAndLocationId(int $userId, array $locationIds): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->and(
                $query->expr()->eq(self::COLUMN_USER_ID, ':' . self::USER_ID_PARAM_NAME),
                $query->expr()->in(self::COLUMN_LOCATION_ID, ':' . self::LOCATION_ID_PARAM_NAME)
            ))
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER)
            ->setParameter(self::LOCATION_ID_PARAM_NAME, $locationIds, ArrayParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
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
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUserBookmarks(int $userId, int $offset = 0, int $limit = -1): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':' . self::USER_ID_PARAM_NAME))
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
    public function countUserBookmarks(int $userId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_BOOKMARKS)
            ->where($query->expr()->eq(self::COLUMN_USER_ID, ':' . self::USER_ID_PARAM_NAME))
            ->setParameter(self::USER_ID_PARAM_NAME, $userId, ParameterType::INTEGER);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function locationSwapped(int $location1Id, int $location2Id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::TABLE_BOOKMARKS)
            ->set(self::COLUMN_LOCATION_ID, '(CASE WHEN node_id = :source_id THEN :target_id ELSE :source_id END)')
            ->where($query->expr()->or(
                $query->expr()->eq(self::COLUMN_LOCATION_ID, ':source_id'),
                $query->expr()->eq(self::COLUMN_LOCATION_ID, ':target_id')
            ))
            ->setParameter('source_id', $location1Id, ParameterType::INTEGER)
            ->setParameter('target_id', $location2Id, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @return string[]
     */
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
