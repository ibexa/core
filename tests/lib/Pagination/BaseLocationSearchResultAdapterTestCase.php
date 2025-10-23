<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pagination;

use Ibexa\Contracts\Core\Repository\Values\Content\Location as APILocation;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * @template TSearchResultAdapter of \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter
 *
 * @extends \Ibexa\Tests\Core\Pagination\BaseSearchResultAdapterTestCase<TSearchResultAdapter>
 */
abstract class BaseLocationSearchResultAdapterTestCase extends BaseSearchResultAdapterTestCase
{
    protected function mockQueryForGetNbResults(int $nbResults): LocationQuery
    {
        $query = $this->createTestQuery();

        // Count query will necessarily have a 0 limit.
        $countQuery = clone $query;
        $countQuery->aggregations = [];
        $countQuery->limit = 0;

        $searchResult = new SearchResult(
            [
                'totalCount' => $nbResults,
            ]
        );

        $this->searchService
            ->expects(self::once())
            ->method('findLocations')
            ->with($countQuery, self::EXAMPLE_LANGUAGE_FILTER)
            ->willReturn($searchResult)
        ;

        return $query;
    }

    /**
     * @phpstan-return list<SearchHit<ValueObject>>
     */
    protected function mockSearchHitsForGetSlice(
        LocationQuery $query,
        int $nbResults,
        AggregationResultCollection $aggregationsResults
    ): array {
        // Injected query is being cloned to modify offset/limit,
        // so we need to do the same here for our assertions.
        $searchQuery = clone $query;
        $searchQuery->offset = self::EXAMPLE_OFFSET;
        $searchQuery->limit = self::EXAMPLE_LIMIT;
        $searchQuery->performCount = false;

        $hits = [];
        for ($i = 0; $i < self::EXAMPLE_LIMIT; ++$i) {
            $hits[] = new SearchHit(
                [
                    'valueObject' => $this->createMock(APILocation::class),
                ]
            );
        }

        $searchResult = new SearchResult(
            [
                'searchHits' => $hits,
                'totalCount' => $nbResults,
                'aggregations' => $aggregationsResults,
                'maxScore' => self::EXAMPLE_RESULT_MAX_SCORE,
                'timedOut' => true,
                'time' => self::EXAMPLE_RESULT_TIME,
            ]
        );

        $this
            ->searchService
            ->expects(self::once())
            ->method('findLocations')
            ->with($searchQuery, self::EXAMPLE_LANGUAGE_FILTER)
            ->willReturn($searchResult)
        ;

        return $hits;
    }

    protected function mockQueryForGetAggregations(
        AggregationResultCollection $expectedAggregationsResults
    ): LocationQuery {
        $query = $this->createTestQuery(self::EXAMPLE_OFFSET, self::EXAMPLE_LIMIT);

        // Injected query is being cloned to modify offset/limit,
        // so we need to do the same here for our assertions.
        $aggregationQuery = clone $query;
        $aggregationQuery->offset = 0;
        $aggregationQuery->limit = 0;

        $searchResult = new SearchResult(
            [
                'searchHits' => [],
                'totalCount' => 0,
                'aggregations' => $expectedAggregationsResults,
            ]
        );

        $this
            ->searchService
            ->expects(self::once())
            ->method('findLocations')
            ->with($aggregationQuery, self::EXAMPLE_LANGUAGE_FILTER)
            ->willReturn($searchResult)
        ;

        return $query;
    }

    protected function createTestQuery(
        int $limit = 25,
        int $offset = 0
    ): LocationQuery {
        $query = new LocationQuery();
        $query->query = $this->createMock(CriterionInterface::class);
        $query->aggregations[] = $this->createMock(Aggregation::class);
        $query->sortClauses[] = $this->createMock(SortClause::class);
        $query->offset = $offset;
        $query->limit = $limit;

        return $query;
    }
}
