<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content\ObjectStateIdQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content\ObjectStateIdQueryBuilder
 */
final class ObjectStateIdQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    private const OBJECT_STATE_SUBQUERY = 'content.id IN (SELECT osl.contentobject_id FROM ezcobj_state_link osl '
        . 'WHERE osl.contentobject_state_id IN (:dcValue%d))';

    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, int[]>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'ObjectStateId IN (1, 2)' => [
            new Criterion\ObjectStateId([1, 2]),
            sprintf(self::OBJECT_STATE_SUBQUERY, 1),
            ['dcValue1' => [1, 2]],
        ];

        yield 'ObjectStateId=1 AND ObjectStateId=5 (states of different groups)' => [
            new Criterion\LogicalAnd([
                new Criterion\ObjectStateId(1),
                new Criterion\ObjectStateId(5),
            ]),
            sprintf(
                '(%s) AND (%s)',
                sprintf(self::OBJECT_STATE_SUBQUERY, 1),
                sprintf(self::OBJECT_STATE_SUBQUERY, 2)
            ),
            ['dcValue1' => [1], 'dcValue2' => [5]],
        ];

        yield 'NOT ObjectStateId=1 (excludes content in state 1, regardless of its states in other groups)' => [
            new Criterion\LogicalNot(new Criterion\ObjectStateId(1)),
            sprintf('NOT (%s)', sprintf(self::OBJECT_STATE_SUBQUERY, 1)),
            ['dcValue1' => [1]],
        ];

        yield 'two policies with ObjectState limitations on two groups' => [
            new Criterion\LogicalOr([
                new Criterion\LogicalAnd([
                    new Criterion\ObjectStateId([1]),
                    new Criterion\ObjectStateId([5]),
                ]),
                new Criterion\LogicalAnd([
                    new Criterion\ObjectStateId([1]),
                    new Criterion\ObjectStateId([6]),
                ]),
            ]),
            sprintf(
                '((%s) AND (%s)) OR ((%s) AND (%s))',
                sprintf(self::OBJECT_STATE_SUBQUERY, 1),
                sprintf(self::OBJECT_STATE_SUBQUERY, 2),
                sprintf(self::OBJECT_STATE_SUBQUERY, 3),
                sprintf(self::OBJECT_STATE_SUBQUERY, 4)
            ),
            ['dcValue1' => [1], 'dcValue2' => [5], 'dcValue3' => [1], 'dcValue4' => [6]],
        ];
    }

    public function testBuildQueryConstraintDoesNotJoinObjectStateLinkTable(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $objectStateIdQueryBuilder = new ObjectStateIdQueryBuilder();

        $objectStateIdQueryBuilder->buildQueryConstraint($queryBuilder, new Criterion\ObjectStateId(1));
        $objectStateIdQueryBuilder->buildQueryConstraint($queryBuilder, new Criterion\ObjectStateId(5));

        self::assertSame([], $queryBuilder->getQueryPart('join'));
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new ObjectStateIdQueryBuilder()];
    }
}
