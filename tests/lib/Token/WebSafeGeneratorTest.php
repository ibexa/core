<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Token;

use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;
use Ibexa\Core\Token\WebSafeGenerator;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Token\WebSafeGenerator
 */
final class WebSafeGeneratorTest extends TestCase
{
    private TokenGeneratorInterface $tokenGenerator;

    protected function setUp(): void
    {
        $this->tokenGenerator = new WebSafeGenerator();
    }

    /**
     * @dataProvider provideDataForTestGenerateToken
     *
     * @throws \Exception
     */
    public function testGenerateToken(int $expectedTokenLength): void
    {
        $token = $this->tokenGenerator->generateToken($expectedTokenLength);

        // Check if Generator returns different tokens
        self::assertNotSame(
            $token,
            $this->tokenGenerator->generateToken($expectedTokenLength)
        );

        self::assertSame(
            $expectedTokenLength,
            strlen($token)
        );
    }

    /**
     * @return iterable<array{
     *     int
     * }>
     */
    public function provideDataForTestGenerateToken(): iterable
    {
        yield [20];

        yield [64];

        yield [100];

        yield [256];
    }
}
