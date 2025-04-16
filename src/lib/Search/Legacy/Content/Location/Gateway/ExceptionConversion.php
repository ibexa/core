<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway;

/**
 * Base class for location gateways.
 */
class ExceptionConversion extends Gateway
{
    protected DoctrineDatabase $innerGateway;

    public function __construct(DoctrineDatabase $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function find(
        CriterionInterface $criterion,
        int $offset,
        int $limit,
        array $sortClauses = null,
        array $languageFilter = [],
        bool $doCount = true
    ): array {
        try {
            return $this->innerGateway->find($criterion, $offset, $limit, $sortClauses, $languageFilter, $doCount);
        } catch (DBALException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
