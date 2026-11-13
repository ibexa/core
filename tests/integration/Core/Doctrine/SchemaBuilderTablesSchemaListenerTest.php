<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Ibexa\Bundle\Core\Doctrine\SchemaBuilderTablesSchemaListener;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Contracts\DoctrineSchema\Builder\SchemaBuilderInterface;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies in a fully booted kernel (real compiler pass ordering, real listener registration)
 * that the legacy schema is part of the schema Doctrine ORM compares the database against.
 */
#[CoversClass(SchemaBuilderTablesSchemaListener::class)]
final class SchemaBuilderTablesSchemaListenerTest extends IbexaKernelTestCase
{
    private const MIGRATIONS_TABLE = 'doctrine_migration_versions';

    protected static function getKernelClass(): string
    {
        return SchemaBuilderListenerTestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        self::loadSchema();
    }

    public function testOrmSchemaUpdateDoesNotDropLegacyTables(): void
    {
        $sql = $this->getUpdateSchemaSql();

        // SQLite alters a table by copying it to a __temp__ table, dropping and re-creating it:
        // that is not a lost table.
        // Doctrine Migrations' own metadata table is in no schema and is no ORM entity. In real
        // doctrine:schema:update / doctrine:schema:validate runs DoctrineMigrationsBundle's
        // SchemaFilterListener hides it, but SchemaTool is used directly here, outside a console
        // command, so that listener is not active.
        $dropped = $created = [];
        foreach ($sql as $statement) {
            if (preg_match('/^DROP TABLE (\S+)/', $statement, $matches) === 1) {
                if (!str_starts_with($matches[1], '__temp__') && $matches[1] !== self::MIGRATIONS_TABLE) {
                    $dropped[] = $matches[1];
                }
            } elseif (preg_match('/^CREATE TABLE (\S+)/', $statement, $matches) === 1) {
                $created[] = $matches[1];
            }
        }

        self::assertSame([], array_values(array_diff($dropped, $created)), implode("\n", $sql));
    }

    public function testOrmSchemaUpdateStillCreatesOrmEntityTables(): void
    {
        $sql = implode("\n", $this->getUpdateSchemaSql());

        self::assertStringContainsString('CREATE TABLE ' . SchemaBuilderListenerTestKernel::MANAGED_TABLE, $sql);
    }

    public function testTableWithoutOrmEntityIsVisibleToSchemaManager(): void
    {
        $connection = self::getServiceByClassName(Connection::class);
        $connection->executeStatement('CREATE TABLE ibexa_test_unmanaged_table (id INTEGER NOT NULL)');

        // This test runs on its own kernel, so DAMA does not roll the table back: drop it explicitly
        // or it leaks into the shared database and SchemaAlignmentTest.
        try {
            self::assertTrue($connection->createSchemaManager()->tablesExist(['ibexa_test_unmanaged_table']));
            self::assertTrue($connection->createSchemaManager()->tablesExist(['ibexa_content']));
        } finally {
            $connection->executeStatement('DROP TABLE ibexa_test_unmanaged_table');
        }
    }

    /**
     * The path ibexa:install takes: OrmEntitiesSchemaSubscriber runs SchemaTool inside buildSchema(),
     * which fires postGenerateSchema, which calls buildSchema() again.
     */
    public function testBuildSchemaWithOrmEntitiesSubscriberReturnsLegacyAndEntityTables(): void
    {
        $schema = self::getServiceByClassName(SchemaBuilderInterface::class)->buildSchema();

        self::assertTrue($schema->hasTable('ibexa_content'));
        self::assertTrue($schema->hasTable(SchemaBuilderListenerTestKernel::MANAGED_TABLE));
    }

    /**
     * @return list<string>
     */
    private function getUpdateSchemaSql(): array
    {
        $entityManager = self::getServiceByClassName(
            EntityManagerInterface::class,
            SchemaBuilderListenerTestKernel::ENTITY_MANAGER_SERVICE_ID
        );

        return (new SchemaTool($entityManager))->getUpdateSchemaSql(
            $entityManager->getMetadataFactory()->getAllMetadata()
        );
    }
}
