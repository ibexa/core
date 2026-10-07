<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\Imagine\VariationPurger;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;

class LegacyStorageImageFileRowReader implements ImageFileRowReader
{
    /** @var Connection */
    private $connection;

    /** @var Statement */
    private $statement;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function init()
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select('filepath')->from('ezimagefile');
        $this->statement = $selectQuery->execute();
    }

    public function getRow()
    {
        return $this->statement->fetchColumn(0);
    }

    public function getCount()
    {
        return $this->statement->rowCount();
    }
}

class_alias(LegacyStorageImageFileRowReader::class, 'eZ\Bundle\EzPublishCoreBundle\Imagine\VariationPurger\LegacyStorageImageFileRowReader');
