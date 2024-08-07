<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token;

use Ibexa\Contracts\Core\Persistence\Token\Token;
use Ibexa\Contracts\Core\Persistence\Token\TokenType;

/**
 * @internal
 */
final class Mapper
{
    public function mapToken(array $tokenRow): Token
    {
        return new Token([
            'id' => (int)$tokenRow['id'],
            'typeId' => (int)$tokenRow['type_id'],
            'token' => (string)$tokenRow['token'],
            'identifier' => $tokenRow['identifier'] === null ? null : (string)$tokenRow['identifier'],
            'created' => (int)$tokenRow['created'],
            'expires' => (int)$tokenRow['expires'],
            'revoked' => (bool)$tokenRow['revoked'],
        ]);
    }

    public function mapTokenType(array $tokenTypeRow): TokenType
    {
        return new TokenType([
            'id' => (int)$tokenTypeRow['id'],
            'identifier' => (string)$tokenTypeRow['identifier'],
        ]);
    }
}
