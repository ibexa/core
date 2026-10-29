<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\DepthQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\DepthQueryBuilder
 */
final class DepthQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, mixed>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Depth=2' => [
            new Criterion\Location\Depth(Criterion\Operator::EQ, 2),
            self::buildContentFilteringLocationConstraint('filter_location.depth = :dcValue1'),
            ['dcValue1' => 2],
        ];

        yield 'Depth IN (2, 3)' => [
            new Criterion\Location\Depth(Criterion\Operator::IN, [2, 3]),
            self::buildContentFilteringLocationConstraint('filter_location.depth IN (:dcValue1)'),
            ['dcValue1' => [2, 3]],
        ];

        yield 'Depth BETWEEN 2 AND 4' => [
            new Criterion\Location\Depth(Criterion\Operator::BETWEEN, [2, 4]),
            self::buildContentFilteringLocationConstraint('filter_location.depth BETWEEN :dcValue1 AND :dcValue2'),
            ['dcValue1' => 2, 'dcValue2' => 4],
        ];

        yield 'Depth>2' => [
            new Criterion\Location\Depth(Criterion\Operator::GT, 2),
            self::buildContentFilteringLocationConstraint('filter_location.depth > :dcValue1'),
            ['dcValue1' => 2],
        ];

        yield 'Depth<=3' => [
            new Criterion\Location\Depth(Criterion\Operator::LTE, 3),
            self::buildContentFilteringLocationConstraint('filter_location.depth <= :dcValue1'),
            ['dcValue1' => 3],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new DepthQueryBuilder()];
    }
}
