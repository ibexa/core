<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\Token;

interface Gateway
{
    /**
     * @throws \Doctrine\DBAL\Exception
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
     * @throws \Doctrine\DBAL\Exception
     */
    public function delete(int $tokenId): void;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteExpired(?int $typeId = null): void;

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getToken(
        string $tokenType,
        string $token,
        ?string $identifier = null
    ): array;

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getTokenById(int $tokenId): array;
}
