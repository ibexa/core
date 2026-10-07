<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom;

class RandomSortClauseHandlerFactory
{
    /** @var iterable|AbstractRandom[] */
    private $randomSortClauseGateways = [];

    /** @var Connection */
    private $connection;

    public function __construct(
        Connection $connection,
        iterable $randomSortClauseGateways
    ) {
        $this->connection = $connection;
        $this->randomSortClauseGateways = $randomSortClauseGateways;
    }

    /**
     * @throws DBALException
     * @throws InvalidArgumentException
     */
    public function getGateway(): AbstractRandom
    {
        $driverName = $this->connection->getDatabasePlatform()->getName();

        foreach ($this->randomSortClauseGateways as $gateway) {
            if ($gateway->getDriverName() === $driverName) {
                return $gateway;
            }
        }

        throw new InvalidArgumentException('$this->randomSortClauseGateways', 'No RandomSortClauseHandler found for driver ' . $driverName);
    }
}

class_alias(RandomSortClauseHandlerFactory::class, 'eZ\Publish\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Factory\RandomSortClauseHandlerFactory');
