<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine;

use Doctrine\DBAL\Query\QueryBuilder;
use WeakMap;

/**
 * Tracks, per QueryBuilder instance, which table names/aliases have already been joined,
 * so callers building queries incrementally can avoid joining the same table twice.
 */
final class JoinedTablesTracker
{
    /** @var \WeakMap<QueryBuilder, array<string, true>> */
    private WeakMap $joinedTablesByQueryBuilder;

    public function __construct()
    {
        $this->joinedTablesByQueryBuilder = new WeakMap();
    }

    /**
     * Marks $tableIdentifier as joined for $queryBuilder.
     *
     * @return bool true if the table was not already joined (i.e. the caller still needs to perform the join)
     */
    public function markTableAsJoined(QueryBuilder $queryBuilder, string $tableIdentifier): bool
    {
        $joined = $this->getJoinedTables($queryBuilder);
        if (isset($joined[$tableIdentifier])) {
            return false;
        }

        $joined[$tableIdentifier] = true;
        $this->joinedTablesByQueryBuilder[$queryBuilder] = $joined;

        return true;
    }

    /**
     * @return array<string, true>
     */
    private function getJoinedTables(QueryBuilder $queryBuilder): array
    {
        return $this->joinedTablesByQueryBuilder[$queryBuilder] ?? [];
    }
}
