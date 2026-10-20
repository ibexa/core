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
use Ibexa\Core\Pagination\Pagerfanta\LocationSearchHitAdapter;

/**
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @extends \Ibexa\Tests\Core\Pagination\BaseLocationSearchResultAdapterTestCase<\Ibexa\Core\Pagination\Pagerfanta\LocationSearchHitAdapter>
 */
final class LocationSearchHitAdapterTest extends BaseLocationSearchResultAdapterTestCase
{
    /**
     * Returns the adapter to test.
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    protected function getAdapter(
        LocationQuery $query,
        SearchService $searchService,
        array $languageFilter = []
    ): LocationSearchHitAdapter {
        return new LocationSearchHitAdapter($query, $searchService, $languageFilter);
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
            $hits,
            $adapter->getSlice(self::EXAMPLE_OFFSET, self::EXAMPLE_LIMIT)
        );

        $this->assertSearchResult($nbResults, $adapter, $aggregationsResults);
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
