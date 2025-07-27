<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType;

interface Gateway
{
    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insert(string $identifier): int;

    public function deleteById(int $typeId): void;

    public function deleteByIdentifier(string $identifier): void;

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getTypeById(int $typeId): array;

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function getTypeByIdentifier(string $identifier): array;
}
