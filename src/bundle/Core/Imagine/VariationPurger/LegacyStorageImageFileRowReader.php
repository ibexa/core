<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

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

    public function init()
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select('filepath')->from(DoctrineStorage::IMAGE_FILE_TABLE);
        $this->result = $selectQuery->executeQuery();
    }

    public function getRow()
    {
        if ($this->result === null) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        return $this->result->fetchOne();
    }

    public function getCount()
    {
        if ($this->result === null) {
            throw new LogicException('Uninitialized reader. You must call init() before getRow()');
        }

        return $this->result->rowCount();
    }
}
