<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\SortClauseQueryBuilder;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;

/**
 * @internal
 */
abstract class BaseLocationSortClauseQueryBuilder implements SortClauseQueryBuilder
{
    private const CONTENT_SORT_LOCATION_ALIAS = 'ibexa_sort_location';

    private string $locationAlias = self::CONTENT_SORT_LOCATION_ALIAS;

    private bool $needsMainLocationJoin = true;

    public function buildQuery(
        FilteringQueryBuilder $queryBuilder,
        FilteringSortClause $sortClause
    ): void {
        $this->prepareLocationAlias($queryBuilder);

        $sort = $this->getSortingExpression($this->locationAlias);
        $queryBuilder->addSelect($sort);

        if ($this->needsMainLocationJoin) {
            $this->joinMainLocationOnly($queryBuilder, $this->locationAlias);
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause */
        $queryBuilder->addOrderBy($sort, $sortClause->direction);
    }

    private function prepareLocationAlias(FilteringQueryBuilder $queryBuilder): void
    {
        if ($this->isLocationFilteringContext($queryBuilder)) {
            $queryBuilder->joinAllLocations();
            $this->locationAlias = 'location';
            $this->needsMainLocationJoin = false;

            return;
        }

        $this->locationAlias = self::CONTENT_SORT_LOCATION_ALIAS;
        $this->needsMainLocationJoin = true;
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

    private function joinMainLocationOnly(FilteringQueryBuilder $queryBuilder, string $alias): void
    {
        $queryBuilder->joinOnce(
            'content',
            LocationGateway::CONTENT_TREE_TABLE,
            $alias,
            (string)$queryBuilder->expr()->andX(
                sprintf('content.id = %s.contentobject_id', $alias),
                sprintf('%s.node_id = %s.main_node_id', $alias, $alias)
            )
        );
    }

    abstract protected function getSortingExpression(string $locationAlias): string;
}

class_alias(BaseLocationSortClauseQueryBuilder::class, 'eZ\Publish\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\BaseLocationSortClauseQueryBuilder');
