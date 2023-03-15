<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository;

use Ibexa\Contracts\Core\Repository\Values\Token\Token;
use Ibexa\Contracts\Core\Token\TokenGeneratorInterface;

interface TokenService
{
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): Token;

    public function checkToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): bool;

    public function generateToken(
        string $type,
        int $ttl,
        ?string $identifier = null,
        int $tokenLength = 64,
        ?TokenGeneratorInterface $tokenGenerator = null
    ): Token;

    public function deleteToken(Token $token): void;
}
