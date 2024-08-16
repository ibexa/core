<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\BookmarkService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Tests\Integration\Core\RepositorySearchTestCase;

final class SearchServiceBookmarkTest extends RepositorySearchTestCase
{
    private const FOLDER_CONTENT_TYPE_IDENTIFIER = 'folder';
    private const MEDIA_CONTENT_TYPE_ID = 43;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addMediaFolderToBookmark();
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
        $query = new LocationQuery();
        $query->filter = new Query\Criterion\LogicalAnd(
            $criteria
        );

        $searchHits = self::getSearchService()->findLocations($query);

        self::assertSame(
            $expectedCount,
            $searchHits->totalCount
        );
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
            6,
            [
                new Query\Criterion\Location\IsBookmarked(self::ADMIN_USER_ID),
            ],
        ];

        yield 'All bookmarked locations limited to folder content type' => [
            1,
            [
                new Query\Criterion\ContentTypeIdentifier(self::FOLDER_CONTENT_TYPE_IDENTIFIER),
                new Query\Criterion\Location\IsBookmarked(self::ADMIN_USER_ID),
            ],
        ];

        yield 'All bookmarked locations limited to user group content type' => [
            4,
            [
                new Query\Criterion\ContentTypeIdentifier('user_group'),
                new Query\Criterion\Location\IsBookmarked(self::ADMIN_USER_ID),
            ],
        ];

        yield 'All bookmarked locations limited to user content type' => [
            1,
            [
                new Query\Criterion\ContentTypeIdentifier('user'),
                new Query\Criterion\Location\IsBookmarked(self::ADMIN_USER_ID),
            ],
        ];
    }

    private function addMediaFolderToBookmark(): void
    {
        /** @var \Ibexa\Contracts\Core\Repository\BookmarkService $bookmarkService */
        $bookmarkService = self::getServiceByClassName(BookmarkService::class);

        $location = $this
            ->getLocationService()
            ->loadLocation(self::MEDIA_CONTENT_TYPE_ID);

        $bookmarkService->createBookmark($location);
    }
}
