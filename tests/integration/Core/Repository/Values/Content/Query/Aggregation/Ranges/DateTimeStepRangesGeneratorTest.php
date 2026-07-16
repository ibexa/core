<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Values\Content\Query\Aggregation\Ranges;

use DateInterval;
use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Ranges\DateTimeStepRangesGenerator;
use PHPUnit\Framework\TestCase;

final class DateTimeStepRangesGeneratorTest extends TestCase
{
    public function testGenerateOpenRangesSequence(): void
    {
        self::assertGeneratorResults(
            [
                $this->createRange(Range::INF, '01-01-2023'),
                $this->createRange('01-01-2023', '02-01-2023'),
                $this->createRange('02-01-2023', '03-01-2023'),
                $this->createRange('03-01-2023', Range::INF),
            ],
            new DateTimeStepRangesGenerator(
                new DateTimeImmutable('01-01-2023 00:00:00'),
                new DateTimeImmutable('03-01-2023 00:00:00')
            )
        );
    }

    public function testGenerateCloseRangesSequence(): void
    {
        $generator = new DateTimeStepRangesGenerator(
            new DateTimeImmutable('01-01-2023 00:00:00'),
            new DateTimeImmutable('03-01-2023 00:00:00')
        );
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults(
            [
                $this->createRange('01-01-2023', '02-01-2023'),
                $this->createRange('02-01-2023', '03-01-2023'),
            ],
            $generator
        );
    }

    public function testGenerateRangesWithCustomStep(): void
    {
        $generator = new DateTimeStepRangesGenerator(
            new DateTimeImmutable('01-01-2023 00:00:00'),
            new DateTimeImmutable('05-01-2023 00:00:00')
        );
        $generator->setStep(new DateInterval('P2D'));

        self::assertGeneratorResults(
            [
                $this->createRange(Range::INF, '01-01-2023'),
                $this->createRange('01-01-2023', '03-01-2023'),
                $this->createRange('03-01-2023', '05-01-2023'),
                $this->createRange('05-01-2023', Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateInfRangesSequence(): void
    {
        $generator = new DateTimeStepRangesGenerator(
            new DateTimeImmutable('01-01-1970 00:00:00'),
            new DateTimeImmutable('01-01-1970 00:00:00'),
        );

        self::assertGeneratorResults(
            [
                Range::ofDateTime(Range::INF, Range::INF),
            ],
            $generator
        );
    }

    public function testGenerateEmptyRangesSequence(): void
    {
        $generator = new DateTimeStepRangesGenerator(
            new DateTimeImmutable('01-01-1970 00:00:00'),
            new DateTimeImmutable('01-01-1970 00:00:00'),
        );
        $generator->setLeftOpen(false);
        $generator->setRightOpen(false);

        self::assertGeneratorResults([], $generator);
    }

    /**
     * @phpstan-return Range<\DateTimeInterface>
     */
    private function createRange(
        ?string $start,
        ?string $end
    ): Range {
        return Range::ofDateTime(
            $start !== null ? new DateTimeImmutable($start . ' 00:00:00') : null,
            $end !== null ? new DateTimeImmutable($end . ' 00:00:00') : null
        );
    }

    /**
     * @param Range<\DateTimeInterface>[] $expectedResult
     */
    private static function assertGeneratorResults(
        array $expectedResult,
        DateTimeStepRangesGenerator $generator
    ): void {
        self::assertEquals($expectedResult, $generator->generate());
    }
}
