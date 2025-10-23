<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pagination;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Core\Pagination\Pagerfanta\LocationSearchAdapter;

/**
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @extends \Ibexa\Tests\Core\Pagination\BaseLocationSearchResultAdapterTestCase<\Ibexa\Core\Pagination\Pagerfanta\LocationSearchAdapter>
 */
final class LocationSearchAdapterTest extends BaseLocationSearchResultAdapterTestCase
{
    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    protected function getAdapter(
        LocationQuery $query,
        SearchService $searchService,
        array $languageFilter = []
    ): LocationSearchAdapter {
        return new LocationSearchAdapter($query, $searchService, $languageFilter);
    }

    public function testGetNbResults(): void
    {
        $nbResults = 123;
        $query = $this->mockQueryForGetNbResults($nbResults);

        $adapter = $this->getAdapter($query, $this->searchService, self::EXAMPLE_LANGUAGE_FILTER);

        self::assertSame($nbResults, $adapter->getNbResults());
        // Running a 2nd time to ensure SearchService::findContent() is called only once.
        self::assertSame($nbResults, $adapter->getNbResults());
    }

    public function testGetSlice(): void
    {
        $nbResults = 123;
        $aggregationsResults = new AggregationResultCollection();
        $query = $this->createTestQuery();
        $hits = $this->mockSearchHitsForGetSlice($query, $nbResults, $aggregationsResults);

        $adapter = $this->getAdapter($query, $this->searchService, self::EXAMPLE_LANGUAGE_FILTER);

        self::assertSame(
            array_map(static fn (SearchHit $hit) => $hit->valueObject, $hits),
            $adapter->getSlice(self::EXAMPLE_OFFSET, self::EXAMPLE_LIMIT)
        );

        self::assertSame($nbResults, $adapter->getNbResults());
        self::assertSame($aggregationsResults, $adapter->getAggregations());
        self::assertSame(self::EXAMPLE_RESULT_MAX_SCORE, $adapter->getMaxScore());
        self::assertTrue($adapter->getTimedOut());
        self::assertSame(self::EXAMPLE_RESULT_TIME, $adapter->getTime());
    }

    public function testGetAggregations(): void
    {
        $expectedAggregationsResults = new AggregationResultCollection();

        $query = $this->mockQueryForGetAggregations($expectedAggregationsResults);

        $adapter = $this->getAdapter($query, $this->searchService, self::EXAMPLE_LANGUAGE_FILTER);

        self::assertSame($expectedAggregationsResults, $adapter->getAggregations());
        // Running a 2nd time to ensure SearchService::findContent() is called only once.
        self::assertSame($expectedAggregationsResults, $adapter->getAggregations());
    }
}
