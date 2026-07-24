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
trait TracksJoinedTablesTrait
{
    /** @var \WeakMap<QueryBuilder, array<string, true>>|null */
    private static ?WeakMap $joinedTablesByQueryBuilder = null;

    protected function isTableJoined(QueryBuilder $queryBuilder, string $tableIdentifier): bool
    {
        return isset(self::getJoinedTables($queryBuilder)[$tableIdentifier]);
    }

    protected function markTableAsJoined(QueryBuilder $queryBuilder, string $tableIdentifier): void
    {
        $joined = self::getJoinedTables($queryBuilder);
        $joined[$tableIdentifier] = true;
        self::$joinedTablesByQueryBuilder ??= new WeakMap();
        self::$joinedTablesByQueryBuilder[$queryBuilder] = $joined;
    }

    /**
     * @return array<string, true>
     */
    private static function getJoinedTables(QueryBuilder $queryBuilder): array
    {
        self::$joinedTablesByQueryBuilder ??= new WeakMap();

        return self::$joinedTablesByQueryBuilder[$queryBuilder] ?? [];
    }
}
