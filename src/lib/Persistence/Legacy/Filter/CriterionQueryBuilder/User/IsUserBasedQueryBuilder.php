<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\User;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsUserBased;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * @internal for internal use by Repository Filtering
 */
final class IsUserBasedQueryBuilder extends BaseUserCriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof IsUserBased;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsUserBased $criterion
     */
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): string {
        // intentionally not using parent buildQueryConstraint
        $queryBuilder
            ->leftJoinOnce(
                'content',
                'ezuser',
                'user_storage',
                'content.id = user_storage.contentobject_id'
            );

        $isUserBased = (bool)reset($criterion->value);

        return $isUserBased
            ? 'user_storage.contentobject_id IS NOT NULL'
            : 'user_storage.contentobject_id IS NULL';
    }
}
