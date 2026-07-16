<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\User;

use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsUserBased;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\User\Gateway;

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
     * @throws Exception
     */
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        /** @var IsUserBased $criterion */
        // intentionally not using parent buildQueryConstraint
        $queryBuilder
            ->leftJoinOnce(
                'content',
                Gateway::USER_TABLE,
                'user_storage',
                'content.id = user_storage.contentobject_id'
            );

        $isUserBased = (bool)reset($criterion->value);
        $databasePlatform = $queryBuilder->getConnection()->getDatabasePlatform();

        return $isUserBased
            ? $databasePlatform->getIsNotNullExpression('user_storage.contentobject_id')
            : $databasePlatform->getIsNullExpression('user_storage.contentobject_id');
    }
}
