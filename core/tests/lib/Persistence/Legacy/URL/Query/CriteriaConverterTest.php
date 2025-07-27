<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\User\Gateway\DoctrineDatabase
 */
class CriteriaConverterTest extends TestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function testConvertCriteriaSuccess(): void
    {
        $fooCriterionHandler = $this->createMock(CriterionHandler::class);
        $barCriterionHandler = $this->createMock(CriterionHandler::class);

        $criteriaConverter = new CriteriaConverter([
            $fooCriterionHandler,
            $barCriterionHandler,
        ]);

        $barCriterion = $this->createMock(Criterion::class);

        $selectQuery = $this->createMock(QueryBuilder::class);

        $fooCriterionHandler
            ->expects(self::once())
            ->method('accept')
            ->with($barCriterion)
            ->willReturn(false);

        $fooCriterionHandler
            ->expects(self::never())
            ->method('handle');

        $barCriterionHandler
            ->expects(self::once())
            ->method('accept')
            ->with($barCriterion)
            ->willReturn(true);

        $sqlExpression = 'SQL EXPRESSION';
        $barCriterionHandler
            ->expects(self::once())
            ->method('handle')
            ->with($criteriaConverter, $selectQuery, $barCriterion)
            ->willReturn($sqlExpression);

        self::assertEquals(
            $sqlExpression,
            $criteriaConverter->convertCriteria(
                $selectQuery,
                $barCriterion
            )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function testConvertCriteriaFailure(): void
    {
        $this->expectException(NotImplementedException::class);

        $criteriaConverter = new CriteriaConverter();
        $criteriaConverter->convertCriteria(
            $this->createMock(QueryBuilder::class),
            $this->createMock(Criterion::class)
        );
    }
}
