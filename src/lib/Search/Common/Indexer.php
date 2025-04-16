<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Common;

use Doctrine\DBAL\Connection;
use Ibexa\Contracts\Core\Persistence\Handler;
use Ibexa\Contracts\Core\Persistence\Handler as PersistenceHandler;
use Ibexa\Contracts\Core\Search\Handler as SearchHandler;
use Psr\Log\LoggerInterface;

/**
 * Base class for the Search Engine Indexer Service aimed to recreate Search Engine Index.
 * Each Search Engine has to extend it on its own.
 */
abstract class Indexer
{
    protected LoggerInterface $logger;

    protected Handler $persistenceHandler;

    protected Connection $connection;

    protected SearchHandler $searchHandler;

    public function __construct(
        LoggerInterface $logger,
        PersistenceHandler $persistenceHandler,
        Connection $connection,
        SearchHandler $searchHandler
    ) {
        $this->logger = $logger;
        $this->persistenceHandler = $persistenceHandler;
        $this->connection = $connection;
        $this->searchHandler = $searchHandler;
    }
}
