<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationList;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use IteratorAggregate;

/**
 * @internal
 */
final class LocationFilteringTest extends BaseRepositoryFilteringTestCase
{
    private const BOOKMARKED_LOCATION_ID = 52;

    public function testBookmarkedAndNotBookmarkedCountsMatchTotal(): void
    {
        $locationService = $this->getRepository(false)->getLocationService();

        $baseFilter = new Filter();
        $totalCount = $locationService->count($baseFilter);

        $bookmarkedFilter = clone $baseFilter;
        $bookmarkedFilter->withCriterion(new Criterion\Location\IsBookmarked(true));
        $bookmarkedCount = $locationService->count($bookmarkedFilter);

        $notBookmarkedFilter = clone $baseFilter;
        $notBookmarkedFilter->withCriterion(new Criterion\Location\IsBookmarked(false));
        $notBookmarkedCount = $locationService->count($notBookmarkedFilter);

        self::assertSame(
            $totalCount,
            $bookmarkedCount + $notBookmarkedCount,
            sprintf(
                'Mismatch: total=%d, bookmarked=%d, notBookmarked=%d',
                $totalCount,
                $bookmarkedCount,
                $notBookmarkedCount
            )
        );
    }

    /**
     * @return iterable<string, array{bool, int, int, int}>
     */
    public function isBookmarkedProvider(): iterable
    {
        // [isBookmarkedCriterion, initialCount, afterCreateCount, afterDeleteCount]
        yield 'bookmarked=true' => [true, 0, 1, 0];
        yield 'bookmarked=false' => [false, 1, 0, 1];
    }

    /**
     * @dataProvider isBookmarkedProvider
     */
    public function testIsBookmarkedTrueAndFalse(
        bool $isBookmarked,
        int $initialCount,
        int $afterCreateCount,
        int $afterDeleteCount
    ): void {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $bookmarkService = $repository->getBookmarkService();

        $bookmarkedLocation = $locationService->loadLocation(self::BOOKMARKED_LOCATION_ID);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $initialCount,
            $locationService->find($filter),
            'Unexpected initial bookmark state for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->createBookmark($bookmarkedLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $afterCreateCount,
            $locationService->find($filter),
            'Unexpected state after creating bookmark for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->deleteBookmark($bookmarkedLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $afterDeleteCount,
            $locationService->find($filter),
            'Unexpected state after deleting bookmark for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );
    }

    public function testLogicalOrOfBookmarkedAndNotBookmarkedMatchesEveryLocationOnce(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $bookmarkService = $repository->getBookmarkService();

        $bookmarkedLocation = $locationService->loadLocation(self::BOOKMARKED_LOCATION_ID);
        $bookmarkService->createBookmark($bookmarkedLocation);

        try {
            $totalCount = $locationService->count(new Filter());

            $orFilter = new Filter();
            $orFilter->withCriterion(
                new Criterion\LogicalOr([
                    new Criterion\Location\IsBookmarked(true),
                    new Criterion\Location\IsBookmarked(false),
                ])
            );

            self::assertSame($totalCount, $locationService->count($orFilter));

            $locationIds = array_map(
                static function ($location) {
                    return $location->getId();
                },
                iterator_to_array($locationService->find($orFilter))
            );

            self::assertSame(
                $totalCount,
                count(array_unique($locationIds)),
                'LogicalOr(IsBookmarked(true), IsBookmarked(false)) returned duplicate locations'
            );
        } finally {
            $bookmarkService->deleteBookmark($bookmarkedLocation);
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function compareWithSearchResults(
        Filter $filter,
        IteratorAggregate $filteredLocationList
    ): void {
        $query = $this->buildSearchQueryFromFilter($filter);
        $locationListFromSearch = $this->findUsingLocationSearch($query);
        self::assertEquals($locationListFromSearch, $filteredLocationList);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function findUsingLocationSearch(LocationQuery $query): LocationList
    {
        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();
        $searchResults = $searchService->findLocations($query);

        return new LocationList(
            [
                'totalCount' => $searchResults->totalCount,
                'locations' => array_map(
                    static function (SearchHit $searchHit) {
                        return $searchHit->valueObject;
                    },
                    $searchResults->searchHits
                ),
            ]
        );
    }

    protected function getDefaultSortClause(): FilteringSortClause
    {
        return new Query\SortClause\Location\Id();
    }

    public function getCriteriaForInitialData(): iterable
    {
        yield 'Location\\Depth=2' => new Criterion\Location\Depth(Criterion\Operator::EQ, 2);
        yield 'Location\\IsMainLocation' => new Criterion\Location\IsMainLocation(
            Criterion\Location\IsMainLocation::MAIN
        );
        yield 'Location\\Priority>0' => new Criterion\Location\Priority(Criterion\Operator::GT, 0);

        yield from parent::getCriteriaForInitialData();
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\LocationList
     */
    protected function find(Filter $filter, ?array $contextLanguages = null): iterable
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();

        return $locationService->find($filter, $contextLanguages);
    }

    protected function assertFoundContentItemsByRemoteIds(
        iterable $list,
        array $expectedContentRemoteIds
    ): void {
        foreach ($list as $location) {
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
            $contentInfo = $location->getContentInfo();
            self::assertContainsEquals(
                $contentInfo->remoteId,
                $expectedContentRemoteIds,
                sprintf(
                    'Content %d (%s) at Location %d was not supposed to be found',
                    $location->id,
                    $contentInfo->id,
                    $contentInfo->remoteId
                )
            );
        }
    }

    private function buildSearchQueryFromFilter(Filter $filter): LocationQuery
    {
        $limit = $filter->getLimit();

        return new LocationQuery(
            [
                'filter' => $filter->getCriterion(),
                'sortClauses' => $filter->getSortClauses(),
                'offset' => $filter->getOffset(),
                'limit' => $limit > 0 ? $limit : 999,
            ]
        );
    }
}

class_alias(LocationFilteringTest::class, 'eZ\Publish\API\Repository\Tests\Filtering\LocationFilteringTest');
