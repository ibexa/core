<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\TokenService;
use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

abstract class TokenServiceDecorator implements TokenService
{
    protected TokenService $innerService;

    public function __construct(
        TokenService $innerService
    ) {
        $this->innerService = $innerService;
    }

    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token {
        return $this->innerService->getToken(
            $tokenType,
            $token,
            $identifier
        );
    }

    public function checkToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): bool {
        return $this->innerService->checkToken(
            $tokenType,
            $token,
            $identifier
        );
    }

    public function generateToken(
        string $type,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ): Token {
        return $this->innerService->generateToken(
            $type,
            $ttl,
            $identifier,
            $tokenLength,
            $tokenGenerator
        );
    }

    public function revokeToken(Token $token): void
    {
        $this->innerService->revokeToken($token);
    }

    public function revokeTokenByIdentifier(
        string $tokenType,
        ?string $identifier
    ): void {
        $this->innerService->revokeTokenByIdentifier($tokenType, $identifier);
    }

    public function deleteToken(Token $token): void
    {
        $this->innerService->deleteToken($token);
    }
}
