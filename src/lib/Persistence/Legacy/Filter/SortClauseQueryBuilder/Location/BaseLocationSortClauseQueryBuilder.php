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
    private const SORT_FIELD_ALIAS_PREFIX = 'ibexa_filter_sort_';

    private string $locationAlias = self::CONTENT_SORT_LOCATION_ALIAS;

    private bool $needsMainLocationJoin = true;

    public function buildQuery(
        FilteringQueryBuilder $queryBuilder,
        FilteringSortClause $sortClause
    ): void {
        $this->prepareLocationAlias($queryBuilder);

        $sort = $this->getSortingExpressionForAlias($this->locationAlias);
        $sortAlias = $this->getSortFieldAlias($sort);
        $queryBuilder->addSelect(sprintf('%s AS %s', $sort, $sortAlias));

        if ($this->needsMainLocationJoin) {
            $this->joinMainLocationOnly($queryBuilder, $this->locationAlias);
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause $sortClause */
        $queryBuilder->addOrderBy($sortAlias, $sortClause->direction);
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

    /**
     * Legacy entry point: implementations are expected to override this.
     */
    abstract protected function getSortingExpression(): string;

    /**
     * Optional alias-aware override; default falls back to legacy expression with alias swap.
     */
    protected function getSortingExpressionForAlias(string $locationAlias): string
    {
        $expression = $this->getSortingExpression();

        if ($locationAlias === 'location') {
            return $expression;
        }

        $replaced = preg_replace('/\\blocation\\./', sprintf('%s.', $locationAlias), $expression);

        return $replaced ?? $expression;
    }

    protected function getSortFieldAlias(string $sortExpression): string
    {
        return self::SORT_FIELD_ALIAS_PREFIX . $this->getSortFieldName($sortExpression);
    }

    protected function getSortFieldName(string $sortExpression): string
    {
        return str_replace('.', '_', $sortExpression);
    }
}

class_alias(BaseLocationSortClauseQueryBuilder::class, 'eZ\Publish\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\BaseLocationSortClauseQueryBuilder');
