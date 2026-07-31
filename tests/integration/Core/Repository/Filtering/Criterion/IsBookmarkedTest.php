<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Tests\Integration\Core\Repository\BaseTest;

class IsBookmarkedTest extends BaseTest
{
    public function testBookmarkedAndNotBookmarkedCountsMatchTotal(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();

        $baseFilter = new Filter();
        $totalCount = $locationService->count($baseFilter);

        $bookmarkedFilter = clone $baseFilter;
        $bookmarkedFilter->withCriterion(new Criterion\IsBookmarked(true));
        $bookmarkedCount = $locationService->count($bookmarkedFilter);

        $notBookmarkedFilter = clone $baseFilter;
        $notBookmarkedFilter->withCriterion(new Criterion\IsBookmarked(false));
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
        return [
            'bookmarked=true' => [true, 0, 1, 0],
            'bookmarked=false' => [false, 1, 0, 1],
        ];
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

        $filesLocation = $locationService->loadLocation(52);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(52));

        $locations = $locationService->find($filter);
        self::assertCount(
            $initialCount,
            $locations,
            'Unexpected initial bookmark state for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->createBookmark($filesLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(52));

        $locations = $locationService->find($filter);
        self::assertCount(
            $afterCreateCount,
            $locations,
            'Unexpected state after creating bookmark for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );

        $bookmarkService->deleteBookmark($filesLocation);

        $filter = new Filter();
        $filter->withCriterion(new Criterion\IsBookmarked($isBookmarked))
            ->andWithCriterion(new Criterion\LocationId(52));

        $locations = $locationService->find($filter);
        self::assertCount(
            $afterDeleteCount,
            $locations,
            'Unexpected state after deleting bookmark for IsBookmarked(' . ($isBookmarked ? 'true' : 'false') . ')'
        );
    }
}
