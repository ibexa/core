<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pagination;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @template TSearchResultAdapter of \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter
 */
abstract class BaseSearchResultAdapterTestCase extends TestCase
{
    protected const int EXAMPLE_LIMIT = 40;
    protected const int EXAMPLE_OFFSET = 10;

    protected const array EXAMPLE_LANGUAGE_FILTER = [
        'languages' => ['eng-GB', 'pol-PL'],
        'useAlwaysAvailable' => true,
    ];

    protected const float EXAMPLE_RESULT_MAX_SCORE = 5.123;
    protected const float EXAMPLE_RESULT_TIME = 30.0;

    protected SearchService & MockObject $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->searchService = $this->createMock(SearchService::class);
    }

    abstract public function testGetNbResults(): void;

    abstract public function testGetSlice(): void;

    abstract public function testGetAggregations(): void;

    /**
     * @phpstan-param TSearchResultAdapter $adapter
     */
    protected function assertSearchResult(
        int $nbResults,
        SearchResultAdapter $adapter,
        AggregationResultCollection $aggregationsResults
    ): void {
        self::assertSame($nbResults, $adapter->getNbResults());
        self::assertSame($aggregationsResults, $adapter->getAggregations());
        self::assertSame(self::EXAMPLE_RESULT_MAX_SCORE, $adapter->getMaxScore());
        self::assertTrue($adapter->getTimedOut());
        self::assertSame(self::EXAMPLE_RESULT_TIME, $adapter->getTime());
    }
}
