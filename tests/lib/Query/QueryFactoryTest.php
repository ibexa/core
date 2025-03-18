<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Query;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Query\QueryFactory;
use Ibexa\Core\QueryType\QueryType;
use Ibexa\Core\QueryType\QueryTypeRegistry;
use Ibexa\Tests\Core\Search\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class QueryFactoryTest extends TestCase
{
    private const EXAMPLE_QUERY_TYPE = 'Example';
    private const EXAMPLE_QUERY_PARAMS = [
        'foo' => 'foo',
        'bar' => 'bar',
        'baz' => 'baz',
    ];

    /** @var \Ibexa\Core\QueryType\QueryTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private MockObject $queryTypeRegistry;

    /** @var \Ibexa\Core\Query\QueryFactory */
    private QueryFactory $queryFactory;

    protected function setUp(): void
    {
        $this->queryTypeRegistry = $this->createMock(QueryTypeRegistry::class);
        $this->queryFactory = new QueryFactory($this->queryTypeRegistry);
    }

    public function testCreate(): void
    {
        $expectedQuery = new Query();

        $queryType = $this->createMock(QueryType::class);
        $queryType
            ->expects(self::once())
            ->method('getQuery')
            ->with(self::EXAMPLE_QUERY_PARAMS)
            ->willReturn($expectedQuery);

        $this->queryTypeRegistry
            ->expects(self::once())
            ->method('getQueryType')
            ->with(self::EXAMPLE_QUERY_TYPE)
            ->willReturn($queryType);

        $actualQuery = $this->queryFactory->create(
            self::EXAMPLE_QUERY_TYPE,
            self::EXAMPLE_QUERY_PARAMS
        );

        self::assertEquals($expectedQuery, $actualQuery);
    }
}
