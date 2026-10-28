<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location\Priority;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * @internal for internal use by Repository Filtering
 */
final class PriorityQueryBuilder extends BaseLocationCriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof Priority;
    }

    /**
     * @throws Exception
     */
    protected function buildLocationConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion,
        string $locationAlias
    ): string {
        /** @var Priority $criterion */
        return $queryBuilder->buildOperatorBasedCriterionConstraint(
            "{$locationAlias}.priority",
            $criterion->value,
            $criterion->operator
        );
    }
}
