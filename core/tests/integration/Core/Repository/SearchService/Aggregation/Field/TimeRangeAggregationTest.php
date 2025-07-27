<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field\TimeRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;
use Ibexa\Core\FieldType\Time\Value as TimeValue;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\AbstractAggregationTestCase;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\FixtureGenerator\FieldAggregationFixtureGenerator;
use RuntimeException;

final class TimeRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        yield [
            new TimeRangeAggregation(
                'time_term',
                'content_type',
                'time_field',
                [
                    Range::ofInt(null, $this->mktime(7, 0, 0, 0, 0, 0)),
                    Range::ofInt(
                        $this->mktime(7, 0, 0, 0, 0, 0),
                        $this->mktime(12, 0, 0, 0, 0, 0)
                    ),
                    Range::ofInt($this->mktime(12, 0, 0, 0, 0, 0), null),
                ]
            ),
            new RangeAggregationResult(
                'time_term',
                [
                    new RangeAggregationResultEntry(
                        Range::ofInt(null, $this->mktime(7, 0, 0, 0, 0, 0)),
                        2
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofInt(
                            $this->mktime(7, 0, 0, 0, 0, 0),
                            $this->mktime(12, 0, 0, 0, 0, 0)
                        ),
                        2
                    ),
                    new RangeAggregationResultEntry(
                        Range::ofInt($this->mktime(12, 0, 0, 0, 0, 0), null),
                        3
                    ),
                ]
            ),
        ];
    }

    protected function createFixturesForAggregation(Aggregation $aggregation): void
    {
        $generator = new FieldAggregationFixtureGenerator($this->getRepository());
        $generator->setContentTypeIdentifier('content_type');
        $generator->setFieldDefinitionIdentifier('time_field');
        $generator->setFieldTypeIdentifier('ibexa_time');
        $generator->setValues([
            new TimeValue($this->mktime(6, 45, 0, 0, 0, 0)),
            new TimeValue($this->mktime(7, 0, 0, 0, 0, 0)),
            new TimeValue($this->mktime(6, 30, 0, 0, 0, 0)),
            new TimeValue($this->mktime(11, 45, 0, 0, 0, 0)),
            new TimeValue($this->mktime(16, 00, 0, 0, 0, 0)),
            new TimeValue($this->mktime(17, 00, 0, 0, 0, 0)),
            new TimeValue($this->mktime(17, 30, 0, 0, 0, 0)),
        ]);

        $generator->execute();

        $this->refreshSearch($this->getRepository());
    }

    private function mktime(int $hour, int $minute, int $second, int $month, int $day, int $year): int
    {
        $timestamp = mktime($hour, $minute, $second, $month, $day, $year);
        if ($timestamp === false) {
            throw new RuntimeException('Failed to create timestamp with mktime.');
        }

        return $timestamp;
    }
}
