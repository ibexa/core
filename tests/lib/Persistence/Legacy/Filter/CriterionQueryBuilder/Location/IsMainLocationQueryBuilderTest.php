<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\IsMainLocationQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\IsMainLocationQueryBuilder
 */
final class IsMainLocationQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, mixed>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'IsMainLocation=MAIN' => [
            new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::MAIN),
            self::buildContentFilteringLocationConstraint(
                'filter_location.node_id = filter_location.main_node_id'
            ),
            [],
        ];

        yield 'IsMainLocation=NOT_MAIN' => [
            new Criterion\Location\IsMainLocation(Criterion\Location\IsMainLocation::NOT_MAIN),
            self::buildContentFilteringLocationConstraint(
                'filter_location.node_id <> filter_location.main_node_id'
            ),
            [],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new IsMainLocationQueryBuilder()];
    }
}
