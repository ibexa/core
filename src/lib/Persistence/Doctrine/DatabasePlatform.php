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

final readonly class DatabasePlatform
{
    private function __construct()
    {
    }

    /**
     * Resolves a Doctrine platform instance to its short driver name.
     *
     * Replaces the DBAL 3.10-deprecated/DBAL 4-removed `AbstractPlatform::getName()`.
     *
     * @return 'mysql'|'postgresql'|'sqlite'
     */
    public static function resolveName(AbstractPlatform $platform): string
    {
        if ($platform instanceof AbstractMySQLPlatform) {
            return 'mysql';
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return 'postgresql';
        }

        if ($platform instanceof SqlitePlatform) {
            return 'sqlite';
        }

        throw new InvalidArgumentException(
            'platform',
            sprintf('Unsupported database platform: %s', get_class($platform))
        );
    }
}
