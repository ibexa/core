<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\SubtreeQueryBuilder;
use Ibexa\Core\Repository\Values\Content\Query\Criterion\PermissionSubtree;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\SubtreeQueryBuilder
 */
final class SubtreeQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, string>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Subtree=/1/2/' => [
            new Criterion\Subtree('/1/2/'),
            self::buildContentFilteringLocationConstraint('filter_location.path_string LIKE :dcValue1'),
            ['dcValue1' => '/1/2/%'],
        ];

        yield 'Subtree IN (/1/2/, /1/5/)' => [
            new Criterion\Subtree(['/1/2/', '/1/5/']),
            self::buildContentFilteringLocationConstraint(
                '(filter_location.path_string LIKE :dcValue1) OR (filter_location.path_string LIKE :dcValue2)'
            ),
            ['dcValue1' => '/1/2/%', 'dcValue2' => '/1/5/%'],
        ];

        yield 'PermissionSubtree=/1/2/' => [
            new PermissionSubtree('/1/2/'),
            self::buildContentFilteringLocationConstraint('filter_location.path_string LIKE :dcValue1'),
            ['dcValue1' => '/1/2/%'],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new SubtreeQueryBuilder()];
    }
}
