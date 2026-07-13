<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver
 */
final class ChainConfigResolverTest extends TestCase
{
    private ChainConfigResolver $chainResolver;

    protected function setUp(): void
    {
        $this->chainResolver = new ChainConfigResolver();
    }

    public function testPriority(): void
    {
        self::assertEquals([], $this->chainResolver->getAllResolvers());

        [$low, $high] = $this->createResolverMocks();

        $this->chainResolver->addResolver($low, 10);
        $this->chainResolver->addResolver($high, 100);

        self::assertEquals(
            [
                $high,
                $low,
            ],
            $this->chainResolver->getAllResolvers()
        );
    }

    /**
     * Resolvers are supposed to be sorted only once.
     * This test will check that by trying to get all resolvers several times.
     */
    public function testSortResolvers(): void
    {
        [$low, $medium, $high] = $this->createResolverMocks();
        // We're using a mock here and not $this->chainResolver because we need to ensure that the sorting operation is done only once.
        $resolver = $this->buildMock();
        $resolver
            ->expects(self::once())
            ->method('sortResolvers')
            ->willReturn([$high, $medium, $low]);

        $resolver->addResolver($low, 10);
        $resolver->addResolver($medium, 50);
        $resolver->addResolver($high, 100);
        $expectedSortedRouters = [$high, $medium, $low];
        // Let's get all routers 5 times, we should only sort once.
        for ($i = 0; $i < 5; ++$i) {
            self::assertSame($expectedSortedRouters, $resolver->getAllResolvers());
        }
    }

    /**
     * This test ensures that if a resolver is being added on the fly, the sorting is reset.
     */
    public function testReSortResolvers(): void
    {
        [$low, $medium, $high] = $this->createResolverMocks();
        $highest = clone $high;
        // We're using a mock here and not $this->chainResolver because we need to ensure that the sorting operation is done only once.
        $resolver = $this->buildMock();
        $counter = self::exactly(2);

        $resolver
            ->expects($counter)
            ->method('sortResolvers')
            ->willReturnCallback(static function () use ($counter, $highest, $high, $medium, $low): array {
                return match ($counter->getInvocationCount()) {
                    1 => [$high, $medium, $low],
                    2 => [$highest, $high, $medium, $low],
                    default => throw new \LogicException('Unexpected call to sortResolvers'),
                };
            });

        $resolver->addResolver($low, 10);
        $resolver->addResolver($medium, 50);
        $resolver->addResolver($high, 100);
        self::assertSame(
            [$high, $medium, $low],
            $resolver->getAllResolvers()
        );

        // Now adding another resolver on the fly, sorting must have been reset
        $resolver->addResolver($highest, 101);
        self::assertSame(
            [$highest, $high, $medium, $low],
            $resolver->getAllResolvers()
        );
    }

    public function testGetDefaultNamespace(): void
    {
        $this->expectException(\LogicException::class);

        $this->chainResolver->getDefaultNamespace();
    }

    public function testSetDefaultNamespace(): void
    {
        $namespace = 'foo';
        foreach ($this->createResolverMocks() as $i => $resolver) {
            $resolver
                ->expects(self::once())
                ->method('setDefaultNamespace')
                ->with($namespace);
            $this->chainResolver->addResolver($resolver, $i);
        }

        $this->chainResolver->setDefaultNamespace($namespace);
    }

    public function testGetParameterInvalid(): void
    {
        $paramName = 'foo';
        $namespace = 'namespace';
        $scope = 'scope';
        foreach ($this->createResolverMocks() as $resolver) {
            $resolver
                ->expects(self::once())
                ->method('getParameter')
                ->with($paramName, $namespace, $scope)
                ->willThrowException(new ParameterNotFoundException($paramName, $namespace));
            $this->chainResolver->addResolver($resolver);
        }

        $this->expectException(ParameterNotFoundException::class);
        $this->chainResolver->getParameter($paramName, $namespace, $scope);
    }

    /**
     * @dataProvider getParameterProvider
     */
    public function testGetParameter(
        string $paramName,
        string $namespace,
        string $scope,
        mixed $expectedValue,
    ): void {
        $resolver = $this->createMock(ConfigResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('getParameter')
            ->with($paramName, $namespace, $scope)
            ->will(self::returnValue($expectedValue));

        $this->chainResolver->addResolver($resolver);
        self::assertSame($expectedValue, $this->chainResolver->getParameter($paramName, $namespace, $scope));
    }

    /**
     * @return iterable<array{string, string, string, mixed}>
     */
    public function getParameterProvider(): iterable
    {
        return [
            ['foo', 'namespace', 'scope', 'someValue'],
            ['some.parameter', 'wowNamespace', 'mySiteaccess', ['foo', 'bar']],
            ['another.parameter.but.longer.name', 'yetAnotherNamespace', 'anotherSiteaccess', ['foo', ['fruit' => 'apple']]],
            ['boolean.parameter', 'yetAnotherNamespace', 'admin', false],
        ];
    }

    public function testHasParameterTrue(): void
    {
        $paramName = 'foo';
        $namespace = 'yetAnotherNamespace';
        $scope = 'mySiteaccess';

        $resolver1 = $this->createMock(ConfigResolverInterface::class);
        $resolver1
            ->expects(self::once())
            ->method('hasParameter')
            ->with($paramName, $namespace, $scope)
            ->will(self::returnValue(false));
        $this->chainResolver->addResolver($resolver1);

        $resolver2 = $this->createMock(ConfigResolverInterface::class);
        $resolver2
            ->expects(self::once())
            ->method('hasParameter')
            ->with($paramName, $namespace, $scope)
            ->will(self::returnValue(true));
        $this->chainResolver->addResolver($resolver2);

        $resolver3 = $this->createMock(ConfigResolverInterface::class);
        $resolver3
            ->expects(self::never())
            ->method('hasParameter');
        $this->chainResolver->addResolver($resolver3);

        self::assertTrue($this->chainResolver->hasParameter($paramName, $namespace, $scope));
    }

    public function testHasParameterFalse(): void
    {
        $paramName = 'foo';
        $namespace = 'yetAnotherNamespace';
        $scope = 'mySiteaccess';

        $resolver = $this->createMock(ConfigResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('hasParameter')
            ->with($paramName, $namespace, $scope)
            ->will(self::returnValue(false));
        $this->chainResolver->addResolver($resolver);

        self::assertFalse($this->chainResolver->hasParameter($paramName, $namespace, $scope));
    }

    /**
     * @phpstan-return array<\PHPUnit\Framework\MockObject\MockObject & ConfigResolverInterface>
     */
    private function createResolverMocks(): array
    {
        return [
            $this->createMock(ConfigResolverInterface::class),
            $this->createMock(ConfigResolverInterface::class),
            $this->createMock(ConfigResolverInterface::class),
        ];
    }

    private function buildMock(): MockObject & ChainConfigResolver
    {
        return $this
            ->getMockBuilder(ChainConfigResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sortResolvers'])
            ->getMock();
    }
}
