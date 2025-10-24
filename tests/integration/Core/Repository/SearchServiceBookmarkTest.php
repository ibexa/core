<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\BookmarkService;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class SearchServiceBookmarkTest extends RepositorySearchTestCase
{
    private const FOLDER_CONTENT_TYPE_IDENTIFIER = 'folder';
    private const MEDIA_CONTENT_TYPE_ID = 43;
    private const ALL_BOOKMARKED_LOCATIONS = 6;
    private const ALL_BOOKMARKED_CONTENT_REMOTE_IDS = [
        '1bb4fe25487f05527efa8bfd394cecc7',
        '3c160cca19fb135f83bd02d911f04db2',
        '5f7f0bdb3381d6a461d8c29ff53d908f',
        '9b47a45624b023b1a76c73b74d704acf',
        'a6e35cbcb7cd6ae4b691f3eee30cd262',
        'f5c88a2209584891056f987fd965b0ba',
    ];
    private const ALL_NO_BOOKMARKED_CONTENT_REMOTE_IDS = [
        '08799e609893f7aba22f10cb466d9cc8',
        '09082deb98662a104f325aaa8c4933d3',
        '14e4411b264a6194a33847843919451a',
        '15b256dbea2ae72418ff5facc999e8f9',
        '241d538ce310074e602f29f49e44e938',
        '27437f3547db19cf81a33c92578b2c89',
        '732a5acd01b51a6fe6eab448ad4138a9',
        '8a9c9c761004866fb458d89910f52bee',
        '8b8b22fe3c6061ed500fbd2b377b885f',
        'e7ff633c6b8e0fd3531e74c6e712bead',
        'f8cc7a4cf8a964a1a0ea6666f5da7d0d',
        'faaeb9be3bd98ed09f606fc16d144eca',
    ];
    private const MEDIA_CONTENT_REMOTE_ID = 'a6e35cbcb7cd6ae4b691f3eee30cd262';

    protected function setUp(): void
    {
        parent::setUp();

        $this->addTestContentToBookmark();

        $this->refreshSearch();
    }

    /**
     * @dataProvider provideDataForTestCriterion
     *
     * @param array<Criterion> $criteria
     * @param array<string> $remoteIds
     */
    public function testCriterion(
        int $expectedCount,
        array $criteria,
        array $remoteIds
    ): void {
        $query = $this->createQuery($criteria);

        $this->assertExpectedSearchHits($expectedCount, $remoteIds, $query);
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
                new Criterion\Location\IsBookmarked(),
            ],
            self::ALL_BOOKMARKED_CONTENT_REMOTE_IDS,
        ];

        yield 'All bookmarked locations limited to folder content type' => [
            1,
            [
                new Criterion\ContentTypeIdentifier(self::FOLDER_CONTENT_TYPE_IDENTIFIER),
                new Criterion\Location\IsBookmarked(),
            ],
            [self::MEDIA_CONTENT_REMOTE_ID],
        ];

        yield 'All bookmarked locations limited to user group content type' => [
            4,
            [
                new Criterion\ContentTypeIdentifier('user_group'),
                new Criterion\Location\IsBookmarked(),
            ],
            [
                '3c160cca19fb135f83bd02d911f04db2',
                '5f7f0bdb3381d6a461d8c29ff53d908f',
                '9b47a45624b023b1a76c73b74d704acf',
                'f5c88a2209584891056f987fd965b0ba',
            ],
        ];

        yield 'All bookmarked locations limited to user content type' => [
            1,
            [
                new Criterion\ContentTypeIdentifier('user'),
                new Criterion\Location\IsBookmarked(),
            ],
            ['1bb4fe25487f05527efa8bfd394cecc7'],
        ];

        yield 'All no bookmarked locations' => [
            12,
            [
                new Criterion\Location\IsBookmarked(false),
            ],
            self::ALL_NO_BOOKMARKED_CONTENT_REMOTE_IDS,
        ];
    }

    public function testCriterionDeleteBookmark(): void
    {
        $query = $this->createQuery(
            [
                new Criterion\Location\IsBookmarked(),
            ]
        );

        $this->assertExpectedSearchHits(
            self::ALL_BOOKMARKED_LOCATIONS,
            self::ALL_BOOKMARKED_CONTENT_REMOTE_IDS,
            $query
        );

        $mediaLocation = $this->loadMediaFolderLocation();

        // Delete bookmark, number of search hits should be changed
        $this
            ->getBookmarkService()
            ->deleteBookmark($mediaLocation);

        $this->refreshSearch();

        $this->assertExpectedSearchHits(
            5,
            [
                '1bb4fe25487f05527efa8bfd394cecc7',
                '3c160cca19fb135f83bd02d911f04db2',
                '5f7f0bdb3381d6a461d8c29ff53d908f',
                '9b47a45624b023b1a76c73b74d704acf',
                'f5c88a2209584891056f987fd965b0ba',
            ],
            $query
        );
    }

    /**
     * @param array<string> $expectedRemoteIds
     */
    private function assertExpectedSearchHits(
        int $expectedCount,
        array $expectedRemoteIds,
        LocationQuery $query
    ): void {
        $searchHits = self::getSearchService()->findLocations($query);

        self::assertSame($expectedCount, $searchHits->totalCount);

        $remoteIds = $this->extractRemoteIds($searchHits);

        self::assertSame($expectedRemoteIds, $remoteIds);
    }

    /**
     * @return array<string>
     */
    private function extractRemoteIds(SearchResult $result): array
    {
        $remoteIds = array_map(
            static function (SearchHit $searchHit): string {
                /** @var Location $location */
                $location = $searchHit->valueObject;

                return $location->getContentInfo()->remoteId;
            },
            $result->searchHits
        );

        sort($remoteIds);

        return $remoteIds;
    }

    /**
     * @param array<Criterion> $criteria
     *
     * @throws InvalidCriterionArgumentException
     */
    private function createQuery(array $criteria): LocationQuery
    {
        $query = new LocationQuery();
        $query->filter = new Criterion\LogicalAnd(
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
