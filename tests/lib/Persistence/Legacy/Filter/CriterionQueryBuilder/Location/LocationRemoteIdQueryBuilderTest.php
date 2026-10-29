<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\RemoteIdQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\Filter\BaseCriterionVisitorQueryBuilderTestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location\RemoteIdQueryBuilder
 */
final class LocationRemoteIdQueryBuilderTest extends BaseCriterionVisitorQueryBuilderTestCase
{
    /**
     * @return iterable<string, array{FilteringCriterion, string, array<string, mixed>}>
     */
    public function getFilteringCriteriaQueryData(): iterable
    {
        yield 'Location remote ID=foo' => [
            new Criterion\LocationRemoteId('foo'),
            self::buildContentFilteringLocationConstraint('filter_location.remote_id IN (:dcValue1)'),
            ['dcValue1' => ['foo']],
        ];

        yield 'Location remote ID IN (foo, bar)' => [
            new Criterion\LocationRemoteId(['foo', 'bar']),
            self::buildContentFilteringLocationConstraint('filter_location.remote_id IN (:dcValue1)'),
            ['dcValue1' => ['foo', 'bar']],
        ];
    }

    protected function getCriterionQueryBuilders(): iterable
    {
        return [new RemoteIdQueryBuilder()];
    }
}
