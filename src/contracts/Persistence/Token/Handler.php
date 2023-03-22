<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Token;

/**
 * @internal
 */
interface Handler
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
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

    public function deleteToken(Token $token): void;

    public function deleteTokenById(int $tokenId): void;

    public function deleteExpiredTokens(?string $tokenType = null): void;
}
