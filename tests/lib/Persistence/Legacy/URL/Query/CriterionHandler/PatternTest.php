<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Persistence\Legacy\URL\Query\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion\Pattern;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriterionHandler\Pattern as PatternHandler;

class PatternTest extends CriterionHandlerTestCase
{
    /**
     * {@inheritdoc}
     */
    public function testAccept()
    {
        $handler = new PatternHandler();

        $this->assertHandlerAcceptsCriterion($handler, Pattern::class);
        $this->assertHandlerRejectsCriterion($handler, Criterion::class);
    }

    /**
     * {@inheritdoc}
     */
    public function testHandle()
    {
        $criterion = new Pattern('google.com');
        $expected = 'url LIKE :pattern';
        $expressionBuilder = $this->createMock(ExpressionBuilder::class);
        $expressionBuilder
            ->expects(self::once())
            ->method('like')
            ->with('url', ':pattern')
            ->willReturn($expected);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder
            ->expects(self::any())
            ->method('expr')
            ->willReturn($expressionBuilder);
        $queryBuilder
            ->expects(self::once())
            ->method('createNamedParameter')
            ->with('%' . $criterion->pattern . '%', ParameterType::STRING, ':pattern')
            ->willReturn(':pattern');

        $converter = $this->createMock(CriteriaConverter::class);

        $handler = new PatternHandler();
        $actual = $handler->handle($converter, $queryBuilder, $criterion);

        self::assertEquals($expected, $actual);
    }
}
