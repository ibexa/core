<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\SharedGateway;

use Doctrine\DBAL\Connection;

/**
 * Builds Shared Gateway object based on the database connection.
 *
 * @internal For internal use by Legacy Storage Gateways.
 */
final class GatewayFactory
{
    /** @var \Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway */
    private $fallbackGateway;

    /** @var \iterable|\Ibexa\Core\Persistence\Legacy\SharedGateway\Gateway[] */
    private $gateways;

    public function __construct(Gateway $fallbackGateway, iterable $gateways)
    {
        $this->fallbackGateway = $fallbackGateway;
        $this->gateways = $gateways;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildSharedGateway(Connection $connection): Gateway
    {
        $platform = $connection->getDatabasePlatform();

        foreach ($this->gateways as $platformClass => $gateway) {
            if ($platform instanceof $platformClass) {
                return $gateway;
            }
        }

        return $this->fallbackGateway;
    }
}
