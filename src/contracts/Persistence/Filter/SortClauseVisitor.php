<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Filter;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder;

/**
 * @internal for internal use by Repository Filtering.
 * Visits instances of {@see SortClauseQueryBuilder}.
 */
interface SortClauseVisitor
{
    /**
     * @param FilteringSortClause[] $sortClauses
     */
    public function visitSortClauses(
        FilteringQueryBuilder $queryBuilder,
        array $sortClauses
    ): void;
}
