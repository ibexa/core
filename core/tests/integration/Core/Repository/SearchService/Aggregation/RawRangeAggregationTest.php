<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\RawRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;

final class RawRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        yield [
            new RawRangeAggregation(
                'raw_range',
                'content_version_no_i',
                [
                    Range::ofInt(null, 2),
                    Range::ofInt(2, 3),
                    Range::ofInt(3, null),
                ]
            ),
            new RangeAggregationResult(
                'raw_range',
                [
                    new RangeAggregationResultEntry(Range::ofInt(null, 2), 14),
                    new RangeAggregationResultEntry(Range::ofInt(2, 3), 3),
                    new RangeAggregationResultEntry(Range::ofInt(3, null), 1),
                ]
            ),
        ];
    }
}
