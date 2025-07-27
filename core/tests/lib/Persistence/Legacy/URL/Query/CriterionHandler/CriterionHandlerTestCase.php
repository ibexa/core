<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;
use PHPUnit\Framework\TestCase;

abstract class CriterionHandlerTestCase extends TestCase
{
    abstract public function testAccept();

    abstract public function testHandle();

    /**
     * Check if critetion handler accepts specyfied criterion class.
     *
     * @param \Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler $handler
     * @param string $criterionClass
     */
    protected function assertHandlerAcceptsCriterion(CriterionHandler $handler, $criterionClass)
    {
        self::assertTrue($handler->accept($this->createMock($criterionClass)));
    }

    /**
     * Check if critetion handler rejects specyfied criterion class.
     *
     * @param \Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler $handler
     * @param string $criterionClass
     */
    protected function assertHandlerRejectsCriterion(CriterionHandler $handler, $criterionClass)
    {
        self::assertFalse($handler->accept($this->createMock($criterionClass)));
    }

    /**
     * @param \Doctrine\DBAL\Query\QueryBuilder|\PHPUnit\Framework\MockObject\MockObject $queryBuilder
     */
    protected function mockConverterForLogicalOperator(
        string $expressionType,
        QueryBuilder $queryBuilder,
        string $expressionBuilderMethod,
        string $fooExpr,
        string $barExpr,
        Criterion $foo,
        Criterion $bar
    ): CriteriaConverter {
        $compositeExpression = new CompositeExpression(
            $expressionType,
            [
                $fooExpr,
                $barExpr,
            ]
        );
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder
            ->expects(self::any())
            ->method($expressionBuilderMethod)
            ->with($fooExpr, $barExpr)
            ->willReturn($compositeExpression);
        $queryBuilder
            ->expects(self::any())
            ->method('expr')
            ->willReturn($expressionBuilder);

        $converter = $this->createMock(CriteriaConverter::class);
        $converter
            ->expects(self::at(0))
            ->method('convertCriteria')
            ->with($queryBuilder, $foo)
            ->willReturn($fooExpr);
        $converter
            ->expects(self::at(1))
            ->method('convertCriteria')
            ->with($queryBuilder, $bar)
            ->willReturn($barExpr);

        return $converter;
    }
}
