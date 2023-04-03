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
     * @throws \Exception
     */
    public function testGenerateToken(
        int $expectedTokenLength,
        string $mockGeneratorOutputToken,
        string $expectedToken
    ): void {
        $this->mockTokenGeneratorGenerateToken(
            $expectedTokenLength,
            $mockGeneratorOutputToken
        );

        $generatedToken = $this->tokenGenerator->generateToken($expectedTokenLength);

        self::assertSame(
            $expectedTokenLength,
            strlen($generatedToken)
        );

        self::assertSame(
            $expectedToken,
            $generatedToken
        );
    }

    /**
     * @return iterable<array{
     *     int,
     *     string,
     *     string
     * }>
     */
    public function provideDataForTestGenerateToken(): iterable
    {
        yield [
            20,
            '123456+-1az2w3edc4==',
            'MTIzNDU2Ky0xYXoydzNl',
        ];

        yield [
            64,
            '123/561qaz2wsx3edc4rfv1234561qaz2wsx+-dc=3edc4rv1234561qarfv145=',
            'MTIzLzU2MXFhejJ3c3gzZWRjNHJmdjEyMzQ1NjFxYXoyd3N4Ky1kYz0zZWRjNHJ2',
        ];

        yield [
            100,
            '+-34561qaz2wsx3ec4rfv1234561qax3edc4rfv5tgbz2wsxaz2wsxdc4rfv123ec4rfv1234561qaz2wsx3edc4rfv457yhnzz=',
            'Ky0zNDU2MXFhejJ3c3gzZWM0cmZ2MTIzNDU2MXFheDNlZGM0cmZ2NXRnYnoyd3N4YXoyd3N4ZGM0cmZ2MTIzZWM0cmZ2MTIzNDU2',
        ];

        yield [
            256,
            '1234561qaz2wsx3ed+-rfv1234561qa561qaz2wsx3edc4rfv1==234561qaz2wsxz2wsx3ec4rfv1234561qaz2wsx3edc4rfv145=7yhnzz1234561qaz2wsx3edc4rfv1234561qaz2wsx3ec4rfv1234561qaz2wsx3edc4rfv1fv1234561qaz2wsx3ec4rf==234564567yhnz3ec4rfv1234561qaz2wsx3edc4rfv14567yhnzz12345',
            'MTIzNDU2MXFhejJ3c3gzZWQrLXJmdjEyMzQ1NjFxYTU2MXFhejJ3c3gzZWRjNHJmdjE9PTIzNDU2MXFhejJ3c3h6MndzeDNlYzRyZnYxMjM0NTYxcWF6MndzeDNlZGM0cmZ2MTQ1PTd5aG56ejEyMzQ1NjFxYXoyd3N4M2VkYzRyZnYxMjM0NTYxcWF6MndzeDNlYzRyZnYxMjM0NTYxcWF6MndzeDNlZGM0cmZ2MWZ2MTIzNDU2MXFhejJ3c3gz',
        ];
    }

    private function mockTokenGeneratorGenerateToken(
        int $length,
        string $token
    ): void {
        $this->randomBytesTokenGenerator
            ->expects(self::once())
            ->method('generateToken')
            ->with($length)
            ->willReturn($token);
    }
}
