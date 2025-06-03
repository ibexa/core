<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Factory;

use Doctrine\DBAL\Connection;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom;

class RandomSortClauseHandlerFactory
{
    /** @var iterable|\Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom[] */
    private $randomSortClauseGateways = [];

    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    public function __construct(Connection $connection, iterable $randomSortClauseGateways)
    {
        $this->connection = $connection;
        $this->randomSortClauseGateways = $randomSortClauseGateways;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    public function getGateway(): AbstractRandom
    {
        $databasePlatform = $this->connection->getDatabasePlatform();

        foreach ($this->randomSortClauseGateways as $gateway) {
            if ($gateway->supportsPlatform($databasePlatform)) {
                return $gateway;
            }
        }

        throw new InvalidArgumentException(
            '$this->randomSortClauseGateways',
            sprintf(
                'No RandomSortClauseHandler found for driver %s',
                get_class($databasePlatform)
            )
        );
    }
}
