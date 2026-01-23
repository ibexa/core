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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

abstract class CriterionHandlerTestCase extends TestCase
{
    abstract public function testAccept();

    abstract public function testHandle();

    /**
     * Check if critetion handler accepts specyfied criterion class.
     *
     * @param CriterionHandler $handler
     * @param string $criterionClass
     */
    protected function assertHandlerAcceptsCriterion(
        CriterionHandler $handler,
        $criterionClass
    ) {
        self::assertTrue($handler->accept($this->createMock($criterionClass)));
    }

    /**
     * Check if critetion handler rejects specyfied criterion class.
     *
     * @param CriterionHandler $handler
     * @param string $criterionClass
     */
    protected function assertHandlerRejectsCriterion(
        CriterionHandler $handler,
        $criterionClass
    ) {
        self::assertFalse($handler->accept($this->createMock($criterionClass)));
    }

    /**
     * @param QueryBuilder|MockObject $queryBuilder
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
            ->expects(self::exactly(2))
            ->method('convertCriteria')
            ->willReturnCallback(static function (
                $qb,
                $criterion
            ) use ($queryBuilder, $foo, $bar, $fooExpr, $barExpr) {
                self::assertSame($queryBuilder, $qb);
                if ($criterion === $foo) {
                    return $fooExpr;
                }
                if ($criterion === $bar) {
                    return $barExpr;
                }
                self::fail('Unexpected criterion');
            });

        return $converter;
    }
}
