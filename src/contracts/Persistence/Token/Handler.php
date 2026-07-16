<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Token;

use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;

/**
 * @internal
 */
interface Handler
{
    /**
     * @throws NotFoundException
     * @throws UnauthorizedException
     */
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token;

    public function getTokenType(
        string $identifier
    ): TokenType;

    public function createToken(CreateStruct $createStruct): Token;

    public function revokeTokenById(int $tokenId): void;

    public function revokeTokenByIdentifier(
        string $tokenType,
        ?string $identifier
    ): void;

    public function deleteToken(Token $token): void;

    public function deleteTokenById(int $tokenId): void;

    public function deleteExpiredTokens(?string $tokenType = null): void;
}
