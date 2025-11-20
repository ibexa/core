<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering\Fixtures;

use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\BaseLocationSortClauseQueryBuilder;

final class LegacyLocationSortQueryBuilder extends BaseLocationSortClauseQueryBuilder
{
    public function accepts(FilteringSortClause $sortClause): bool
    {
        return $sortClause instanceof LegacyLocationSortClause;
    }

    protected function getSortingExpression(): string
    {
        return 'location.depth';
    }
}
