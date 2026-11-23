<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Bundle\RepositoryInstaller\Migration\ImportDataMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\MigrationRunContext;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @covers \Ibexa\Bundle\RepositoryInstaller\Migration\ImportDataMigration
 */
final class ImportDataMigrationTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    }

    public function testImportsDataIntoEmptyContentTable(): void
    {
        $schema = $this->createContentTable();

        self::assertNotEmpty($this->getQueuedSql($schema));
    }

    /**
     * @dataProvider provideExistingContentIds
     *
     * @param non-empty-list<int> $contentIds
     */
    public function testSkipsWhenContentExists(array $contentIds): void
    {
        $schema = $this->createContentTable();
        foreach ($contentIds as $contentId) {
            $this->connection->insert('ezcontentobject', ['id' => $contentId]);
        }

        self::assertSame([], $this->getQueuedSql($schema));
    }

    /**
     * @return iterable<string, array{non-empty-list<int>}>
     */
    public static function provideExistingContentIds(): iterable
    {
        yield 'root content' => [[1, 4, 57]];
        yield 'no content with id 1' => [[57, 58]];
    }

    public function testSkipsWithoutContentTable(): void
    {
        self::assertSame([], $this->getQueuedSql(new Schema()));
    }

    private function createContentTable(): Schema
    {
        $schema = new Schema();
        $schema->createTable('ezcontentobject')->addColumn('id', 'integer');
        foreach ($schema->toSql($this->connection->getDatabasePlatform()) as $statement) {
            $this->connection->executeStatement($statement);
        }

        return $schema;
    }

    /**
     * @return array<string>
     */
    private function getQueuedSql(Schema $schema): array
    {
        $migration = new ImportDataMigration($this->connection, new NullLogger(), new MigrationRunContext());
        $migration->up($schema);

        return array_map(static fn ($query): string => $query->getStatement(), $migration->getSql());
    }
}
