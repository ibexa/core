<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\Configuration;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\Migrations\Configuration\Migration\ExistingConfiguration;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration;
use Ibexa\Bundle\RepositoryInstaller\Migration\Exception\MigrationFailedException;
use Ibexa\Bundle\RepositoryInstaller\Migration\TaggedMigrationsRunner;
use Ibexa\DoctrineSchema\Filter\SchemaAssetsFilterBypass;
use Ibexa\Tests\Bundle\RepositoryInstaller\Migration\Fixtures\Migration1CreateTable;
use Ibexa\Tests\Bundle\RepositoryInstaller\Migration\Fixtures\Migration2InsertRow;
use Ibexa\Tests\Bundle\RepositoryInstaller\Migration\Fixtures\Migration3Failing;
use Ibexa\Tests\Bundle\RepositoryInstaller\Migration\Fixtures\Migration4RequiresVisibleTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Runs real migrations through a real DependencyFactory on in-memory SQLite, which has
 * transactional DDL, so a failed migration's rollback is observable.
 */
#[CoversClass(TaggedMigrationsRunner::class)]
final class TaggedMigrationsRunnerTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
    }

    public function testRunsPendingMigrationsAndReturnsTheirQueries(): void
    {
        $dependencyFactory = $this->createDependencyFactory(Migration1CreateTable::class, Migration2InsertRow::class);

        $queries = (new TaggedMigrationsRunner($dependencyFactory, new SchemaAssetsFilterBypass()))->run();

        self::assertSame(
            ['CREATE TABLE runner_test (id INTEGER NOT NULL)', 'INSERT INTO runner_test (id) VALUES (1)'],
            array_map(static fn ($query): string => $query->getStatement(), $queries)
        );
        self::assertEquals(1, $this->connection->fetchOne('SELECT COUNT(*) FROM runner_test'));
        self::assertSame(
            [Migration1CreateTable::class, Migration2InsertRow::class],
            $this->getExecutedVersions($dependencyFactory)
        );
        self::assertSame(0, $this->connection->getTransactionNestingLevel());
    }

    public function testSkipsMigrationsExecutedInAnEarlierRun(): void
    {
        $migrationClasses = [Migration1CreateTable::class, Migration2InsertRow::class];
        (new TaggedMigrationsRunner($this->createDependencyFactory(...$migrationClasses), new SchemaAssetsFilterBypass()))->run();

        $queries = (new TaggedMigrationsRunner($this->createDependencyFactory(...$migrationClasses), new SchemaAssetsFilterBypass()))->run();

        self::assertSame([], $queries);
        self::assertEquals(1, $this->connection->fetchOne('SELECT COUNT(*) FROM runner_test'));
    }

    public function testFailureNamesTheMigrationAndRollsItBack(): void
    {
        $dependencyFactory = $this->createDependencyFactory(Migration1CreateTable::class, Migration3Failing::class);

        try {
            (new TaggedMigrationsRunner($dependencyFactory, new SchemaAssetsFilterBypass()))->run();
            self::fail('Expected the failing migration to throw.');
        } catch (MigrationFailedException $e) {
            self::assertSame(Migration3Failing::class, $e->getVersion());
            self::assertStringStartsWith(
                sprintf(
                    'Migration "%s" failed while executing "INSERT INTO runner_test_missing (id) VALUES (1)": ',
                    Migration3Failing::class
                ),
                $e->getMessage()
            );
            self::assertNotNull($e->getPrevious());
        }

        // The migration before it stays applied and recorded; the failing one leaves nothing behind.
        self::assertSame([Migration1CreateTable::class], $this->getExecutedVersions($dependencyFactory));
        self::assertTrue($this->hasTable('runner_test'));
        self::assertFalse($this->hasTable('runner_test_partial'));
        self::assertSame(0, $this->connection->getTransactionNestingLevel());
    }

    public function testLiftsTheSchemaAssetsFilterWhileMigrating(): void
    {
        // Like 6.0's managed-tables filter, which hides every table without an ORM entity behind it.
        $hideRunnerTest = static fn (string $assetName): bool => $assetName !== 'runner_test';
        $this->connection->getConfiguration()->setSchemaAssetsFilter($hideRunnerTest);
        $dependencyFactory = $this->createDependencyFactory(
            Migration1CreateTable::class,
            Migration4RequiresVisibleTable::class
        );

        (new TaggedMigrationsRunner($dependencyFactory, new SchemaAssetsFilterBypass()))->run();

        self::assertSame(
            [Migration1CreateTable::class, Migration4RequiresVisibleTable::class],
            $this->getExecutedVersions($dependencyFactory)
        );
        self::assertSame($hideRunnerTest, $this->connection->getConfiguration()->getSchemaAssetsFilter());
    }

    /**
     * @param class-string ...$migrationClasses
     */
    private function createDependencyFactory(string ...$migrationClasses): DependencyFactory
    {
        $configuration = new Configuration();
        $configuration->setMetadataStorageConfiguration(new TableMetadataStorageConfiguration());
        foreach ($migrationClasses as $migrationClass) {
            $configuration->addMigrationClass($migrationClass);
        }

        return DependencyFactory::fromConnection(
            new ExistingConfiguration($configuration),
            new ExistingConnection($this->connection)
        );
    }

    /**
     * @return list<string>
     */
    private function getExecutedVersions(DependencyFactory $dependencyFactory): array
    {
        return array_values(array_map(
            static fn ($migration): string => (string)$migration->getVersion(),
            $dependencyFactory->getMetadataStorage()->getExecutedMigrations()->getItems()
        ));
    }

    private function hasTable(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }
}
