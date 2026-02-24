<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Bookmark;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\BookmarkId;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder;

final class IdSortClauseQueryBuilder implements SortClauseQueryBuilder
{
    public function accepts(FilteringSortClause $sortClause): bool
    {
        return $sortClause instanceof BookmarkId;
    }

    public function buildQuery(
        FilteringQueryBuilder $queryBuilder,
        FilteringSortClause $sortClause
    ): void {
        if (!$sortClause instanceof BookmarkId) {
            throw new \InvalidArgumentException(sprintf(
                'Expected %s, got %s',
                BookmarkId::class,
                get_class($sortClause),
            ));
        }
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\BookmarkId $sortClause */
        $queryBuilder->addSelect('bookmark.id');
        $queryBuilder->addOrderBy('bookmark.id', $sortClause->direction);
    }
}
