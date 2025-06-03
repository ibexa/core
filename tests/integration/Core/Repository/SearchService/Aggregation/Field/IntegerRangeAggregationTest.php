<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field\IntegerRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\AbstractAggregationTestCase;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\FixtureGenerator\FieldAggregationFixtureGenerator;

final class IntegerRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        yield [
            new IntegerRangeAggregation('integer_range', 'content_type', 'integer_field', [
                Range::ofInt(null, 10),
                Range::ofInt(10, 25),
                Range::ofInt(25, 50),
                Range::ofInt(50, null),
            ]),
            new RangeAggregationResult(
                'integer_range',
                [
                    new RangeAggregationResultEntry(Range::ofInt(null, 10), 9),
                    new RangeAggregationResultEntry(Range::ofInt(10, 25), 15),
                    new RangeAggregationResultEntry(Range::ofInt(25, 50), 25),
                    new RangeAggregationResultEntry(Range::ofInt(50, null), 51),
                ]
            ),
        ];
    }

    protected function createFixturesForAggregation(Aggregation $aggregation): void
    {
        $generator = new FieldAggregationFixtureGenerator($this->getRepository());
        $generator->setContentTypeIdentifier('content_type');
        $generator->setFieldDefinitionIdentifier('integer_field');
        $generator->setFieldTypeIdentifier('ezinteger');
        $generator->setValues(range(1, 100));

        $generator->execute();

        $this->refreshSearch($this->getRepository());
    }
}
