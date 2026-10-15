<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;
use PDOException;

/**
 * @internal Internal exception conversion layer.
 */
final class ExceptionConversion extends Gateway
{
    /**
     * The wrapped gateway.
     *
     * @var Gateway
     */
    private $innerGateway;

    /**
     * Create a new exception conversion gateway around $innerGateway.
     *
     * @param Gateway $innerGateway
     */
    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function insertUrlWildcard(UrlWildcard $urlWildcard): int
    {
        try {
            return $this->innerGateway->insertUrlWildcard($urlWildcard);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function updateUrlWildcard(
        int $id,
        string $sourceUrl,
        string $destinationUrl,
        bool $forward
    ): void {
        try {
            $this->innerGateway->updateUrlWildcard(
                $id,
                $sourceUrl,
                $destinationUrl,
                $forward
            );
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function deleteUrlWildcard(int $id): void
    {
        try {
            $this->innerGateway->deleteUrlWildcard($id);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlWildcardData(int $id): array
    {
        try {
            return $this->innerGateway->loadUrlWildcardData($id);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlWildcardsData(
        int $offset = 0,
        int $limit = -1
    ): array {
        try {
            return $this->innerGateway->loadUrlWildcardsData($offset, $limit);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function find(
        Criterion $criterion,
        int $offset,
        int $limit,
        array $sortClauses = [],
        bool $doCount = true
    ): array {
        try {
            return $this->innerGateway->find($criterion, $offset, $limit, $sortClauses, $doCount);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlWildcardBySourceUrl(string $sourceUrl): array
    {
        try {
            return $this->innerGateway->loadUrlWildcardBySourceUrl($sourceUrl);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function countAll(): int
    {
        try {
            return $this->innerGateway->countAll();
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
