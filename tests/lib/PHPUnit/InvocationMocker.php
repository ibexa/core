<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\PHPUnit;

use PHPUnit\Framework\Assert;
use Stringable;

/**
 * @internal
 *
 * @experimental
 *
 * This is an experimental replacement for PHPUnit's deprecated `at` method.
 * Register expected calls using the ` expect ` method and then mock `willReturnCallback` with InvocationMocker as callback.
 *
 * ```
 * $mocker = new InvocationMocker('methodName');
 * $mocker->expects(['argument1 a', 'argument2 a'], 'returnValue a');
 * $mocker->expects(['argument1 b', 'argument2 b'], 'returnValue b');
 *
 * $mock = $this->createMock(MyInterface::class);
 * $mock
 *      ->expects(self::exactly($mocker->getExpectedInvocationCount()))
 *      ->method('methodName')
 *      ->willReturnCallback($mocker);
 * ```
 */
final class InvocationMocker implements Stringable
{
    /** @phpstan-var list<array{arguments: list<mixed>, returnValue: mixed}> */
    private array $invocationMap = [];

    private int $currentInvocationIndex = 0;

    public function __construct(private readonly string $methodName)
    {
    }

    /**
     * @phpstan-param list<mixed> $arguments
     */
    public function expect(array $arguments, mixed $returnValue): void
    {
        $this->invocationMap[] = [
            'arguments' => $arguments,
            'returnValue' => $returnValue,
        ];
    }

    /**
     * @phpstan-return int<0, max>
     */
    public function getExpectedInvocationCount(): int
    {
        return count($this->invocationMap);
    }

    /**
     * @throws \PHPUnit\Framework\AssertionFailedError
     * @throws \JsonException
     */
    public function __invoke(): mixed
    {
        if (!isset($this->invocationMap[$this->currentInvocationIndex])) {
            Assert::fail("Failed to find $this->methodName invocation at $this->currentInvocationIndex index");
        }
        $invocation = $this->invocationMap[$this->currentInvocationIndex];

        $args = func_get_args();
        Assert::assertSame(
            $invocation['arguments'],
            $args,
            sprintf(
                'Expected %s to be invoked with %s arguments but got %s at %d index',
                $this->methodName,
                json_encode($invocation['arguments'], JSON_THROW_ON_ERROR),
                json_encode($args, JSON_THROW_ON_ERROR),
                $this->currentInvocationIndex
            )
        );
        ++$this->currentInvocationIndex;

        return $invocation['returnValue'];
    }

    public function __toString(): string
    {
        return sprintf('%s invocation mocker', $this->methodName);
    }
}
