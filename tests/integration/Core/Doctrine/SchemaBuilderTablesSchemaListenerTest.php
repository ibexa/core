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
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Verifies in a fully booted kernel (real compiler pass ordering, real listener registration)
 * that the legacy schema is part of the schema Doctrine ORM compares the database against.
 */
#[CoversClass(SchemaBuilderTablesSchemaListener::class)]
final class SchemaBuilderTablesSchemaListenerTest extends IbexaKernelTestCase
{
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
        $dropped = $created = [];
        foreach ($sql as $statement) {
            if (preg_match('/^DROP TABLE (\S+)/', $statement, $matches) === 1) {
                if (!str_starts_with($matches[1], '__temp__')) {
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

        self::assertTrue($connection->createSchemaManager()->tablesExist(['ibexa_test_unmanaged_table']));
        self::assertTrue($connection->createSchemaManager()->tablesExist(['ibexa_content']));
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
