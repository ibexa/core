<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Doctrine\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\SqlWalker;
use Ibexa\Core\Persistence\Doctrine\Query\JsonTextFunction;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;

/**
 * @covers \Ibexa\Core\Persistence\Doctrine\Query\JsonTextFunction
 */
final class JsonTextFunctionTest extends TestCase
{
    /**
     * @dataProvider provideForGetSql
     */
    public function testItReadsTheKeyAsTextOn(
        AbstractPlatform $platform,
        string $expectedSql
    ): void {
        self::assertSame($expectedSql, $this->buildFunction()->getSql($this->createSqlWalker($platform)));
    }

    /**
     * @return iterable<string, array{AbstractPlatform, string}>
     */
    public static function provideForGetSql(): iterable
    {
        yield 'PostgreSQL' => [
            new PostgreSQLPlatform(),
            't0_.names ->> ?',
        ];

        yield 'MySQL' => [
            new MySQLPlatform(),
            'JSON_UNQUOTE(JSON_EXTRACT(t0_.names, CONCAT(\'$."\', ?, \'"\')))',
        ];

        // DBAL 4 no longer has MariaDBPlatform extend MySQLPlatform, so it has to match on its own.
        yield 'MariaDB' => [
            new MariaDBPlatform(),
            'JSON_UNQUOTE(JSON_EXTRACT(t0_.names, CONCAT(\'$."\', ?, \'"\')))',
        ];

        yield 'SQLite' => [
            new SQLitePlatform(),
            'json_extract(t0_.names, \'$."\' || ? || \'"\')',
        ];
    }

    public function testAnUnsupportedPlatformIsReported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('IBEXA_JSON_TEXT() has no implementation for');

        $this->buildFunction()->getSql($this->createSqlWalker(new DB2Platform()));
    }

    private function buildFunction(): JsonTextFunction
    {
        $function = new JsonTextFunction(JsonTextFunction::NAME);

        foreach (['document', 'key'] as $property) {
            (new ReflectionProperty(JsonTextFunction::class, $property))
                ->setValue($function, $this->createMock(Node::class));
        }

        return $function;
    }

    private function createSqlWalker(AbstractPlatform $platform): SqlWalker
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('getDatabasePlatform')->willReturn($platform);

        $sqlWalker = $this->createMock(SqlWalker::class);
        $sqlWalker->method('getConnection')->willReturn($connection);
        $sqlWalker->method('walkStringPrimary')->willReturnOnConsecutiveCalls('t0_.names', '?');

        return $sqlWalker;
    }
}
