<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion\LogicalAnd;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler\LogicalAnd as LogicalAndHandler;

class LogicalAndTest extends CriterionHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testAccept()
    {
        $handler = new LogicalAndHandler();

        self::assertTrue($handler->accept($this->createMock(LogicalAnd::class)));
        self::assertFalse($handler->accept($this->createMock(Criterion::class)));
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function testHandle(): void
    {
        $foo = $this->createMock(Criterion::class);
        $bar = $this->createMock(Criterion::class);

        $fooExpr = 'FOO';
        $barExpr = 'BAR';

        $expected = '(FOO) AND (BAR)';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $converter = $this->mockConverterForLogicalOperator(
            CompositeExpression::TYPE_AND,
            $queryBuilder,
            'and',
            $fooExpr,
            $barExpr,
            $foo,
            $bar
        );

        $handler = new LogicalAndHandler();
        $actual = (string)$handler->handle(
            $converter,
            $queryBuilder,
            new LogicalAnd([$foo, $bar])
        );

        self::assertEquals($expected, $actual);
    }
}
