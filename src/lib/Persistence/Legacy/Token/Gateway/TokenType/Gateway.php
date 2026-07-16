<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Token\Gateway\TokenType;

use Doctrine\DBAL\Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;

interface Gateway
{
    /**
     * @throws Exception
     */
    public function insert(string $identifier): int;

    public function deleteById(int $typeId): void;

    public function deleteByIdentifier(string $identifier): void;

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws NotFoundException
     */
    public function getTypeById(int $typeId): array;

    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws NotFoundException
     */
    public function getTypeByIdentifier(string $identifier): array;
}
