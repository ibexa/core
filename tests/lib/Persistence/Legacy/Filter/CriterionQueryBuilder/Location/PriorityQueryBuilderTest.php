<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\PriorityQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\PriorityQueryBuilder
 */
final class PriorityQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, mixed>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Priority BETWEEN 1 AND 5' => [
            new Criterion\Location\Priority(Criterion\Operator::BETWEEN, [1, 5]),
            self::buildContentFilteringLocationConstraint('filter_location.priority BETWEEN :dcValue1 AND :dcValue2'),
            ['dcValue1' => 1, 'dcValue2' => 5],
        ];

        yield 'Priority>0' => [
            new Criterion\Location\Priority(Criterion\Operator::GT, 0),
            self::buildContentFilteringLocationConstraint('filter_location.priority > :dcValue1'),
            ['dcValue1' => 0],
        ];

        yield 'Priority>=1' => [
            new Criterion\Location\Priority(Criterion\Operator::GTE, 1),
            self::buildContentFilteringLocationConstraint('filter_location.priority >= :dcValue1'),
            ['dcValue1' => 1],
        ];

        yield 'Priority<10' => [
            new Criterion\Location\Priority(Criterion\Operator::LT, 10),
            self::buildContentFilteringLocationConstraint('filter_location.priority < :dcValue1'),
            ['dcValue1' => 10],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new PriorityQueryBuilder()];
    }
}
