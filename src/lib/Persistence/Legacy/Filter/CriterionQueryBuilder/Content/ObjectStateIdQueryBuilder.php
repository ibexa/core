<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ObjectStateId;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;

/**
 * @internal for internal use by Repository Filtering
 */
final class ObjectStateIdQueryBuilder implements CriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof ObjectStateId;
    }

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        /** @var ObjectStateId $criterion */
        $value = (array)$criterion->value;

        $subQueryBuilder = new QueryBuilder($queryBuilder->getConnection());
        $subQueryBuilder
            ->select('osl.contentobject_id')
            ->from(Gateway::OBJECT_STATE_LINK_TABLE, 'osl')
            ->where(
                $subQueryBuilder->expr()->in(
                    'osl.contentobject_state_id',
                    $queryBuilder->createNamedParameter($value, Connection::PARAM_INT_ARRAY)
                )
            );

        return $queryBuilder->expr()->in('content.id', $subQueryBuilder->getSQL());
    }
}

class_alias(ObjectStateIdQueryBuilder::class, 'eZ\Publish\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content\ObjectStateIdQueryBuilder');
