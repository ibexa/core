<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark;

use Doctrine\DBAL\DriverManager;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Location\Bookmark\Id;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark\IdSortClauseQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark\IdSortClauseQueryBuilder
 */
final class IdSortClauseQueryBuilderTest extends TestCase
{
    private const CURRENT_USER_ID = 14;

    public function testBuildQueryJoinsBookmarksForCurrentUserAndOrdersByBookmarkId(): void
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $queryBuilder = new FilteringQueryBuilder($connection);
        $queryBuilder->select('location.node_id')->from('ezcontentobject_tree', 'location');

        $userReference = $this->createMock(UserReference::class);
        $userReference->method('getUserId')->willReturn(self::CURRENT_USER_ID);

        $permissionResolver = $this->createMock(PermissionResolver::class);
        $permissionResolver->method('getCurrentUserReference')->willReturn($userReference);

        $builder = new IdSortClauseQueryBuilder($permissionResolver);
        $sortClause = new Id(Query::SORT_DESC);

        self::assertTrue($builder->accepts($sortClause));

        $builder->buildQuery($queryBuilder, $sortClause);

        self::assertContains(
            'ibexa_sort_bookmark.id',
            $queryBuilder->getQueryPart('select')
        );

        $joins = $queryBuilder->getQueryPart('join');
        self::assertArrayHasKey('location', $joins);
        self::assertSame(DoctrineDatabase::TABLE_BOOKMARKS, $joins['location'][0]['joinTable']);
        self::assertSame('ibexa_sort_bookmark', $joins['location'][0]['joinAlias']);
        self::assertSame(
            '(location.node_id = ibexa_sort_bookmark.node_id) AND (ibexa_sort_bookmark.user_id = :dcValue1)',
            (string)$joins['location'][0]['joinCondition']
        );
        self::assertSame(['dcValue1' => self::CURRENT_USER_ID], $queryBuilder->getParameters());

        self::assertSame(['ibexa_sort_bookmark.id DESC'], $queryBuilder->getQueryPart('orderBy'));
    }
}
