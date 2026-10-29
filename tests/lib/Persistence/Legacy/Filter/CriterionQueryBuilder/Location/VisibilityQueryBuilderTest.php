<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\VisibilityQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\VisibilityQueryBuilder
 */
final class VisibilityQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, mixed>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Visibility=VISIBLE' => [
            new Criterion\Visibility(Criterion\Visibility::VISIBLE),
            self::buildContentFilteringLocationConstraint(
                '(filter_location.is_hidden = :dcValue1) AND (filter_location.is_invisible = :dcValue2)'
            ),
            ['dcValue1' => Criterion\Visibility::VISIBLE, 'dcValue2' => Criterion\Visibility::VISIBLE],
        ];

        yield 'Visibility=HIDDEN' => [
            new Criterion\Visibility(Criterion\Visibility::HIDDEN),
            self::buildContentFilteringLocationConstraint(
                '(filter_location.is_hidden = :dcValue1) OR (filter_location.is_invisible = :dcValue2)'
            ),
            ['dcValue1' => Criterion\Visibility::HIDDEN, 'dcValue2' => Criterion\Visibility::HIDDEN],
        ];

        yield 'NOT Visibility=HIDDEN' => [
            new Criterion\LogicalNot(new Criterion\Visibility(Criterion\Visibility::HIDDEN)),
            'NOT (' . self::buildContentFilteringLocationConstraint(
                '(filter_location.is_hidden = :dcValue1) OR (filter_location.is_invisible = :dcValue2)'
            ) . ')',
            ['dcValue1' => Criterion\Visibility::HIDDEN, 'dcValue2' => Criterion\Visibility::HIDDEN],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new VisibilityQueryBuilder()];
    }
}
