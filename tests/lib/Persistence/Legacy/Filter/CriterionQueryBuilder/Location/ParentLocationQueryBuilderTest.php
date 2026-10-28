<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\ParentLocationIdQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\ParentLocationIdQueryBuilder
 */
final class ParentLocationQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Parent Location ID=1' => [
            new Criterion\ParentLocationId(1),
            self::buildContentFilteringLocationConstraint('filter_location.parent_node_id IN (:dcValue1)'),
            ['dcValue1' => [1]],
        ];

        yield 'Parent Location ID=1 OR Parent Location ID=2' => [
            new Criterion\LogicalOr(
                [
                    new Criterion\ParentLocationId(1),
                    new Criterion\ParentLocationId(2),
                ]
            ),
            '(' . self::buildContentFilteringLocationConstraint('filter_location.parent_node_id IN (:dcValue1)') . ') OR (' . self::buildContentFilteringLocationConstraint('filter_location.parent_node_id IN (:dcValue2)') . ')',
            ['dcValue1' => [1], 'dcValue2' => [2]],
        ];
    }

    public function testBuildsConstraintOnFilteredLocationInLocationFiltering(): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->from(LocationGateway::CONTENT_TREE_TABLE, 'location');

        self::assertSame(
            'location.parent_node_id IN (:dcValue1)',
            (new ParentLocationIdQueryBuilder())->buildQueryConstraint(
                $queryBuilder,
                new Criterion\ParentLocationId(1)
            )
        );
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new ParentLocationIdQueryBuilder()];
    }
}
