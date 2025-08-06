<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Ibexa\Core\FieldType\Image\ImageStorage\Gateway\DoctrineStorage;
use LogicException;

class LegacyStorageImageFileRowReader implements ImageFileRowReader
{
    private Connection $connection;

    private ?Result $result;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->result = null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function init(): void
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select('filepath')->from(DoctrineStorage::IMAGE_FILE_TABLE);
        $this->result = $selectQuery->executeQuery();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getRow(): ?string
    {
        if ($this->result === null) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        $filePath = $this->result->fetchOne();

        return $filePath ?: null;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCount(): int
    {
        if ($this->result === null) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        /** @phpstan-var int<0, max> */
        return $this->result->rowCount();
    }
}
