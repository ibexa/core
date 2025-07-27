<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pagination;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Core\Pagination\Pagerfanta\ContentSearchHitAdapter;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @extends \Ibexa\Tests\Core\Pagination\BaseContentSearchResultAdapterTestCase<\Ibexa\Core\Pagination\Pagerfanta\ContentSearchHitAdapter>
 */
final class ContentSearchHitAdapterTest extends BaseContentSearchResultAdapterTestCase
{
    protected SearchService & MockObject $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = $this->createMock(SearchService::class);
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    protected function getAdapter(
        Query $query,
        SearchService $searchService,
        array $languageFilter = []
    ): ContentSearchHitAdapter {
        return new ContentSearchHitAdapter($query, $searchService, $languageFilter);
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
        $aggregationsResults = new AggregationResultCollection();
        $nbResults = 123;

        $query = $this->createTestQuery(self::EXAMPLE_OFFSET, self::EXAMPLE_LIMIT);
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
        $exceptedAggregationsResults = new AggregationResultCollection();

        $query = $this->mockQueryForGetAggregations($exceptedAggregationsResults);

        $adapter = $this->getAdapter($query, $this->searchService, self::EXAMPLE_LANGUAGE_FILTER);

        self::assertSame($exceptedAggregationsResults, $adapter->getAggregations());
        // Running a 2nd time to ensure SearchService::findContent() is called only once.
        self::assertSame($exceptedAggregationsResults, $adapter->getAggregations());
    }
}
