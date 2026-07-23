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
use Ibexa\Core\Persistence\Doctrine\DatabasePlatform;
use PHPUnit\Framework\TestCase;

final class DatabasePlatformTest extends TestCase
{
    /**
     * @return iterable<string, array{\Doctrine\DBAL\Platforms\AbstractPlatform, string}>
     */
    public function provideDataForTestResolveName(): iterable
    {
        yield 'mysql' => [new MySQLPlatform(), 'mysql'];
        yield 'mariadb' => [new MariaDBPlatform(), 'mysql'];
        yield 'postgresql' => [new PostgreSQLPlatform(), 'postgresql'];
        yield 'sqlite' => [new SqlitePlatform(), 'sqlite'];
    }

    /**
     * @dataProvider provideDataForTestResolveName
     */
    public function testResolveName(AbstractPlatform $platform, string $expectedName): void
    {
        self::assertSame($expectedName, DatabasePlatform::resolveName($platform));
    }

    public function testResolveNameThrowsForUnsupportedPlatform(): void
    {
        $this->expectException(InvalidArgumentException::class);

        DatabasePlatform::resolveName(new OraclePlatform());
    }
}
