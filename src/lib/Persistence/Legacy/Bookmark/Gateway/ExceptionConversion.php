<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\Bookmark\Bookmark;
use Ibexa\Contracts\Core\Persistence\Content\Location;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway;

class ExceptionConversion extends Gateway
{
    protected DoctrineDatabase $innerGateway;

    public function __construct(DoctrineDatabase $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function insertBookmark(Bookmark $bookmark): int
    {
        try {
            return $this->innerGateway->insertBookmark($bookmark);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteBookmark(int $id): void
    {
        try {
            $this->innerGateway->deleteBookmark($id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadBookmarkDataByUserIdAndLocationId(int $userId, array $locationIds): array
    {
        try {
            return $this->innerGateway->loadBookmarkDataByUserIdAndLocationId($userId, $locationIds);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUserBookmarks(int $userId, int $offset = 0, int $limit = -1): array
    {
        try {
            return $this->innerGateway->loadUserBookmarks($userId, $offset, $limit);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countUserBookmarks(int $userId): int
    {
        try {
            return $this->innerGateway->countUserBookmarks($userId);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function locationSwapped(int $location1Id, int $location2Id): void
    {
        try {
            $this->innerGateway->locationSwapped($location1Id, $location2Id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUserIdsByLocation(Location $location): array
    {
        try {
            return $this->innerGateway->loadUserIdsByLocation($location);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
