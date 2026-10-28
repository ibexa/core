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

use function sprintf;

/**
 * In Location filtering, the constraint applies to the filtered Location itself.
 * In Content filtering, it matches Content items having at least one Location meeting it,
 * the same way as Content search does, so that each Content item is returned once.
 *
 * @internal for internal use by Repository Filtering
 */
abstract class BaseLocationCriterionQueryBuilder implements CriterionQueryBuilder
{
    private const string CONTENT_FILTERING_LOCATION_ALIAS = 'filter_location';

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        if ($this->isLocationFilteringContext($queryBuilder)) {
            return $this->buildLocationConstraint($queryBuilder, $criterion, 'location');
        }

        // Built as a plain string: DBAL 3 QueryBuilder::getSQL() requires a database platform,
        // which is not needed for a sub-query of this shape.
        return sprintf(
            'content.id IN (SELECT %1$s.contentobject_id FROM %2$s %1$s WHERE %3$s)',
            self::CONTENT_FILTERING_LOCATION_ALIAS,
            LocationGateway::CONTENT_TREE_TABLE,
            $this->buildLocationConstraint($queryBuilder, $criterion, self::CONTENT_FILTERING_LOCATION_ALIAS)
        );
    }

    /**
     * Builds the constraint on the Location table available under the given alias.
     */
    abstract protected function buildLocationConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion,
        string $locationAlias
    ): string;

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
