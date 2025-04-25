<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\PHPUnit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Tests\Core\PHPUnit\InvocationMocker
 */
final class InvocationMockerTest extends TestCase
{
    /**
     * @throws \JsonException
     */
    public function testExpect(): void
    {
        $invocationMocker = new InvocationMocker('testMethod');
        $invocationMocker->expect([1, 2, 3], 42);

        self::assertSame(42, $invocationMocker(1, 2, 3));
    }

    /**
     * @phpstan-return iterable<string, array{0: callable, 1: string}>
     */
    public function getDataForFailedAssertions(): iterable
    {
        yield 'too many invocations' => [
            static function () {
                $invocationMocker = new InvocationMocker('testMethod');
                $invocationMocker->expect([1, 2, 3], 42);
                $invocationMocker(1, 2, 3);
                $invocationMocker(1, 2, 3);
            },
            'Failed to find testMethod invocation at 1 index',
        ];

        yield 'invocation with incorrect arguments' => [
            static function () {
                $invocationMocker = new InvocationMocker('testMethod');
                $invocationMocker->expect(['foo'], null);
                $invocationMocker(1, 5, 3);
            },
            'Expected testMethod to be invoked with ["foo"] arguments but got [1,5,3] at 0 index',
        ];
    }

    /**
     * @dataProvider getDataForFailedAssertions
     */
    public function testExpectThrowsAssertionFailedError(callable $invocation, string $expectedMessage): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage($expectedMessage);

        $invocation();
    }

    public function testGetExpectedInvocationCount(): void
    {
        $invocationMocker = new InvocationMocker('testMethod');
        self::assertSame(0, $invocationMocker->getExpectedInvocationCount());

        $invocationMocker->expect([1, 2, 3], 42);
        $invocationMocker->expect([5, 7, 9], 55);

        self::assertSame(2, $invocationMocker->getExpectedInvocationCount());
    }
}
