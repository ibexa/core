<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\TokenService;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\Base\Exceptions\TokenLengthException;

/**
 * @covers \Ibexa\Core\Repository\TokenService
 */
final class TokenServiceTest extends IbexaKernelTestCase
{
    private const TOKEN_TYPE = 'foo';
    private const TOKEN_TTL = 100;
    private const TOKEN_IDENTIFIER = 'test';

    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();

        self::loadSchema();
        self::loadFixtures();

        $this->tokenService = self::getServiceByClassName(TokenService::class);
    }

    /**
     * @dataProvider provideTokenData
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGenerateToken(
        string $type,
        int $tll,
        ?string $identifier,
        int $length = 64
    ): void {
        $token = $this->tokenService->generateToken($type, $tll, $identifier, $length);

        self::assertSame($type, $token->getType());
        self::assertSame($identifier, $token->getIdentifier());
        self::assertSame($length, strlen($token->getToken()));
        self::assertFalse($token->isRevoked());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGenerateTokenThrowsTokenLengthException(): void
    {
        $length = 300;

        $this->expectException(TokenLengthException::class);
        $this->expectExceptionMessage('Token length is too long: 300 characters. Max length is 255.');

        $this->tokenService->generateToken(
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER,
            $length
        );
    }

    /**
     * @dataProvider provideDataForTestCheckToken
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testCheckExistingToken(
        string $type,
        int $tll,
        ?string $identifier
    ): void {
        $token = $this->tokenService->generateToken($type, $tll, $identifier);

        self::assertTrue(
            $this->tokenService->checkToken(
                $token->getType(),
                $token->getToken(),
                $token->getIdentifier()
            )
        );
    }

    public function testCheckNotExistentToken(): void
    {
        self::assertFalse(
            $this->tokenService->checkToken(
                'bar',
                '1qaz2wsx3edc4rfv5tgb6yhn7ujm8ik,',
                'test'
            )
        );
    }

    /**
     * @dataProvider provideTokenData
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testGetToken(
        string $type,
        int $tll,
        ?string $identifier,
        int $length = 64
    ): void {
        $token = $this->tokenService->generateToken($type, $tll, $identifier, $length);

        self::assertEquals(
            $token,
            $this->tokenService->getToken($type, $token->getToken(), $identifier)
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testRevokeToken(): void
    {
        $token = $this->tokenService->generateToken(
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER
        );

        $this->tokenService->revokeToken($token);

        $this->assertRevokedToken($token);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testRevokeAllTokensByIdentifier(): void
    {
        $tokens = [
            $this->tokenService->generateToken(
                self::TOKEN_TYPE,
                self::TOKEN_TTL,
                self::TOKEN_IDENTIFIER
            ),
            $this->tokenService->generateToken(
                self::TOKEN_TYPE,
                self::TOKEN_TTL,
                self::TOKEN_IDENTIFIER
            ),
            $this->tokenService->generateToken(
                self::TOKEN_TYPE,
                self::TOKEN_TTL,
                self::TOKEN_IDENTIFIER
            ),
        ];

        $differentToken = $this->tokenService->generateToken(
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            'different'
        );

        $tokenWithoutIdentifier = $this->tokenService->generateToken(
            self::TOKEN_TYPE,
            self::TOKEN_TTL
        );

        $this->tokenService->revokeTokenByIdentifier(self::TOKEN_TYPE, self::TOKEN_IDENTIFIER);

        foreach ($tokens as $token) {
            $this->assertRevokedToken($token);
        }

        self::assertFalse(
            $this->tokenService->getToken(
                $differentToken->getType(),
                $differentToken->getToken(),
                $differentToken->getIdentifier()
            )->isRevoked()
        );

        self::assertFalse(
            $this->tokenService->getToken(
                $tokenWithoutIdentifier->getType(),
                $tokenWithoutIdentifier->getToken(),
                $tokenWithoutIdentifier->getIdentifier()
            )->isRevoked()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testDeleteToken(): void
    {
        $token = $this->tokenService->generateToken(
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER
        );

        $this->tokenService->deleteToken($token);

        self::assertFalse(
            $this->tokenService->checkToken(
                $token->getType(),
                $token->getToken(),
                $token->getIdentifier()
            )
        );
    }

    /**
     * @return iterable<array{
     *     string,
     *     int,
     *     ?string,
     *     ?int
     * }>
     */
    public function provideTokenData(): iterable
    {
        yield 'Token with default length 64 and custom identifier' => [
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER,
            64,
        ];

        yield 'Token with length 200 and custom identifier' => [
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER,
            200,
        ];

        yield 'Token without identifier' => [
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            null,
        ];
    }

    /**
     * @return iterable<array{
     *     string,
     *     int,
     *     ?string
     * }>
     */
    public function provideDataForTestCheckToken(): iterable
    {
        yield 'Token with identifier' => [
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            self::TOKEN_IDENTIFIER,
        ];

        yield 'Token without identifier' => [
            self::TOKEN_TYPE,
            self::TOKEN_TTL,
            null,
        ];
    }

    private function assertRevokedToken(Token $token): void
    {
        $revokedToken = $this->tokenService->getToken(
            $token->getType(),
            $token->getToken(),
            $token->getIdentifier()
        );

        self::assertSame($token->getType(), $revokedToken->getType());
        self::assertSame($token->getToken(), $revokedToken->getToken());
        self::assertSame($token->getIdentifier(), $revokedToken->getIdentifier());
        self::assertTrue($revokedToken->isRevoked());

        self::assertFalse(
            $this->tokenService->checkToken(
                $token->getType(),
                $token->getToken(),
                $token->getIdentifier()
            )
        );
    }
}
