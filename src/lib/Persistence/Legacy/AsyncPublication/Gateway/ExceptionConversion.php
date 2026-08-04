<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\CreateStruct;
use Ibexa\Contracts\Core\Persistence\Content\AsyncPublication\UpdateStruct;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\AsyncPublication\Gateway;
use PDOException;

class ExceptionConversion extends Gateway
{
    public function __construct(
        private readonly Gateway $innerGateway
    ) {
    }

    public function insert(CreateStruct $createStruct): int
    {
        try {
            return $this->innerGateway->insert($createStruct);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function findByContentId(int $contentId): array
    {
        try {
            return $this->innerGateway->findByContentId($contentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateByContentIdAndVersion(int $contentId, int $versionNo, UpdateStruct $updateStruct): void
    {
        try {
            $this->innerGateway->updateByContentIdAndVersion($contentId, $versionNo, $updateStruct);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadAll(int $offset = 0, int $limit = -1): array
    {
        try {
            return $this->innerGateway->loadAll($offset, $limit);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countAll(): int
    {
        try {
            return $this->innerGateway->countAll();
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteByContentIdAndVersion(int $contentId, int $versionNo): void
    {
        try {
            $this->innerGateway->deleteByContentIdAndVersion($contentId, $versionNo);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function findContentIdsWithDispatchableWork(): array
    {
        try {
            return $this->innerGateway->findContentIdsWithDispatchableWork();
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function findOldestQueuedForContent(int $contentId): array
    {
        try {
            return $this->innerGateway->findOldestQueuedForContent($contentId);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function assignTransportMessageId(int $id, int $transportMessageId, int $modified): void
    {
        try {
            $this->innerGateway->assignTransportMessageId($id, $transportMessageId, $modified);
        } catch (DBALException|PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
