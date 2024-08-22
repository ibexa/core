<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\BookmarkService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class SearchServiceBookmarkTest extends RepositorySearchTestCase
{
    private const FOLDER_CONTENT_TYPE_IDENTIFIER = 'folder';
    private const MEDIA_CONTENT_TYPE_ID = 43;
    private const ALL_BOOKMARKED_LOCATIONS = 6;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addTestContentToBookmark();

        $this->refreshSearch();
    }

    /**
     * @dataProvider provideDataForTestCriterion
     *
     * @param array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion> $criteria
     */
    public function testCriterion(
        int $expectedCount,
        array $criteria
    ): void {
        $query = $this->createQuery($criteria);

        $this->assertExpectedSearchHits($expectedCount, $query);
    }

    /**
     * @return iterable<array{
     *     int,
     *     array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion>
     * }>
     */
    public function provideDataForTestCriterion(): iterable
    {
        yield 'All bookmarked locations' => [
            self::ALL_BOOKMARKED_LOCATIONS,
            [
                new Query\Criterion\Location\IsBookmarked(),
            ],
        ];

        yield 'All bookmarked locations limited to folder content type' => [
            1,
            [
                new Query\Criterion\ContentTypeIdentifier(self::FOLDER_CONTENT_TYPE_IDENTIFIER),
                new Query\Criterion\Location\IsBookmarked(),
            ],
        ];

        yield 'All bookmarked locations limited to user group content type' => [
            4,
            [
                new Query\Criterion\ContentTypeIdentifier('user_group'),
                new Query\Criterion\Location\IsBookmarked(),
            ],
        ];

        yield 'All bookmarked locations limited to user content type' => [
            1,
            [
                new Query\Criterion\ContentTypeIdentifier('user'),
                new Query\Criterion\Location\IsBookmarked(),
            ],
        ];

        yield 'All no bookmarked locations' => [
            12,
            [
                new Query\Criterion\Location\IsBookmarked(false),
            ],
        ];
    }

    public function testCriterionDeleteBookmark(): void
    {
        $query = $this->createQuery(
            [
                new Query\Criterion\Location\IsBookmarked(),
            ]
        );

        $this->assertExpectedSearchHits(self::ALL_BOOKMARKED_LOCATIONS, $query);

        $mediaLocation = $this->loadMediaFolderLocation();

        // Delete bookmark, number of search hits should be changed
        $this
            ->getBookmarkService()
            ->deleteBookmark($mediaLocation);

        $this->refreshSearch();

        $this->assertExpectedSearchHits(5, $query);
    }

    private function assertExpectedSearchHits(
        int $expectedCount,
        LocationQuery $query
    ): void {
        $searchHits = self::getSearchService()->findLocations($query);

        self::assertSame(
            $expectedCount,
            $searchHits->totalCount
        );
    }

    /**
     * @param array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion> $criteria
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    private function createQuery(array $criteria): LocationQuery
    {
        $query = new LocationQuery();
        $query->filter = new Query\Criterion\LogicalAnd(
            $criteria
        );

        return $query;
    }

    public function addTestContentToBookmark(): void
    {
        $location = $this->loadMediaFolderLocation();
        $this->addLocationToBookmark($location);
    }

    private function addLocationToBookmark(Location $location): void
    {
        $this->getBookmarkService()->createBookmark($location);
    }

    private function loadMediaFolderLocation(): Location
    {
        return $this
            ->getLocationService()
            ->loadLocation(self::MEDIA_CONTENT_TYPE_ID);
    }

    private function getBookmarkService(): BookmarkService
    {
        return self::getServiceByClassName(BookmarkService::class);
    }
}
