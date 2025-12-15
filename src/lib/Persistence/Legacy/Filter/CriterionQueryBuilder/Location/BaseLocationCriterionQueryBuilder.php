<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;

/**
 * @internal for internal use by Repository Filtering
 */
abstract class BaseLocationCriterionQueryBuilder implements CriterionQueryBuilder
{
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        if ($this->isLocationFilteringContext($queryBuilder)) {
            return null;
        }

        $expressionBuilder = $queryBuilder->expr();
        $queryBuilder->joinOnce(
            'content',
            LocationGateway::CONTENT_TREE_TABLE,
            'location',
            (string)$expressionBuilder->andX(
                'content.id = location.contentobject_id',
                'location.node_id = location.main_node_id'
            )
        );

        return null;
    }

    private function isLocationFilteringContext(FilteringQueryBuilder $queryBuilder): bool
    {
        $fromParts = $queryBuilder->getQueryPart('from');
        foreach ($fromParts as $fromPart) {
            if (($fromPart['alias'] ?? null) === 'location') {
                return true;
            }
        }

        return false;
    }
}
