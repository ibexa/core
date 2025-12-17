<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location;

use Doctrine\DBAL\DriverManager;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\SortClauseQueryBuilder\Location\BaseLocationSortClauseQueryBuilder;
use PHPUnit\Framework\TestCase;

final class BaseLocationSortClauseQueryBuilderTest extends TestCase
{
    public function testLegacyImplementationIsSupported(): void
    {
        $connection = DriverManager::getConnection(['url' => 'sqlite:///:memory:']);
        $queryBuilder = new FilteringQueryBuilder($connection);

        $sortClause = new class() implements FilteringSortClause {
            public string $direction = Query::SORT_ASC;
        };

        $builder = new class() extends BaseLocationSortClauseQueryBuilder {
            protected function getSortingExpression(): string
            {
                return 'location.depth';
            }

            public function accepts(FilteringSortClause $sortClause): bool
            {
                return true;
            }
        };

        $builder->buildQuery($queryBuilder, $sortClause);

        self::assertSame(
            ['ibexa_sort_location.depth AS ibexa_filter_sort_ibexa_sort_location_depth'],
            $queryBuilder->getQueryPart('select')
        );

        $joins = $queryBuilder->getQueryPart('join');
        self::assertArrayHasKey('content', $joins);
        self::assertCount(1, $joins['content']);
        self::assertSame(LocationGateway::CONTENT_TREE_TABLE, $joins['content'][0]['joinTable']);
        self::assertSame('ibexa_sort_location', $joins['content'][0]['joinAlias']);
        self::assertSame(
            '(content.id = ibexa_sort_location.contentobject_id) AND (ibexa_sort_location.node_id = ibexa_sort_location.main_node_id)',
            (string)$joins['content'][0]['joinCondition']
        );

        $orderBy = $queryBuilder->getQueryPart('orderBy');
        self::assertSame(['ibexa_filter_sort_ibexa_sort_location_depth ASC'], $orderBy);
    }
}
