<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;
use Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;

class DoctrineDatabase extends Gateway
{
    public const string TABLE_ASYNC_PUBLICATION = 'ibexa_async_publication';
    public const string COLUMN_ID = 'id';
    public const string COLUMN_CONTENT_ID = 'content_id';
    public const string COLUMN_VERSION_NO = 'version_no';
    public const string COLUMN_STATUS = 'status';
    public const string COLUMN_OWNER_ID = 'owner_id';
    public const string COLUMN_CREATED = 'created';
    public const string COLUMN_MODIFIED = 'modified';
    public const string COLUMN_ERROR = 'error';
    public const string COLUMN_DATA = 'data';

    public function __construct(
        private readonly Connection $connection
    ) {
    }

    public function insert(CreateStruct $createStruct): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::TABLE_ASYNC_PUBLICATION)
            ->values([
                self::COLUMN_CONTENT_ID => ':content_id',
                self::COLUMN_VERSION_NO => ':version_no',
                self::COLUMN_STATUS => ':status',
                self::COLUMN_OWNER_ID => ':owner_id',
                self::COLUMN_CREATED => ':created',
                self::COLUMN_MODIFIED => ':modified',
                self::COLUMN_DATA => ':data',
            ])
            ->setParameter('content_id', $createStruct->contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $createStruct->versionNo, ParameterType::INTEGER)
            ->setParameter('status', $createStruct->status->value, ParameterType::STRING)
            ->setParameter('owner_id', $createStruct->ownerId, ParameterType::INTEGER)
            ->setParameter('created', $createStruct->created, ParameterType::INTEGER)
            ->setParameter('modified', $createStruct->modified, ParameterType::INTEGER)
            ->setParameter('data', json_encode($createStruct->data), ParameterType::STRING);

        $query->executeStatement();

        return (int) $this->connection->lastInsertId();
    }

    public function findByContentId(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function updateByContentId(int $contentId, UpdateStruct $updateStruct): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        $hasChange = false;

        if ($updateStruct->status !== null) {
            $query->set(self::COLUMN_STATUS, ':status')
                ->setParameter('status', $updateStruct->status->value, ParameterType::STRING);
            $hasChange = true;
        }

        if ($updateStruct->errorMessage !== null) {
            $query->set(self::COLUMN_ERROR, ':error')
                ->setParameter('error', $updateStruct->errorMessage, ParameterType::STRING);
            $hasChange = true;
        }

        if ($updateStruct->modified !== null) {
            $query->set(self::COLUMN_MODIFIED, ':modified')
                ->setParameter('modified', $updateStruct->modified, ParameterType::INTEGER);
            $hasChange = true;
        }

        if (!$hasChange) {
            return;
        }

        $query->executeStatement();
    }

    public function loadAll(int $offset = 0, int $limit = -1): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_ASYNC_PUBLICATION)
            ->orderBy(self::COLUMN_MODIFIED, 'DESC')
            ->setFirstResult($offset);

        if ($limit > 0) {
            $query->setMaxResults($limit);
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function countAll(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(' . self::COLUMN_ID . ')')
            ->from(self::TABLE_ASYNC_PUBLICATION);

        /** @phpstan-var int<0, max> */
        return (int) $query->executeQuery()->fetchOne();
    }

    public function deleteByContentId(int $contentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        $query->executeStatement();
    }

    /**
     * @return string[]
     */
    private function getColumns(): array
    {
        return [
            self::COLUMN_ID,
            self::COLUMN_CONTENT_ID,
            self::COLUMN_VERSION_NO,
            self::COLUMN_STATUS,
            self::COLUMN_OWNER_ID,
            self::COLUMN_CREATED,
            self::COLUMN_MODIFIED,
            self::COLUMN_ERROR,
            self::COLUMN_DATA,
        ];
    }
}
