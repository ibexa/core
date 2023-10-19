<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\ControllerArgumentResolver;

use Generator;
use Ibexa\Bundle\Core\ControllerArgumentResolver\LocationArgumentResolver;
use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * @covers \Ibexa\Bundle\Core\Converter\LocationArgumentResolver
 */
final class LocationArgumentResolverTest extends TestCase
{
    private const PARAMETER_LOCATION_ID = 'locationId';

    private LocationArgumentResolver $locationArgumentResolver;

    protected function setUp(): void
    {
        $locationService = $this->createMock(LocationService::class);
        $this->locationArgumentResolver = new LocationArgumentResolver($locationService);
    }

    /**
     * @dataProvider provideDataForTestSupports
     */
    public function testSupports(
        bool $expected,
        Request $request,
        ArgumentMetadata $argumentMetadata
    ): void {
        self::assertSame(
            $expected,
            $this->locationArgumentResolver->supports(
                $request,
                $argumentMetadata
            )
        );
    }

    public function testResolveThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'locationId\' is invalid: Expected numeric type, string given.');

        $generator = $this->locationArgumentResolver->resolve(
            new Request(
                [
                    'locationId' => 'foo',
                ]
            ),
            $this->createMock(ArgumentMetadata::class)
        );

        self::assertInstanceOf(Generator::class, $generator);

        $generator->getReturn();
    }

    public function testResolve(): void
    {
        $resolvedArgumentsGenerator = $this->locationArgumentResolver->resolve(
            $this->createRequest(true, false, 1),
            $this->createMock(ArgumentMetadata::class)
        );

        self::assertInstanceOf(Generator::class, $resolvedArgumentsGenerator);
        $resolvedArguments = iterator_to_array($resolvedArgumentsGenerator);

        self::assertCount(1, $resolvedArguments);

        $value = current($resolvedArguments);
        self::assertInstanceOf(
            Location::class,
            $value
        );
    }

    /**
     * @return iterable<array{
     *     bool,
     *     \Symfony\Component\HttpFoundation\Request,
     *     \Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata
     * }>
     */
    public function provideDataForTestSupports(): iterable
    {
        $locationBasedArgumentMetadata = $this->createArgumentMetadata(Location::class);

        yield 'Supported - locationId passed to request query' => [
            true,
            $this->createRequest(true, false, 1),
            $locationBasedArgumentMetadata,
        ];

        yield 'Not supported - type different than Ibexa\Contracts\Core\Repository\Values\Content\Location' => [
            false,
            $this->createRequest(true, false, 1),
            $this->createArgumentMetadata('foo'),
        ];

        yield 'Not supported - locationId passed to request attributes' => [
            false,
            $this->createRequest(false, true, 1),
            $locationBasedArgumentMetadata,
        ];

        yield 'Not supported - locationId passed to request attributes and query' => [
            false,
            $this->createRequest(true, true, 1),
            $locationBasedArgumentMetadata,
        ];
    }

    private function createArgumentMetadata(string $type): ArgumentMetadata
    {
        $argumentMetadata = $this->createMock(ArgumentMetadata::class);
        $argumentMetadata
            ->method('getType')
            ->willReturn($type);

        return $argumentMetadata;
    }

    private function createRequest(
        bool $addToQuery,
        bool $addToAttributes,
        ?int $locationId = null
    ): Request {
        $request = Request::create('/');

        if ($addToQuery) {
            $request->query->set(self::PARAMETER_LOCATION_ID, $locationId);
        }

        if ($addToAttributes) {
            $request->attributes->set(self::PARAMETER_LOCATION_ID, $locationId);
        }

        return $request;
    }
}
