<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Doctrine\DBAL\Connection;

class LegacyStorageImageFileRowReader implements ImageFileRowReader
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /** @var \Doctrine\DBAL\ForwardCompatibility\Result */
    private $statement;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function init()
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select('filepath')->from('ezimagefile');
        $this->statement = $selectQuery->executeQuery();
    }

    public function getRow()
    {
        return $this->statement->fetchOne();
    }

    public function getCount()
    {
        return $this->statement->rowCount();
    }
}
