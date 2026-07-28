<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Doctrine;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

final readonly class DatabasePlatformResolver
{
    private function __construct()
    {
        // intentionally prevent instantiation
    }

    /**
     * Resolves a Doctrine platform instance to its short driver name.
     *
     * Replaces the DBAL 3.10-deprecated/DBAL 4-removed `AbstractPlatform::getName()`.
     */
    public static function resolveName(AbstractPlatform $platform): DatabasePlatformName
    {
        if ($platform instanceof AbstractMySQLPlatform) {
            return DatabasePlatformName::Mysql;
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return DatabasePlatformName::Postgresql;
        }

        if ($platform instanceof SqlitePlatform) {
            return DatabasePlatformName::Sqlite;
        }

        throw new InvalidArgumentException(
            'platform',
            sprintf('Unsupported database platform: %s', get_class($platform))
        );
    }
}
