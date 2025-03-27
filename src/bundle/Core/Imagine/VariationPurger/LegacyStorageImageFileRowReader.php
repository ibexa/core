<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;

class LegacyStorageImageFileRowReader implements ImageFileRowReader
{
    private Connection $connection;

    private Result $result;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function init(): void
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select('filepath')->from('ezimagefile');
        $this->result = $selectQuery->executeQuery();
    }

    /**
     * @phpstan-return array<string, scalar>
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRow(): array
    {
        return $this->result->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCount(): int
    {
        return $this->result->rowCount();
    }
}
