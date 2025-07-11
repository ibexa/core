<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation;

use DateTime;
use DateTimeZone;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\DateMetadataRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;

final class DateMetadataRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        $timezone = new DateTimeZone('+0000');

        yield '::MODIFIED' => [
            new DateMetadataRangeAggregation(
                'modification_date',
                DateMetadataRangeAggregation::MODIFIED,
                [
                    Range::ofDateTime(
                        null,
                        new DateTime('2003-01-01', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2003-01-01', $timezone),
                        new DateTime('2004-01-01', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2004-01-01', $timezone),
                        null
                    ),
                ]
            ),
            new RangeAggregationResult(
                'modification_date',
                [
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            null,
                            new DateTime('2003-01-01', $timezone)
                        ),
                        3
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2003-01-01', $timezone),
                            new DateTime('2004-01-01', $timezone)
                        ),
                        3
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2004-01-01', $timezone),
                            null
                        ),
                        12
                    ),
                ]
            ),
        ];

        yield '::PUBLISHED' => [
            new DateMetadataRangeAggregation(
                'publication_date',
                DateMetadataRangeAggregation::PUBLISHED,
                [
                    Range::ofDateTime(
                        null,
                        new DateTime('2003-01-01', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2003-01-01', $timezone),
                        new DateTime('2004-01-01', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2004-01-01', $timezone),
                        null
                    ),
                ]
            ),
            new RangeAggregationResult(
                'publication_date',
                [
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            null,
                            new DateTime('2003-01-01', $timezone)
                        ),
                        6
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2003-01-01', $timezone),
                            new DateTime('2004-01-01', $timezone)
                        ),
                        2
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2004-01-01', $timezone),
                            null
                        ),
                        10
                    ),
                ]
            ),
        ];
    }
}
