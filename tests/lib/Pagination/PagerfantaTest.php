<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Pagination;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Core\Pagination\Pagerfanta\Pagerfanta;
use Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @template TSearchResultAdapter
 */
final class PagerfantaTest extends TestCase
{
    private const float EXAMPLE_TIME_RESULT = 30.0;
    private const float EXAMPLE_MAX_SCORE_RESULT = 5.12354;

    /** @phpstan-var SearchResultAdapter<TSearchResultAdapter> & MockObject */
    private SearchResultAdapter & MockObject $adapter;

    /** @var Pagerfanta<TSearchResultAdapter> */
    private Pagerfanta $pagerfanta;

    protected function setUp(): void
    {
        $this->adapter = $this->createMock(SearchResultAdapter::class);
        $this->pagerfanta = new Pagerfanta($this->adapter);
    }

    public function testGetAggregations(): void
    {
        $aggregations = new AggregationResultCollection();

        $this->adapter->method('getAggregations')->willReturn($aggregations);

        self::assertEquals(
            $aggregations,
            $this->pagerfanta->getAggregations()
        );
    }

    public function testGetTime(): void
    {
        $this->adapter->method('getTime')->willReturn(self::EXAMPLE_TIME_RESULT);

        self::assertEquals(
            self::EXAMPLE_TIME_RESULT,
            $this->pagerfanta->getTime()
        );
    }

    public function testGetTimedOut(): void
    {
        $this->adapter->method('getTimedOut')->willReturn(true);

        self::assertTrue(
            $this->pagerfanta->getTimedOut()
        );
    }

    public function testGetMaxScore(): void
    {
        $this->adapter->method('getMaxScore')->willReturn(self::EXAMPLE_MAX_SCORE_RESULT);

        self::assertEquals(
            self::EXAMPLE_MAX_SCORE_RESULT,
            $this->pagerfanta->getMaxScore()
        );
    }
}
