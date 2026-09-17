<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\Bookmark;

use Doctrine\DBAL\Connection;
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

        self::assertSame(
            sprintf(
                'SELECT location.node_id, %1$s.id AS %2$s'
                . ' FROM %3$s location'
                . ' INNER JOIN %4$s content ON content.id = location.contentobject_id'
                . ' LEFT JOIN %5$s %1$s ON (location.node_id = %1$s.node_id) AND (%1$s.user_id = :dcValue1)'
                . ' ORDER BY %2$s DESC',
                self::BOOKMARK_ALIAS,
                self::SORT_ALIAS,
                LocationGateway::CONTENT_TREE_TABLE,
                self::CONTENT_ITEM_TABLE,
                DoctrineDatabase::TABLE_BOOKMARKS
            ),
            $queryBuilder->getSQL()
        );
        self::assertSame(['dcValue1' => self::CURRENT_USER_ID], $queryBuilder->getParameters());
    }

    /**
     * Content filtering: there is no "location" FROM table, so the Content item's main Location
     * has to be joined first and the bookmarks table joined against *that* alias.
     */
    public function testBuildQueryInContentFilteringContext(): void
    {
        $queryBuilder = $this->createContentFilteringQueryBuilder();

        $this->createBuilder()->buildQuery($queryBuilder, new Id(Query::SORT_DESC));

        self::assertSame(
            sprintf(
                'SELECT content.id, %1$s.id AS %2$s'
                . ' FROM %3$s content'
                . ' INNER JOIN %4$s %5$s ON (content.id = %5$s.contentobject_id) AND (%5$s.node_id = %5$s.main_node_id)'
                . ' LEFT JOIN %6$s %1$s ON (%5$s.node_id = %1$s.node_id) AND (%1$s.user_id = :dcValue1)'
                . ' ORDER BY %2$s DESC',
                self::BOOKMARK_ALIAS,
                self::SORT_ALIAS,
                self::CONTENT_ITEM_TABLE,
                LocationGateway::CONTENT_TREE_TABLE,
                self::CONTENT_LOCATION_ALIAS,
                DoctrineDatabase::TABLE_BOOKMARKS
            ),
            $queryBuilder->getSQL()
        );
        self::assertSame(['dcValue1' => self::CURRENT_USER_ID], $queryBuilder->getParameters());
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

    private function createInMemoryConnection(): Connection
    {
        return DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    }
}
