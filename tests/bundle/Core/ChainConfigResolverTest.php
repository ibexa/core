<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Exception\ParameterNotFoundException;
use LogicException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver
 */
class ChainConfigResolverTest extends TestCase
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
        $resolver = $this->mockChainConfigResolver();
        $resolver
            ->expects(self::once())
            ->method('sortResolvers')
            ->willReturn(
                [$high, $medium, $low]
            );

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
        $resolver = $this->mockChainConfigResolver();

        $resolver
            ->expects(self::exactly(2))
            ->method('sortResolvers')
            ->willReturnOnConsecutiveCalls(
                [$high, $medium, $low],
                // The second time sortResolvers() is called, we're supposed to get the newly added router ($highest)
                [$highest, $high, $medium, $low]
            );

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
        $this->expectException(LogicException::class);

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
        $this->expectException(ParameterNotFoundException::class);

        $paramName = 'foo';
        $namespace = 'namespace';
        $scope = 'scope';
        foreach ($this->createResolverMocks() as $resolver) {
            $resolver
                ->expects(self::once())
                ->method('getParameter')
                ->with($paramName, $namespace, $scope)
                ->will(self::throwException(new ParameterNotFoundException($paramName, $namespace)));
            $this->chainResolver->addResolver($resolver);
        }

        $this->chainResolver->getParameter($paramName, $namespace, $scope);
    }

    /**
     * @dataProvider getParameterProvider
     *
     * @param string|bool|array<mixed> $expectedValue
     */
    public function testGetParameter(
        string $paramName,
        string $namespace,
        string $scope,
        string|bool|array $expectedValue
    ): void {
        $resolver = $this->createMock(ConfigResolverInterface::class);
        $resolver
            ->expects(self::once())
            ->method('getParameter')
            ->with($paramName, $namespace, $scope)
            ->willReturn($expectedValue);

        $this->chainResolver->addResolver($resolver);
        self::assertSame($expectedValue, $this->chainResolver->getParameter($paramName, $namespace, $scope));
    }

    /**
     * @phpstan-return iterable<array{string, string, string, string|bool|array<mixed>}>
     */
    public static function getParameterProvider(): iterable
    {
        yield ['foo', 'namespace', 'scope', 'someValue'];
        yield ['some.parameter', 'wowNamespace', 'mySiteaccess', ['foo', 'bar']];
        yield ['another.parameter.but.longer.name', 'yetAnotherNamespace', 'anotherSiteaccess', ['foo', ['fruit' => 'apple']]];
        yield ['boolean.parameter', 'yetAnotherNamespace', 'admin', false];
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
            ->willReturn(false);
        $this->chainResolver->addResolver($resolver1);

        $resolver2 = $this->createMock(ConfigResolverInterface::class);
        $resolver2
            ->expects(self::once())
            ->method('hasParameter')
            ->with($paramName, $namespace, $scope)
            ->willReturn(true);
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
            ->willReturn(false);
        $this->chainResolver->addResolver($resolver);

        self::assertFalse($this->chainResolver->hasParameter($paramName, $namespace, $scope));
    }

    /**
     * @return array<\Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject>
     */
    private function createResolverMocks(): array
    {
        return [
            $this->createMock(ConfigResolverInterface::class),
            $this->createMock(ConfigResolverInterface::class),
            $this->createMock(ConfigResolverInterface::class),
        ];
    }

    private function mockChainConfigResolver(): MockObject & ChainConfigResolver
    {
        return $this
            ->getMockBuilder(ChainConfigResolver::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['sortResolvers'])
            ->getMock();
    }
}
