<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Token;

use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;
use Ibexa\Core\Token\RandomBytesGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Token\RandomBytesGenerator
 */
final class RandomBytesGeneratorTest extends TestCase
{
    private TokenGeneratorInterface $tokenGenerator;

    protected function setUp(): void
    {
        $this->tokenGenerator = new RandomBytesGenerator();
    }

    /**
     * @dataProvider provideDataForTestGenerateToken
     *
     * @throws \Exception
     */
    public function testGenerateToken(
        int $expectedTokenLengthInBytes,
        int $length
    ): void {
        $generatedToken = $this->tokenGenerator->generateToken($length);

        self::assertNotSame(
            $generatedToken,
            $this->tokenGenerator->generateToken($length),
            'Token generator should return different values on subsequent calls',
        );

        self::assertSame(
            $expectedTokenLengthInBytes,
            strlen($generatedToken)
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     int
     * }>
     */
    public function provideDataForTestGenerateToken(): iterable
    {
        yield [
            15,
            20,
        ];

        yield [
            48,
            64,
        ];

        yield [
            75,
            100,
        ];

        yield [
            192,
            256,
        ];
    }
}
