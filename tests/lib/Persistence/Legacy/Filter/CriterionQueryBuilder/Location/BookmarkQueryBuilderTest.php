<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion as Criterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\BookmarkQueryBuilder;
use Ibexa\Core\Repository\Permission\PermissionResolver;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

final class BookmarkQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<array-key, array{Criterion, string, array<string, int>}>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Bookmarks locations for user_id=14' => [
            new Criterion\IsBookmarked(true, 14),
            'bookmark.user_id = :dcValue1',
            ['dcValue1' => 14],
        ];

        yield 'Bookmarks locations for user_id=14 OR user_id=7' => [
            new Criterion\LogicalOr(
                [
                    new Criterion\IsBookmarked(true, 14),
                    new Criterion\IsBookmarked(true, 7),
               ]
            ),
            '(bookmark.user_id = :dcValue1) OR (bookmark.user_id = :dcValue2)',
            ['dcValue1' => 14, 'dcValue2' => 7],
        ];

        yield 'Bookmarks locations for user_id=7' => [
            new Criterion\IsBookmarked(true, 7),
            'bookmark.user_id = :dcValue1',
            ['dcValue1' => 7],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new BookmarkQueryBuilder($this->createMock(PermissionResolver::class))];
    }
}
