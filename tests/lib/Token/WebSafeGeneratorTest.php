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

    /** @var \Ibexa\Contracts\Core\Token\TokenGeneratorInterface&\PHPUnit\Framework\MockObject\MockObject */
    private TokenGeneratorInterface $randomBytesTokenGenerator;

    protected function setUp(): void
    {
        $this->randomBytesTokenGenerator = $this->createMock(TokenGeneratorInterface::class);
        $this->tokenGenerator = new WebSafeGenerator($this->randomBytesTokenGenerator);
    }

    /**
     * @dataProvider provideDataForTestGenerateToken
     *
     * @param array<array<int>> $tokenGeneratingArguments
     * @param array<string> $tokens
     *
     * @throws \Exception
     */
    public function testGenerateToken(
        int $expectedTokenLength,
        array $tokenGeneratingArguments,
        array $tokens
    ): void {
        $this->mockTokenGeneratorGenerateToken($tokenGeneratingArguments, $tokens);

        $generatedToken = $this->tokenGenerator->generateToken($expectedTokenLength);

        // Check if Generator returns different tokens
        self::assertNotSame(
            $generatedToken,
            $this->tokenGenerator->generateToken($expectedTokenLength)
        );

        self::assertSame(
            $expectedTokenLength,
            strlen($generatedToken)
        );

        // Check if generated token is web safe
        self::assertStringNotContainsString(
            $generatedToken,
            '='
        );

        self::assertStringNotContainsString(
            $generatedToken,
            '+-'
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     array<array<int>>,
     *     array<string>
     * }>
     */
    public function provideDataForTestGenerateToken(): iterable
    {
        yield [
            20,
            [
                [20],
                [20],
            ],
            [
                '1234561qaz2wsx3edc4rfv',
                '2wsx3edc4rfv5tgb04ddda',
            ],
        ];

        yield [
            64,
            [
                [64],
                [64],
            ],
            [
                '1234561qaz2wsx3edc4rfv1234561qaz2wsx3edc4rfv14567',
                '2wsx3edc4rfv5tgb04ddda2wsx3edc4rfv5tgb04dddazxcvb',
            ],
        ];

        yield [
            100,
            [
                [100],
                [100],
            ],
            [
                '1234561qaz2wsx3edc4rfv1234561qaz2wsx3ec4rfv1234561qaz2wsx3edc4rfv14567yhnzz',
                '2wsx3edc4rfv5tgb04ddda2ws2wsx3edc4rfv5tgb04dddazxcvb5678913ec4rfv12aaazccwwa',
            ],
        ];

        yield [
            256,
            [
                [256],
                [256],
            ],
            [
                '1234561qaz2wsx3edc4rfv1234561qaz2wsx3ec4rfv1234561qaz2wsx3edc4rfv14567yhnzz1234561qaz2wsx3edc4rfv1234561qaz2wsx3ec4rfv1234561qaz2wsx3edc4rfv14567yhnz3ec4rfv1234561qaz2wsx3edc4rfv14567yhnzz12345',
                '4rfv1234561qaz2wsx3ec4rfv1234561qaz2561qaz2wsx3ec4rfv1234561qaz2wsx3edc4rfv14567yhnzz1234561qaz2wsx3edcrfv1234561qazwsx3edc4rfv14567yhnz3ec41234561qaz2wsx3edc4rfv12342wsx3edc4rfv14567yhnzz12345',
            ],
        ];
    }

    /**
     * @param array<array<int>> $tokenGeneratingArguments
     * @param array<string> $tokens
     */
    private function mockTokenGeneratorGenerateToken(
        array $tokenGeneratingArguments,
        array $tokens
    ): void {
        $this->randomBytesTokenGenerator
            ->expects(self::atLeastOnce())
            ->method('generateToken')
            ->withConsecutive(...$tokenGeneratingArguments)
            ->willReturnOnConsecutiveCalls(...$tokens);
    }
}
