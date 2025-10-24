<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;

/**
 * Database connection factory for integration tests.
 *
 * @phpstan-type TIbexaDatabasePlatform \Ibexa\DoctrineSchema\Database\DbPlatform\DbPlatformInterface & \Doctrine\DBAL\Platforms\AbstractPlatform
 */
class DatabaseConnectionFactory
{
    /**
     * Associative array of <code>[driver => AbstractPlatform]</code>.
     *
     * @phpstan-var array<string, TIbexaDatabasePlatform>
     */
    private array $databasePlatforms;

    private EventManager $eventManager;

    /**
     * Connection Pool for re-using an already created connection.
     *
     * An associative array mapping database URL to a Connection object.
     *
     * @var Connection[]
     */
    private static ?array $connectionPool = null;

    /**
     * @phpstan-param array<TIbexaDatabasePlatform> $databasePlatforms
     */
    public function __construct(
        iterable $databasePlatforms,
        EventManager $eventManager
    ) {
        $this->databasePlatforms = [];
        foreach ($databasePlatforms as $databasePlatform) {
            $this->databasePlatforms[$databasePlatform->getDriverName()] = $databasePlatform;
        }

        $this->eventManager = $eventManager;
    }

    /**
     * Connect to a database described by URL (a.k.a. DSN).
     *
     * @throws Exception if connection failed
     */
    public function createConnection(string $databaseURL): Connection
    {
        if (isset(self::$connectionPool[$databaseURL])) {
            return self::$connectionPool[$databaseURL];
        }

        $params = ['url' => $databaseURL];

        // set DbPlatform based on a database url scheme
        $scheme = parse_url($databaseURL, PHP_URL_SCHEME);
        $driverName = 'pdo_' . $scheme;
        $config = new Configuration();
        if (isset($this->databasePlatforms[$driverName])) {
            $params['platform'] = $this->databasePlatforms[$driverName];
            // add predefined event subscribers only for the relevant connection
            $params['platform']->addEventSubscribers($this->eventManager);
            $params['platform']->configure($config);
        }

        self::$connectionPool[$databaseURL] = DriverManager::getConnection(
            $params,
            $config,
            $this->eventManager
        );
        self::$connectionPool[$databaseURL]->setNestTransactionsWithSavepoints(true);

        return self::$connectionPool[$databaseURL];
    }
}
