<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Persistence\Filter\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\SQL\Builder\DefaultSelectSQLBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use PHPUnit\Framework\TestCase;

class FilteringQueryBuilderTest extends TestCase
{
    /** @var \Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder */
    private $queryBuilder;

    protected function setUp(): void
    {
        $platform = $this->createMock(AbstractPlatform::class);
        $platform->method('createSelectSQLBuilder')->willReturn(new DefaultSelectSQLBuilder($platform, null, null));

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);

        $connectionMock->method('getExpressionBuilder')->willReturn(
            new ExpressionBuilder($connectionMock)
        );
        $this->queryBuilder = new FilteringQueryBuilder($connectionMock);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder::joinOnce
     */
    public function testJoinOnce(): void
    {
        $this->queryBuilder
            ->select('f.id')->from('foo', 'f')
            ->joinOnce('f', 'bar', 'b', 'f.id = b.foo_id');

        $expr = $this->queryBuilder->expr();
        // should not be joined again
        $this->queryBuilder->joinOnce('f', 'bar', 'b', $expr->eq('f.id', 'b.foo_id'));
        // can be joined
        $this->queryBuilder->joinOnce('f', 'bar', 'b2', $expr->eq('f.id', 'b2.foo_id'));

        self::assertSame(
            'SELECT f.id FROM foo f ' .
            'INNER JOIN bar b ON f.id = b.foo_id ' .
            'INNER JOIN bar b2 ON f.id = b2.foo_id',
            $this->queryBuilder->getSQL()
        );
    }

    public function testJoinOnceThrowsDatabaseError(): void
    {
        $this
            ->queryBuilder
            ->select('f.id')->from('foo', 'f')
            ->joinOnce('f', 'bar', 'b', 'f.id = b.foo_id');

        $this->expectException(DatabaseException::class);
        $this->expectExceptionMessageMatches('/^FilteringQueryBuilder: .*f.id = b.foo_id/');

        // different condition should cause Runtime DatabaseException as automatic error recovery is not possible
        $this->queryBuilder->joinOnce('f', 'bar', 'b', 'f.bar_id = b.id');
    }
}
