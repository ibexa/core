<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\Token;

use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

interface Gateway
{
    /**
     * @throws Exception
     */
    public function insert(
        int $typeId,
        string $token,
        ?string $identifier,
        int $ttl
    ): int;

    public function revoke(int $tokenId): void;

    public function revokeByIdentifier(
        int $typeId,
        ?string $identifier
    ): void;

    /**
     * @throws Exception
     */
    public function delete(int $tokenId): void;

    /**
     * @throws Exception
     */
    public function deleteExpired(?int $typeId = null): void;

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws NotFoundException
     */
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): array;

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws NotFoundException
     */
    public function getTokenById(int $tokenId): array;
}
