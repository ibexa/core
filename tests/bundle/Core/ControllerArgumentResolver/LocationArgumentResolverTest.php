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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[CoversClass(LocationArgumentResolver::class)]
final class LocationArgumentResolverTest extends TestCase
{
    private const PARAMETER_LOCATION_ID = 'locationId';

    private LocationArgumentResolver $locationArgumentResolver;

    protected function setUp(): void
    {
        $this->locationArgumentResolver = new LocationArgumentResolver(self::createStub(LocationService::class));
    }

    #[DataProvider('dataProviderForTestResolverForUnsupportedRequest')]
    public function testResolveForUnsupportedRequest(
        Request $request,
        ArgumentMetadata $argumentMetadata
    ): void {
        $actualValue = $this->locationArgumentResolver->resolve(
            $request,
            $argumentMetadata
        );

        self::assertEmpty(iterator_to_array($actualValue));
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
            self::createArgumentMetadata(Location::class)
        );

        self::assertInstanceOf(Generator::class, $generator);

        $generator->getReturn();
    }

    public function testResolve(): void
    {
        $resolvedArgumentsGenerator = $this->locationArgumentResolver->resolve(
            self::createRequest(true, false, 1),
            self::createArgumentMetadata(Location::class)
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
     *     \Symfony\Component\HttpFoundation\Request,
     *     \Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata
     * }>
     */
    public static function dataProviderForTestResolverForUnsupportedRequest(): iterable
    {
        $locationBasedArgumentMetadata = self::createArgumentMetadata(Location::class);

        yield 'Not supported - type different than Ibexa\Contracts\Core\Repository\Values\Content\Location' => [
            self::createRequest(true, false, 1),
            self::createArgumentMetadata('foo'),
        ];

        yield 'Not supported - locationId passed to request attributes' => [
            self::createRequest(false, true, 1),
            $locationBasedArgumentMetadata,
        ];

        yield 'Not supported - locationId passed to request attributes and query' => [
            self::createRequest(true, true, 1),
            $locationBasedArgumentMetadata,
        ];
    }

    private static function createArgumentMetadata(string $type): ArgumentMetadata
    {
        return new ArgumentMetadata('locationId', $type, false, false, null);
    }

    private static function createRequest(
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
