<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\Field;

use DateTime;
use DateTimeZone;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field\DateTimeRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\AbstractAggregationTestCase;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\FixtureGenerator\FieldAggregationFixtureGenerator;

final class DateTimeRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        $timezone = new DateTimeZone('+0000');

        yield [
            new DateTimeRangeAggregation(
                'datetime_range',
                'content_type',
                'datetime_field',
                [
                    Range::ofDateTime(
                        null,
                        new DateTime('2020-06-30 00:00:01', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2020-06-30 12:00:00', $timezone),
                        new DateTime('2020-07-30 00:00:00', $timezone)
                    ),
                    Range::ofDateTime(
                        new DateTime('2020-07-30 00:00:01', $timezone),
                        new DateTime('2020-08-01 00:00:03', $timezone)
                    ),
                ]
            ),
            new RangeAggregationResult(
                'datetime_range',
                [
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            null,
                            new DateTime('2020-06-30 00:00:01', $timezone)
                        ),
                        2,
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2020-06-30 12:00:00', $timezone),
                            new DateTime('2020-07-30 00:00:00', $timezone)
                        ),
                        3
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofDateTime(
                            new DateTime('2020-07-30 00:00:01', $timezone),
                            new DateTime('2020-08-01 00:00:03', $timezone)
                        ),
                        3
                    ),
                ]
            ),
        ];
    }

    protected function createFixturesForAggregation(Aggregation $aggregation): void
    {
        $timezone = new DateTimeZone('+0000');

        $generator = new FieldAggregationFixtureGenerator($this->getRepository());
        $generator->setContentTypeIdentifier('content_type');
        $generator->setFieldDefinitionIdentifier('datetime_field');
        $generator->setFieldTypeIdentifier('ibexa_datetime');
        $generator->setValues([
            new DateTime('2020-05-01 00:00:00', $timezone),
            new DateTime('2020-06-30 00:00:00', $timezone),
            new DateTime('2020-06-30 12:00:00', $timezone),
            new DateTime('2020-07-01 00:00:00', $timezone),
            new DateTime('2020-07-01 12:00:00', $timezone),
            new DateTime('2020-07-30 00:00:00', $timezone),
            new DateTime('2020-07-30 12:00:00', $timezone),
            new DateTime('2020-08-01 00:00:01', $timezone),
            new DateTime('2020-08-01 00:00:02', $timezone),
            new DateTime('2020-08-01 00:00:03', $timezone),
        ]);

        $generator->execute();

        $this->refreshSearch($this->getRepository());
    }
}
