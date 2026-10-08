<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\SharedGateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;

/**
 * Builds Shared Gateway object based on the database connection.
 *
 * @internal For internal use by Legacy Storage Gateways.
 */
final class GatewayFactory
{
    /** @var Gateway */
    private $fallbackGateway;

    /** @var iterable|Gateway[] */
    private $gateways;

    public function __construct(
        Gateway $fallbackGateway,
        iterable $gateways
    ) {
        $this->fallbackGateway = $fallbackGateway;
        $this->gateways = $gateways;
    }

    /**
     * @throws DBALException
     */
    public function buildSharedGateway(Connection $connection): Gateway
    {
        return $this->getGatewayForDatabasePlatform($connection->getDatabasePlatform()->getName());
    }

    private function getGatewayForDatabasePlatform(string $currentDatabasePlatformName): Gateway
    {
        foreach ($this->gateways as $databasePlatformName => $gateway) {
            if ($currentDatabasePlatformName === $databasePlatformName) {
                return $gateway;
            }
        }

        return $this->fallbackGateway;
    }
}

class_alias(GatewayFactory::class, 'eZ\Publish\Core\Persistence\Legacy\SharedGateway\GatewayFactory');
