<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDB1010Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQL80Platform;
use Doctrine\DBAL\Platforms\MySQL84Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\MySqlRandom;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\PgSqlRandom;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\SqlLiteRandom;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\MySqlRandom
 * @covers \Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\PgSqlRandom
 * @covers \Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\Random\SqlLiteRandom
 */
final class RandomSortClauseHandlerPlatformSupportTest extends TestCase
{
    /**
     * @dataProvider providePlatformSupport
     *
     * @phpstan-param class-string<\Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom> $handlerClass
     */
    public function testSupportsPlatform(
        string $handlerClass,
        AbstractPlatform $platform,
        bool $expectedSupport
    ): void {
        self::assertSame(
            $expectedSupport,
            $this->createHandler($handlerClass, $platform)->supportsPlatform($platform)
        );
    }

    /**
     * @return iterable<string, array{class-string<\Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom>, AbstractPlatform, bool}>
     */
    public function providePlatformSupport(): iterable
    {
        yield 'MySQL' => [MySqlRandom::class, new MySQLPlatform(), true];
        yield 'MySQL 8.0' => [MySqlRandom::class, new MySQL80Platform(), true];
        yield 'MySQL 8.4' => [MySqlRandom::class, new MySQL84Platform(), true];
        yield 'MariaDB' => [MySqlRandom::class, new MariaDBPlatform(), true];
        yield 'MariaDB 10.10' => [MySqlRandom::class, new MariaDB1010Platform(), true];
        yield 'MySQL handler rejects PostgreSQL' => [MySqlRandom::class, new PostgreSQLPlatform(), false];
        yield 'MySQL handler rejects SQLite' => [MySqlRandom::class, new SQLitePlatform(), false];

        yield 'PostgreSQL' => [PgSqlRandom::class, new PostgreSQLPlatform(), true];
        yield 'PostgreSQL handler rejects MariaDB' => [PgSqlRandom::class, new MariaDBPlatform(), false];

        yield 'SQLite' => [SqlLiteRandom::class, new SQLitePlatform(), true];
        yield 'SQLite handler rejects MySQL' => [SqlLiteRandom::class, new MySQLPlatform(), false];
    }

    /**
     * @phpstan-param class-string<\Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler\AbstractRandom> $handlerClass
     */
    private function createHandler(string $handlerClass, AbstractPlatform $platform): AbstractRandom
    {
        $connection = $this->createStub(Connection::class);
        $connection
            ->method('getDatabasePlatform')
            ->willReturn($platform);

        return new $handlerClass($connection);
    }
}
