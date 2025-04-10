<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\URL\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Persistence\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\URL\Gateway;

class ExceptionConversion extends Gateway
{
    protected DoctrineDatabase $innerGateway;

    public function __construct(DoctrineDatabase $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function updateUrl(URL $url): void
    {
        try {
            $this->innerGateway->updateUrl($url);
        } catch (DBALException $e) {
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
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function findUsages(int $id): array
    {
        try {
            return $this->innerGateway->findUsages($id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlData(int $id): array
    {
        try {
            return $this->innerGateway->loadUrlData($id);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }

    public function loadUrlDataByUrl(string $url): array
    {
        try {
            return $this->innerGateway->loadUrlDataByUrl($url);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
