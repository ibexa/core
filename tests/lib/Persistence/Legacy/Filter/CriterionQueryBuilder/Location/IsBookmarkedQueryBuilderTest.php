<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion as Criterion;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\IsBookmarkedQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\IsBookmarkedQueryBuilder
 */
final class IsBookmarkedQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    private const CURRENT_USER_ID = 14;

    private const BOOKMARK_EXISTS_SUBQUERY = 'SELECT 1 FROM ' . DoctrineDatabase::TABLE_BOOKMARKS . ' bookmark WHERE '
        . '(bookmark.' . DoctrineDatabase::COLUMN_USER_ID . ' = :dcValue%1$d)'
        . ' AND (bookmark.' . DoctrineDatabase::COLUMN_LOCATION_ID . ' = location.node_id)';

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion, string, array<string, int>}>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'IsBookmarked(true)' => [
            new Criterion\Location\IsBookmarked(true),
            sprintf('EXISTS (%s)', sprintf(self::BOOKMARK_EXISTS_SUBQUERY, 1)),
            ['dcValue1' => self::CURRENT_USER_ID],
        ];

        yield 'IsBookmarked(false)' => [
            new Criterion\Location\IsBookmarked(false),
            sprintf('NOT EXISTS (%s)', sprintf(self::BOOKMARK_EXISTS_SUBQUERY, 1)),
            ['dcValue1' => self::CURRENT_USER_ID],
        ];

        yield 'IsBookmarked(true) OR IsBookmarked(false)' => [
            new Criterion\LogicalOr(
                [
                    new Criterion\Location\IsBookmarked(true),
                    new Criterion\Location\IsBookmarked(false),
                ]
            ),
            sprintf(
                '(EXISTS (%s)) OR (NOT EXISTS (%s))',
                sprintf(self::BOOKMARK_EXISTS_SUBQUERY, 1),
                sprintf(self::BOOKMARK_EXISTS_SUBQUERY, 2)
            ),
            ['dcValue1' => self::CURRENT_USER_ID, 'dcValue2' => self::CURRENT_USER_ID],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        $userReference = $this->createMock(UserReference::class);
        $userReference->method('getUserId')->willReturn(self::CURRENT_USER_ID);

        $permissionResolver = $this->createMock(PermissionResolver::class);
        $permissionResolver->method('getCurrentUserReference')->willReturn($userReference);

        return [new IsBookmarkedQueryBuilder($permissionResolver)];
    }
}
