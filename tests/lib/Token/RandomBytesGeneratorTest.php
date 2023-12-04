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
    public function testGenerateToken(int $expectedTokenLength): void
    {
        $generatedToken = $this->tokenGenerator->generateToken($expectedTokenLength);

        self::assertNotSame(
            $generatedToken,
            $this->tokenGenerator->generateToken($expectedTokenLength),
            'Token generator should return different values on subsequent calls',
        );

        self::assertSame(
            $expectedTokenLength,
            strlen($generatedToken)
        );
    }

    /**
     * @return iterable<array{
     *     int
     * }>
     */
    public function provideDataForTestGenerateToken(): iterable
    {
        yield [
            20,
        ];

        yield [
            64,
        ];

        yield [
            100,
        ];

        yield [
            256,
        ];
    }
}
