<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering\Criterion;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\IsBookmarkedQueryBuilder
 */
final class IsBookmarkedTest extends BaseTest
{
    private const BOOKMARKED_LOCATION_ID = 52;

    /**
     * @return iterable<string, array{string}>
     */
    public function serviceProvider(): iterable
    {
        yield 'Location' => ['getLocationService'];
        yield 'Content' => ['getContentService'];
    }

    public function testBookmarkedAndNotBookmarkedCountsMatchTotal(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();

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
     * @return iterable<string, array{string, bool, int, int, int}>
     */
    public function isBookmarkedProvider(): iterable
    {
        // [initialCount, afterCreateCount, afterDeleteCount] per isBookmarkedCriterion value
        foreach ($this->serviceProvider() as $serviceLabel => [$serviceGetter]) {
            yield "$serviceLabel, bookmarked=true" => [$serviceGetter, true, 0, 1, 0];
            yield "$serviceLabel, bookmarked=false" => [$serviceGetter, false, 1, 0, 1];
        }
    }

    /**
     * @dataProvider isBookmarkedProvider
     */
    public function testIsBookmarkedTrueAndFalse(
        string $serviceGetter,
        bool $isBookmarked,
        int $initialCount,
        int $afterCreateCount,
        int $afterDeleteCount
    ): void {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $service = $repository->$serviceGetter();
        $bookmarkService = $repository->getBookmarkService();

        $bookmarkedLocation = $locationService->loadLocation(self::BOOKMARKED_LOCATION_ID);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $initialCount,
            $service->find($filter),
            'Unexpected initial bookmark state for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->createBookmark($bookmarkedLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $afterCreateCount,
            $service->find($filter),
            'Unexpected state after creating bookmark for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->deleteBookmark($bookmarkedLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\Location\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(self::BOOKMARKED_LOCATION_ID));

        self::assertCount(
            $afterDeleteCount,
            $service->find($filter),
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
                    return $location->id;
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
}
