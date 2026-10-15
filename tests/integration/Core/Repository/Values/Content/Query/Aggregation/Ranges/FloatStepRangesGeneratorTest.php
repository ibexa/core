<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\FloatStepRangesGenerator;
use PHPUnit\Framework\TestCase;

final class FloatStepRangesGeneratorTest extends TestCase
{
    public function testGenerateOpenRangesSequence(): void
    {
        self::assertGeneratorResults(
            [
                Range::ofFloat(Range::INF, 1.0),
                Range::ofFloat(1.0, 2.0),
                Range::ofFloat(2.0, 3.0),
                Range::ofFloat(3.0, Range::INF),
            ],
            new FloatStepRangesGenerator(1.0, 3.0)
        );
    }

    public function testGenerateCloseRangesSequence(): void
    {
        $generator = new FloatStepRangesGenerator(1.0, 3.0);
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults(
            [
                Range::ofFloat(1.0, 2.0),
                Range::ofFloat(2.0, 3.0),
            ],
            $generator
        );
    }

    public function testGenerateRangesWithCustomStep(): void
    {
        $generator = new FloatStepRangesGenerator(1.0, 10.0);
        $generator->setStep(2.0);

        self::assertGeneratorResults(
            [
                Range::ofFloat(Range::INF, 1.0),
                Range::ofFloat(1.0, 3.0),
                Range::ofFloat(3.0, 5.0),
                Range::ofFloat(5.0, 7.0),
                Range::ofFloat(7.0, 9.0),
                Range::ofFloat(10.0, Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateInfRangesSequence(): void
    {
        $generator = new FloatStepRangesGenerator(0.0, 0.0);

        self::assertGeneratorResults(
            [
                Range::ofFloat(Range::INF, Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateEmptyRangesSequence(): void
    {
        $generator = new FloatStepRangesGenerator(0.0, 0.0);
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults([], $generator);
    }

    /**
     * @param Range<float>[] $expectedResult
     */
    private static function assertGeneratorResults(
        array $expectedResult,
        FloatStepRangesGenerator $generator
    ): void {
        self::assertEquals($expectedResult, $generator->generate());
    }
}
