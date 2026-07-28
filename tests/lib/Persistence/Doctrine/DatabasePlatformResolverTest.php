<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Doctrine\DatabasePlatformName;
use Ibexa\Core\Persistence\Doctrine\DatabasePlatformResolver;
use PHPUnit\Framework\TestCase;

final class DatabasePlatformResolverTest extends TestCase
{
    /**
     * @return iterable<string, array{\Doctrine\DBAL\Platforms\AbstractPlatform, \Ibexa\Core\Persistence\Doctrine\DatabasePlatformName}>
     */
    public function provideDataForTestResolveName(): iterable
    {
        yield 'mysql' => [new MySQLPlatform(), DatabasePlatformName::Mysql];
        yield 'mariadb' => [new MariaDBPlatform(), DatabasePlatformName::Mysql];
        yield 'postgresql' => [new PostgreSQLPlatform(), DatabasePlatformName::Postgresql];
        yield 'sqlite' => [new SqlitePlatform(), DatabasePlatformName::Sqlite];
    }

    /**
     * @dataProvider provideDataForTestResolveName
     */
    public function testResolveName(AbstractPlatform $platform, DatabasePlatformName $expected): void
    {
        self::assertSame($expected, DatabasePlatformResolver::resolveName($platform));
    }

    public function testResolveNameThrowsForUnsupportedPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabasePlatformResolver::resolveName(new OraclePlatform());
    }
}
