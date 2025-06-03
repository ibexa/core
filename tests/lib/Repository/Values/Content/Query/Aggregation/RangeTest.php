<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Content\Query\Aggregation;

use DateTimeImmutable;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range;
use PHPUnit\Framework\TestCase;

final class RangeTest extends TestCase
{
    /**
     * @dataProvider dataProviderForTestToString
     *
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed> $range
     */
    public function testToString(Range $range, string $expected): void
    {
        self::assertEquals($expected, (string)$range);
    }

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed>, string}>
     */
    public function dataProviderForTestToString(): iterable
    {
        yield 'empty' => [
            new Range(null, null),
            '[*;*)',
        ];

        yield 'int' => [
            new Range(1, 10),
            '[1;10)',
        ];

        yield 'float' => [
            new Range(0.25, 3.25),
            '[0.25;3.25)',
        ];

        yield 'datetime' => [
            new Range(
                new DateTimeImmutable('2020-01-01T00:00:00+0000'),
                new DateTimeImmutable('2020-12-31T23:59:59+0000'),
            ),
            '[2020-01-01T00:00:00+0000;2020-12-31T23:59:59+0000)',
        ];
    }

    public function testOfInt(): void
    {
        self::assertEquals(new Range(null, 10), Range::ofInt(null, 10));
        self::assertEquals(new Range(1, 10), Range::ofInt(1, 10));
        self::assertEquals(new Range(1, null), Range::ofInt(1, null));
    }

    public function testOfFloat(): void
    {
        self::assertEquals(new Range(null, 10.0), Range::ofFloat(null, 10.0));
        self::assertEquals(new Range(1.0, 10.0), Range::ofFloat(1.0, 10.0));
        self::assertEquals(new Range(1.0, null), Range::ofFloat(1.0, null));
    }

    public function testOfDateTime(): void
    {
        $a = new DateTimeImmutable('2020-01-01T00:00:00+0000');
        $b = new DateTimeImmutable('2020-12-31T23:59:59+0000');

        self::assertEquals(new Range(null, $b), Range::ofDateTime(null, $b));
        self::assertEquals(new Range($a, $b), Range::ofDateTime($a, $b));
        self::assertEquals(new Range($a, null), Range::ofDateTime($a, null));
    }

    /**
     * @dataProvider dataProviderForEqualsTo
     *
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed> $rangeA
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed> $rangeB
     */
    public function testEqualsTo(Range $rangeA, Range $rangeB, bool $expectedResult): void
    {
        self::assertEquals($expectedResult, $rangeA->equalsTo($rangeB));
        self::assertEquals($expectedResult, $rangeB->equalsTo($rangeA));
    }

    /**
     * @return iterable<string, array{\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed>, \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation\Range<mixed>, bool}>
     */
    public function dataProviderForEqualsTo(): iterable
    {
        yield 'int (true)' => [
            Range::ofInt(1, 10),
            Range::ofInt(1, 10),
            true,
        ];

        yield 'int (false)' => [
            Range::ofInt(1, 10),
            Range::ofInt(1, 100),
            false,
        ];

        yield 'float (true)' => [
            Range::ofFloat(1.0, 10.0),
            Range::ofFloat(1.0, 10.0),
            true,
        ];

        yield 'float (false)' => [
            Range::ofFloat(1.0, 10.0),
            Range::ofFloat(1.0, 100.0),
            false,
        ];

        yield 'data & time (true)' => [
            Range::ofDateTime(
                new DateTimeImmutable('2023-01-01 00:00:00'),
                new DateTimeImmutable('2023-12-01 00:00:00')
            ),
            Range::ofDateTime(
                new DateTimeImmutable('2023-01-01 00:00:00'),
                new DateTimeImmutable('2023-12-01 00:00:00')
            ),
            true,
        ];

        yield 'data & time (false)' => [
            Range::ofDateTime(
                new DateTimeImmutable('2023-01-01 00:00:00'),
                new DateTimeImmutable('2023-12-01 00:00:00')
            ),
            Range::ofDateTime(
                new DateTimeImmutable('2024-01-01 00:00:00'),
                new DateTimeImmutable('2024-12-01 00:00:00')
            ),
            false,
        ];
    }
}
