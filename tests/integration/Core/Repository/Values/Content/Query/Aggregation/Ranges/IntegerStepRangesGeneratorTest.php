<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\IntegerStepRangesGenerator;
use PHPUnit\Framework\TestCase;

final class IntegerStepRangesGeneratorTest extends TestCase
{
    public function testGenerateOpenRangesSequence(): void
    {
        self::assertGeneratorResults(
            [
                Range::ofInt(Range::INF, 1),
                Range::ofInt(1, 2),
                Range::ofInt(2, 3),
                Range::ofInt(3, Range::INF),
            ],
            new IntegerStepRangesGenerator(1, 3)
        );
    }

    public function testGenerateCloseRangesSequence(): void
    {
        $generator = new IntegerStepRangesGenerator(1, 3);
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults(
            [
                Range::ofInt(1, 2),
                Range::ofInt(2, 3),
            ],
            $generator
        );
    }

    public function testGenerateRangesWithCustomStep(): void
    {
        $generator = new IntegerStepRangesGenerator(1, 10);
        $generator->setStep(2);

        self::assertGeneratorResults(
            [
                Range::ofInt(Range::INF, 1),
                Range::ofInt(1, 3),
                Range::ofInt(3, 5),
                Range::ofInt(5, 7),
                Range::ofInt(7, 9),
                Range::ofInt(10, Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateInfRangesSequence(): void
    {
        $generator = new IntegerStepRangesGenerator(0, 0);

        self::assertGeneratorResults(
            [
                Range::ofInt(Range::INF, Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateEmptyRangesSequence(): void
    {
        $generator = new IntegerStepRangesGenerator(0, 0);
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults([], $generator);
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<int>[] $expectedResult
     */
    private static function assertGeneratorResults(array $expectedResult, IntegerStepRangesGenerator $generator): void
    {
        self::assertEquals(
            $expectedResult,
            iterator_to_array($generator->generate())
        );
    }
}
