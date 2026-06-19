<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\AsyncPublicationJobStatus;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;
use Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;

class DoctrineDatabase extends Gateway
{
    public const string TABLE_ASYNC_PUBLICATION = 'ibexa_content_async_publication_job';
    public const string COLUMN_ID = 'id';
    public const string COLUMN_CONTENT_ID = 'content_id';
    public const string COLUMN_VERSION_NO = 'version_no';
    public const string COLUMN_TRANSPORT_MESSAGE_ID = 'transport_message_id';
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
                self::COLUMN_TRANSPORT_MESSAGE_ID => ':transport_message_id',
                self::COLUMN_STATUS => ':status',
                self::COLUMN_OWNER_ID => ':owner_id',
                self::COLUMN_CREATED => ':created',
                self::COLUMN_MODIFIED => ':modified',
                self::COLUMN_DATA => ':data',
            ])
            ->setParameter('content_id', $createStruct->contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $createStruct->versionNo, ParameterType::INTEGER)
            ->setParameter(
                'transport_message_id',
                $createStruct->transportMessageId,
                $createStruct->transportMessageId === null ? ParameterType::NULL : ParameterType::INTEGER
            )
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
            ->orderBy(self::COLUMN_CREATED, 'ASC')
            ->addOrderBy(self::COLUMN_ID, 'ASC')
            ->setParameter('content_id', $contentId, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function updateByContentIdAndVersion(int $contentId, int $versionNo, UpdateStruct $updateStruct): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->andWhere($query->expr()->eq(self::COLUMN_VERSION_NO, ':version_no'))
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER);

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

    public function deleteByContentIdAndVersion(int $contentId, int $versionNo): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->andWhere($query->expr()->eq(self::COLUMN_VERSION_NO, ':version_no'))
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER);

        $query->executeStatement();
    }

    public function findContentIdsWithDispatchableWork(): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('DISTINCT j.' . self::COLUMN_CONTENT_ID)
            ->from(self::TABLE_ASYNC_PUBLICATION, 'j')
            ->where($query->expr()->eq('j.' . self::COLUMN_STATUS, ':awaitingDispatch'))
            ->andWhere($query->expr()->isNull('j.' . self::COLUMN_TRANSPORT_MESSAGE_ID))
            ->andWhere(
                <<<'SQL'
                NOT EXISTS (
                    SELECT 1 FROM ibexa_content_async_publication_job k
                    WHERE k.content_id = j.content_id
                    AND (
                        k.status = :inFlightProcessing
                        OR (k.status = :inFlightQueued AND k.transport_message_id IS NOT NULL)
                    )
                )
                SQL
            )
            ->setParameter('awaitingDispatch', AsyncPublicationJobStatus::QUEUED->value, ParameterType::STRING)
            ->setParameter('inFlightQueued', AsyncPublicationJobStatus::QUEUED->value, ParameterType::STRING)
            ->setParameter('inFlightProcessing', AsyncPublicationJobStatus::PROCESSING->value, ParameterType::STRING);

        return array_map('intval', $query->executeQuery()->fetchFirstColumn());
    }

    public function findOldestQueuedForContent(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(...$this->getColumns())
            ->from(self::TABLE_ASYNC_PUBLICATION)
            ->where($query->expr()->eq(self::COLUMN_CONTENT_ID, ':content_id'))
            ->andWhere($query->expr()->eq(self::COLUMN_STATUS, ':queued'))
            ->andWhere($query->expr()->isNull(self::COLUMN_TRANSPORT_MESSAGE_ID))
            ->orderBy(self::COLUMN_CREATED, 'ASC')
            ->addOrderBy(self::COLUMN_ID, 'ASC')
            ->setMaxResults(1)
            ->setParameter('content_id', $contentId, ParameterType::INTEGER)
            ->setParameter('queued', AsyncPublicationJobStatus::QUEUED->value, ParameterType::STRING);

        $row = $query->executeQuery()->fetchAssociative();

        return $row === false ? [] : $row;
    }

    public function assignTransportMessageId(int $id, int $transportMessageId, int $modified): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::TABLE_ASYNC_PUBLICATION)
            ->set(self::COLUMN_TRANSPORT_MESSAGE_ID, ':transport_message_id')
            ->set(self::COLUMN_MODIFIED, ':modified')
            ->where($query->expr()->eq(self::COLUMN_ID, ':id'))
            ->setParameter('transport_message_id', $transportMessageId, ParameterType::INTEGER)
            ->setParameter('modified', $modified, ParameterType::INTEGER)
            ->setParameter('id', $id, ParameterType::INTEGER);

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
