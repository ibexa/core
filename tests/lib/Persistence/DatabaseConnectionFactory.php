<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Ibexa\DoctrineSchema\Database\DbPlatform\DbPlatformInterface;

/**
 * Database connection factory for integration tests.
 */
class DatabaseConnectionFactory
{
    /**
     * Associative array of <code>[driver => AbstractPlatform]</code>.
     *
     * @var array
     */
    private $databasePlatforms = [];

    /** @var EventManager */
    private $eventManager;

    /**
     * Connection Pool for re-using already created connection.
     *
     * An associative array mapping database URL to Connection object.
     *
     * @var Connection[]
     */
    private static $connectionPool;

    /**
     * @param DbPlatformInterface[] $databasePlatforms
     * @param EventManager $eventManager
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
     * @param string $databaseURL
     *
     * @return Connection
     *
     * @throws DBALException if connection failed
     */
    public function createConnection(string $databaseURL): Connection
    {
        if (isset(self::$connectionPool[$databaseURL])) {
            return self::$connectionPool[$databaseURL];
        }

        $params = ['url' => $databaseURL];

        // set DbPlatform based on database url scheme
        $scheme = parse_url($databaseURL, PHP_URL_SCHEME);
        $driverName = 'pdo_' . $scheme;
        if (isset($this->databasePlatforms[$driverName])) {
            $params['platform'] = $this->databasePlatforms[$driverName];
            // add predefined event subscribers only for the relevant connection
            $params['platform']->addEventSubscribers($this->eventManager);
        }

        self::$connectionPool[$databaseURL] = DriverManager::getConnection(
            $params,
            null,
            $this->eventManager
        );

        return self::$connectionPool[$databaseURL];
    }
}

class_alias(DatabaseConnectionFactory::class, 'eZ\Publish\Core\Persistence\Tests\DatabaseConnectionFactory');
