<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionVisitor;
use PHPUnit\Framework\TestCase;

abstract class BaseCriterionVisitorQueryBuilderTestCase extends TestCase
{
    /** @var CriterionVisitor */
    private $criterionVisitor;

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder[]
     */
    abstract protected function getCriterionQueryBuilders(): iterable;

    /**
     * Data provider for {@see testVisitCriteriaProducesQuery}.
     */
    abstract public function getFilteringCriteriaQueryData(): iterable;

    protected function setUp(): void
    {
        $this->criterionVisitor = new CriterionVisitor([]);
        $this->criterionVisitor->setCriterionQueryBuilders(
            array_merge(
                $this->getBaseCriterionQueryBuilders($this->criterionVisitor),
                $this->getCriterionQueryBuilders()
            )
        );
    }

    /**
     * @dataProvider getFilteringCriteriaQueryData
     *
     * @covers \Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder::buildQueryConstraint
     * @covers \Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder::accepts
     * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionVisitor::visitCriteria
     *
     * @throws NotImplementedException
     */
    public function testVisitCriteriaProducesQuery(
        FilteringCriterion $criterion,
        string $expectedQuery,
        array $expectedParameterValues
    ): void {
        $queryBuilder = $this->getQueryBuilder();
        $actualQuery = $this->criterionVisitor->visitCriteria($queryBuilder, $criterion);
        $criterionFQCN = get_class($criterion);
        self::assertSame(
            $expectedQuery,
            $actualQuery,
            sprintf(
                'Query Builder for %s Criterion does not produce expected query',
                $criterionFQCN
            )
        );
        self::assertSame(
            $expectedParameterValues,
            $queryBuilder->getParameters(),
            sprintf(
                'Query Builder for %s Criterion does not bind expected query parameter values',
                $criterionFQCN
            )
        );
    }

    /**
     * Wraps a Location constraint the way it is used in Content filtering, i.e. matching any Location.
     */
    protected static function buildContentFilteringLocationConstraint(string $locationConstraint): string
    {
        return sprintf(
            'content.id IN (SELECT filter_location.contentobject_id FROM %s filter_location WHERE %s)',
            LocationGateway::CONTENT_TREE_TABLE,
            $locationConstraint
        );
    }

    protected function getQueryBuilder(): FilteringQueryBuilder
    {
        $connectionMock = $this->createMock(Connection::class);
        $connectionMock
            ->method('getExpressionBuilder')
            ->willReturn(
                new ExpressionBuilder($connectionMock)
            );

        return new FilteringQueryBuilder($connectionMock);
    }

    /**
     * Create Query Builders needed for every test case.
     *
     * @see getCriterionQueryBuilders
     */
    private function getBaseCriterionQueryBuilders(CriterionVisitor $criterionVisitor): iterable
    {
        return [
            new CriterionQueryBuilder\LogicalAndQueryBuilder($criterionVisitor),
            new CriterionQueryBuilder\LogicalOrQueryBuilder($criterionVisitor),
            new CriterionQueryBuilder\LogicalNotQueryBuilder($criterionVisitor),
        ];
    }
}
