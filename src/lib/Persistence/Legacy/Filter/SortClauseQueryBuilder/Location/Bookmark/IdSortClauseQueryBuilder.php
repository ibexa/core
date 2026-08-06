<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Location\Bookmark\Id;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;

/**
 * @internal
 */
final class IdSortClauseQueryBuilder implements SortClauseQueryBuilder
{
    private const ALIAS = 'ibexa_sort_bookmark';

    private PermissionResolver $permissionResolver;

    public function __construct(PermissionResolver $permissionResolver)
    {
        $this->permissionResolver = $permissionResolver;
    }

    public function accepts(FilteringSortClause $sortClause): bool
    {
        return $sortClause instanceof Id;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function buildQuery(
        FilteringQueryBuilder $queryBuilder,
        FilteringSortClause $sortClause
    ): void {
        if (!$sortClause instanceof Id) {
            throw new InvalidArgumentException(
                '$sortClause',
                sprintf('Expected %s, got %s', Id::class, get_class($sortClause))
            );
        }

        $userId = $this->permissionResolver->getCurrentUserReference()->getUserId();

        $queryBuilder->leftJoinOnce(
            'location',
            DoctrineDatabase::TABLE_BOOKMARKS,
            self::ALIAS,
            (string)$queryBuilder->expr()->and(
                sprintf('location.node_id = %s.node_id', self::ALIAS),
                $queryBuilder->expr()->eq(
                    sprintf('%s.%s', self::ALIAS, DoctrineDatabase::COLUMN_USER_ID),
                    $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                )
            )
        );

        $queryBuilder->addSelect(self::ALIAS . '.id');
        $queryBuilder->addOrderBy(self::ALIAS . '.id', $sortClause->direction);
    }
}
