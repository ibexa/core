<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\Field;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Field\FloatRangeAggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResult\RangeAggregationResultEntry;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\AbstractAggregationTestCase;
use Ibexa\Tests\Integration\Core\Repository\SearchService\Aggregation\FixtureGenerator\FieldAggregationFixtureGenerator;

final class FloatRangeAggregationTest extends AbstractAggregationTestCase
{
    public function dataProviderForTestFindContentWithAggregation(): iterable
    {
        yield [
            new FloatRangeAggregation('float_range', 'content_type', 'float_field', [
                Range::ofFloat(null, 10.0),
                Range::ofFloat(10.0, 25.0),
                Range::ofFloat(25.0, 50.0),
                Range::ofFloat(50.0, null),
            ]),
            new RangeAggregationResult(
                'float_range',
                [
                    new RangeAggregationResultEntry(Range::ofFloat(null, 10.0), 4),
                    new RangeAggregationResultEntry(Range::ofFloat(10.0, 25.0), 6),
                    new RangeAggregationResultEntry(Range::ofFloat(25, 50), 10),
                    new RangeAggregationResultEntry(Range::ofFloat(50, null), 20),
                ]
            ),
        ];
    }

    protected function createFixturesForAggregation(Aggregation $aggregation): void
    {
        $generator = new FieldAggregationFixtureGenerator($this->getRepository());
        $generator->setContentTypeIdentifier('content_type');
        $generator->setFieldDefinitionIdentifier('float_field');
        $generator->setFieldTypeIdentifier('ezfloat');
        $generator->setValues(range(1.0, 100.0, 2.5));

        $generator->execute();

        $this->refreshSearch($this->getRepository());
    }
}
