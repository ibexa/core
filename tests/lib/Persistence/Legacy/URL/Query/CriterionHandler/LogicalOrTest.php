<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion\LogicalOr;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler\LogicalOr as LogicalOrHandler;

class LogicalOrTest extends CriterionHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testAccept()
    {
        $handler = new LogicalOrHandler();

        $this->assertHandlerAcceptsCriterion($handler, LogicalOr::class);
        $this->assertHandlerRejectsCriterion($handler, Criterion::class);
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotImplementedException
     */
    public function testHandle(): void
    {
        $foo = $this->createMock(Criterion::class);
        $bar = $this->createMock(Criterion::class);

        $fooExpr = 'FOO';
        $barExpr = 'BAR';

        $expected = '(FOO) OR (BAR)';

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $converter = $this->mockConverterForLogicalOperator(
            CompositeExpression::TYPE_OR,
            $queryBuilder,
            'or',
            $fooExpr,
            $barExpr,
            $foo,
            $bar
        );

        $handler = new LogicalOrHandler();
        $actual = (string)$handler->handle(
            $converter,
            $queryBuilder,
            new LogicalOr([$foo, $bar])
        );

        self::assertEquals($expected, $actual);
    }
}
