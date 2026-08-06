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
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark\IdSortClauseQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark\IdSortClauseQueryBuilder
 */
final class IdSortClauseQueryBuilderTest extends TestCase
{
    private const CURRENT_USER_ID = 14;
    private const BOOKMARK_ALIAS = 'ibexa_sort_bookmark';
    private const CONTENT_LOCATION_ALIAS = 'ibexa_sort_location';
    private const SORT_ALIAS = 'ibexa_filter_sort_bookmark_id';
    private const CONTENT_ITEM_TABLE = 'ezcontentobject';

    /**
     * Location filtering: "location" is the FROM table, so the bookmarks table is joined
     * directly against it.
     */
    public function testBuildQueryInLocationFilteringContext(): void
    {
        $queryBuilder = $this->createLocationFilteringQueryBuilder();

        $builder = $this->createBuilder();
        $sortClause = new Id(Query::SORT_DESC);

        self::assertTrue($builder->accepts($sortClause));

        $builder->buildQuery($queryBuilder, $sortClause);

        self::assertContains(
            sprintf('%s.id AS %s', self::BOOKMARK_ALIAS, self::SORT_ALIAS),
            $queryBuilder->getQueryPart('select')
        );

        $joins = $queryBuilder->getQueryPart('join');
        self::assertArrayHasKey('location', $joins);

        $bookmarkJoin = $this->findJoinByAlias($joins['location'], self::BOOKMARK_ALIAS);
        self::assertNotNull($bookmarkJoin, 'Bookmarks table was not joined against "location"');
        self::assertSame(DoctrineDatabase::TABLE_BOOKMARKS, $bookmarkJoin['joinTable']);
        self::assertSame(
            sprintf(
                '(location.node_id = %1$s.node_id) AND (%1$s.user_id = :dcValue1)',
                self::BOOKMARK_ALIAS
            ),
            (string)$bookmarkJoin['joinCondition']
        );
        self::assertSame(['dcValue1' => self::CURRENT_USER_ID], $queryBuilder->getParameters());

        self::assertSame(
            [self::SORT_ALIAS . ' DESC'],
            $queryBuilder->getQueryPart('orderBy')
        );

        // the whole join graph has to resolve
        self::assertStringContainsString(self::BOOKMARK_ALIAS, $queryBuilder->getSQL());
    }

    /**
     * Content filtering: there is no "location" FROM table, so the Content item's main Location
     * has to be joined first and the bookmarks table joined against *that* alias.
     */
    public function testBuildQueryInContentFilteringContext(): void
    {
        $queryBuilder = $this->createContentFilteringQueryBuilder();

        $this->createBuilder()->buildQuery($queryBuilder, new Id(Query::SORT_DESC));

        $joins = $queryBuilder->getQueryPart('join');

        // main Location joined off the "content" FROM table...
        self::assertArrayHasKey('content', $joins);
        self::assertSame(LocationGateway::CONTENT_TREE_TABLE, $joins['content'][0]['joinTable']);
        self::assertSame(self::CONTENT_LOCATION_ALIAS, $joins['content'][0]['joinAlias']);

        // ...and bookmarks joined off that alias, not off a hardcoded "location"
        self::assertArrayHasKey(self::CONTENT_LOCATION_ALIAS, $joins);
        self::assertSame(
            DoctrineDatabase::TABLE_BOOKMARKS,
            $joins[self::CONTENT_LOCATION_ALIAS][0]['joinTable']
        );
        self::assertSame(
            self::BOOKMARK_ALIAS,
            $joins[self::CONTENT_LOCATION_ALIAS][0]['joinAlias']
        );
        self::assertSame(
            sprintf(
                '(%1$s.node_id = %2$s.node_id) AND (%2$s.user_id = :dcValue1)',
                self::CONTENT_LOCATION_ALIAS,
                self::BOOKMARK_ALIAS
            ),
            (string)$joins[self::CONTENT_LOCATION_ALIAS][0]['joinCondition']
        );

        self::assertArrayNotHasKey('location', $joins);

        self::assertSame(
            [self::SORT_ALIAS . ' DESC'],
            $queryBuilder->getQueryPart('orderBy')
        );

        self::assertStringContainsString(self::BOOKMARK_ALIAS, $queryBuilder->getSQL());
    }

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder}>
     */
    public function standaloneContextProvider(): iterable
    {
        yield 'Location filtering' => [$this->createLocationFilteringQueryBuilder()];
        yield 'Content filtering' => [$this->createContentFilteringQueryBuilder()];
    }

    /**
     * Test that sort clause works without an IsBookmarked criterion having joined anything first.
     *
     * @dataProvider standaloneContextProvider
     */
    public function testBuildQueryStandaloneProducesResolvableSql(
        FilteringQueryBuilder $queryBuilder
    ): void {
        $this->createBuilder()->buildQuery($queryBuilder, new Id(Query::SORT_ASC));

        $sql = $queryBuilder->getSQL();

        self::assertStringContainsString(DoctrineDatabase::TABLE_BOOKMARKS, $sql);
        self::assertStringContainsString('ORDER BY ' . self::SORT_ALIAS . ' ASC', $sql);
    }

    /**
     * @param array<array<string, mixed>> $joins
     *
     * @return array<string, mixed>|null
     */
    private function findJoinByAlias(array $joins, string $joinAlias): ?array
    {
        foreach ($joins as $join) {
            if ($join['joinAlias'] === $joinAlias) {
                return $join;
            }
        }

        return null;
    }

    private function createBuilder(): IdSortClauseQueryBuilder
    {
        $userReference = $this->createMock(UserReference::class);
        $userReference->method('getUserId')->willReturn(self::CURRENT_USER_ID);

        $permissionResolver = $this->createMock(PermissionResolver::class);
        $permissionResolver->method('getCurrentUserReference')->willReturn($userReference);

        return new IdSortClauseQueryBuilder($permissionResolver);
    }

    /**
     * Mirrors the baseline query built by
     * {@see \Ibexa\Core\Persistence\Legacy\Filter\Gateway\Location\Doctrine\DoctrineGateway}:
     * "location" is the FROM table and "content" is joined off it.
     */
    private function createLocationFilteringQueryBuilder(): FilteringQueryBuilder
    {
        $queryBuilder = new FilteringQueryBuilder($this->createInMemoryConnection());
        $queryBuilder
            ->select('location.node_id')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 'location')
            ->join(
                'location',
                self::CONTENT_ITEM_TABLE,
                'content',
                'content.id = location.contentobject_id'
            );

        return $queryBuilder;
    }

    /**
     * Mirrors the baseline query built by
     * {@see \Ibexa\Core\Persistence\Legacy\Filter\Gateway\Content\Doctrine\DoctrineGateway}:
     * "content" is the FROM table and there is no "location" alias at all.
     */
    private function createContentFilteringQueryBuilder(): FilteringQueryBuilder
    {
        $queryBuilder = new FilteringQueryBuilder($this->createInMemoryConnection());
        $queryBuilder
            ->select('content.id')
            ->from(self::CONTENT_ITEM_TABLE, 'content');

        return $queryBuilder;
    }

    private function createInMemoryConnection(): \Doctrine\DBAL\Connection
    {
        return DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
    }
}
