<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway;

use Doctrine\DBAL\Exception as DBALException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Search\Legacy\Content\Gateway;
use PDOException;

/**
 * The Content Search Gateway provides the implementation for one database to
 * retrieve the desired content objects.
 */
class ExceptionConversion extends Gateway
{
    /**
     * @var Gateway
     */
    protected $innerGateway;

    public function __construct(Gateway $innerGateway)
    {
        $this->innerGateway = $innerGateway;
    }

    public function find(
        CriterionInterface $criterion,
        $offset = 0,
        $limit = null,
        ?array $sort = null,
        array $languageFilter = [],
        $doCount = true
    ): array {
        try {
            return $this->innerGateway->find($criterion, $offset, $limit, $sort, $languageFilter, $doCount);
        } catch (DBALException | PDOException $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
