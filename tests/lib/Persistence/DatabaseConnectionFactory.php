<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;

/**
 * Database connection factory for integration tests.
 *
 * @phpstan-type TIbexaDatabasePlatform \Ibexa\DoctrineSchema\Database\DbPlatform\DbPlatformInterface & \Doctrine\DBAL\Platforms\AbstractPlatform
 */
class DatabaseConnectionFactory
{
    private const array DSN_SCHEME_MAP = [
        'sqlite' => 'pdo_sqlite',
        'sqlite3' => 'sqlite3',
        'mysql' => 'pdo_mysql',
        'mysql2' => 'pdo_mysql',
        'postgres' => 'pdo_pgsql',
        'postgresql' => 'pdo_pgsql',
        'pgsql' => 'pdo_pgsql',
    ];

    /**
     * Associative array of <code>[driver => AbstractPlatform]</code>.
     *
     * @phpstan-var array<string, TIbexaDatabasePlatform>
     */
    private array $databasePlatforms;

    /**
     * Connection Pool for re-using an already created connection.
     *
     * An associative array mapping database URL to a Connection object.
     *
     * @var \Doctrine\DBAL\Connection[]
     */
    private static ?array $connectionPool = null;

    private static function normalizeDsn(string $databaseURL): string
    {
        if (str_starts_with($databaseURL, 'sqlite://:memory:')) {
            return 'sqlite:///:memory:';
        }

        return $databaseURL;
    }

    /**
     * @phpstan-param array<TIbexaDatabasePlatform> $databasePlatforms
     */
    public function __construct(iterable $databasePlatforms)
    {
        $this->databasePlatforms = [];
        foreach ($databasePlatforms as $databasePlatform) {
            $this->databasePlatforms[$databasePlatform->getDriverName()] = $databasePlatform;
        }
    }

    /**
     * Connect to a database described by URL (a.k.a. DSN).
     *
     * @throws \Doctrine\DBAL\Exception if connection failed
     */
    public function createConnection(string $databaseURL): Connection
    {
        if (isset(self::$connectionPool[$databaseURL])) {
            return self::$connectionPool[$databaseURL];
        }

        $params = (new DsnParser(self::DSN_SCHEME_MAP))->parse(self::normalizeDsn($databaseURL));

        // set DbPlatform based on a database url scheme
        $scheme = parse_url($databaseURL, PHP_URL_SCHEME);
        $driverName = 'pdo_' . $scheme;
        $config = new Configuration();
        if (isset($this->databasePlatforms[$driverName])) {
            $this->databasePlatforms[$driverName]->configure($config);
        }

        self::$connectionPool[$databaseURL] = DriverManager::getConnection($params, $config);
        self::$connectionPool[$databaseURL]->setNestTransactionsWithSavepoints(true);

        return self::$connectionPool[$databaseURL];
    }
}
