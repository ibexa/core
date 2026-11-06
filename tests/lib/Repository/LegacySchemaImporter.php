<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema as DoctrineSchema;
use Doctrine\DBAL\Schema\SchemaConfig;
use Ibexa\Contracts\DoctrineSchema\Exception\InvalidConfigurationException;
use Ibexa\DoctrineSchema\Database\DbPlatform\PostgreSqlDbPlatform;
use Ibexa\DoctrineSchema\Database\DbPlatform\SqliteDbPlatform;
use Ibexa\DoctrineSchema\Database\DbPlatformFactory;
use Ibexa\DoctrineSchema\Importer\SchemaImporter;
use RuntimeException;

/**
 * Legacy database Schema Importer for database integration tests.
 *
 * @uses \Ibexa\DoctrineSchema\Importer\SchemaImporter
 *
 * @internal For internal use by the Repository test cases.
 */
final class LegacySchemaImporter
{
    /** @var Connection */
    private $connection;

    /** @var array<string, mixed> */
    private array $defaultTableOptions;

    /**
     * @param array<string, mixed> $defaultTableOptions
     */
    public function __construct(
        Connection $connection,
        array $defaultTableOptions
    ) {
        $this->connection = $connection;
        $this->defaultTableOptions = $defaultTableOptions;
    }

    /**
     * Import database schema from Doctrine Schema Yaml configuration file.
     *
     * @param string $schemaFilePath Yaml schema configuration file path
     */
    public function importSchema(string $schemaFilePath): void
    {
        if (!file_exists($schemaFilePath)) {
            throw new RuntimeException("The schema file path {$schemaFilePath} does not exist");
        }

        $importer = new SchemaImporter();
        try {
            $databasePlatform = $this->getIbexaDatabasePlatform();
            // Tests have to generate the same DDL an installation does, so the schema carries
            // the same default table options SchemaBuilder applies in production.
            $schemaConfig = new SchemaConfig();
            $schemaConfig->setDefaultTableOptions($this->defaultTableOptions);
            $schema = $importer->importFromFile(
                $schemaFilePath,
                new DoctrineSchema([], [], $schemaConfig)
            );
            $statements = array_merge(
                $this->getDropSqlStatementsForExistingSchema(
                    $schema,
                    $databasePlatform,
                    $this->connection
                ),
                // generate schema DDL queries
                $schema->toSql($databasePlatform)
            );

            foreach ($statements as $statement) {
                $this->connection->executeStatement($statement);
            }
        } catch (InvalidConfigurationException $e) {
            throw new RuntimeException($e->getMessage(), 1, $e);
        }
    }

    /**
     * @return string[]
     */
    private function getDropSqlStatementsForExistingSchema(
        DoctrineSchema $newSchema,
        AbstractPlatform $databasePlatform,
        Connection $connection
    ): array {
        // This test bootstrap re-imports schema against a database that may
        // already have it from a previous test class.
        $existingSchema = $connection->createSchemaManager()->introspectSchema();

        $statements = [];
        // reverse table order for clean-up (due to FKs)
        $tables = array_reverse($newSchema->getTables());
        // cleanup pre-existing database
        foreach ($tables as $table) {
            if ($existingSchema->hasTable($table->getName())) {
                $statements[] = $databasePlatform->getDropTableSQL($table->getName());
            }
        }

        return $statements;
    }

    private function getIbexaDatabasePlatform(): AbstractPlatform
    {
        $driverName = $this->connection->getParams()['driver'] ?? '';

        return (new DbPlatformFactory([new SqliteDbPlatform(), new PostgreSqlDbPlatform()]))
            ->createDatabasePlatformFromDriverName($driverName)
            ?? $this->connection->getDatabasePlatform();
    }
}
