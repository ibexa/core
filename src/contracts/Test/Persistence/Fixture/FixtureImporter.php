<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Persistence\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Column;
use Ibexa\Contracts\Core\Test\Persistence\Fixture;

/**
 * Database fixture importer.
 *
 * @internal for internal use by Repository test setup
 */
final class FixtureImporter
{
    private Connection $connection;

    /** @var array<string, string|null> */
    private static array $resetSequenceStatements = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function import(Fixture $fixture): void
    {
        $data = $fixture->load();

        $tablesList = array_keys($data);

        $this->withoutForeignKeyChecks(function () use ($data, $tablesList): void {
            // truncate all tables, even the ones initially empty (some tests are affected by this)
            $this->truncateTables(array_reverse($tablesList));

            $nonEmptyTablesData = array_filter(
                $data,
                static function ($tableData): bool {
                    return !empty($tableData);
                }
            );
            foreach ($nonEmptyTablesData as $table => $rows) {
                foreach ($rows as $row) {
                    $this->connection->insert($table, $row);
                }
            }
        });

        if ($this->connection->getDatabasePlatform()->supportsSequences()) {
            $this->resetSequences($tablesList);
        }
    }

    /**
     * Run $work with referential integrity checks suspended for this session.
     *
     * A fixture is a bulk load, not an ordered sequence of valid states: it truncates every table it
     * mentions and re-inserts rows in the order they happen to appear, and a row may legitimately
     * reference data that another fixture - or a later bootstrap step, such as migrations - supplies.
     * SQLite ignores foreign keys unless explicitly asked not to, so this only ever mattered on
     * MySQL/MariaDB and PostgreSQL, where the same fixtures fail mid-import.
     *
     * Best effort: if the session is not allowed to toggle the setting (PostgreSQL requires elevated
     * privileges for this), the work still runs, just with the checks left on.
     */
    private function withoutForeignKeyChecks(callable $work): void
    {
        $platform = $this->connection->getDatabasePlatform();

        if ($platform instanceof AbstractMySQLPlatform) {
            $disable = 'SET FOREIGN_KEY_CHECKS = 0';
            $restore = 'SET FOREIGN_KEY_CHECKS = 1';
        } elseif ($platform instanceof PostgreSQLPlatform) {
            // constraints here are not DEFERRABLE, so SET CONSTRAINTS would be a no-op; suppressing
            // the triggers that implement them is the only session-level lever
            $disable = "SET session_replication_role = 'replica'";
            $restore = "SET session_replication_role = 'origin'";
        } elseif ($platform instanceof SQLitePlatform) {
            $disable = 'PRAGMA foreign_keys = OFF';
            $restore = 'PRAGMA foreign_keys = ON';
        } else {
            $work();

            return;
        }

        try {
            $this->connection->executeStatement($disable);
        } catch (DBALException) {
            $work();

            return;
        }

        try {
            $work();
        } finally {
            $this->connection->executeStatement($restore);
        }
    }

    /**
     * @param string[] $tables a list of table names
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function truncateTables(array $tables): void
    {
        $dbPlatform = $this->connection->getDatabasePlatform();

        foreach ($tables as $table) {
            try {
                // Cleanup before inserting (using TRUNCATE for speed, however not possible to rollback)
                $this->connection->executeStatement(
                    $dbPlatform->getTruncateTableSql($this->connection->quoteIdentifier($table))
                );
            } catch (DBALException) {
                // Fallback to DELETE if TRUNCATE failed (because of FKs for instance)
                $this->connection->createQueryBuilder()->delete($table)->executeStatement();
            }
        }
    }

    /**
     * Reset database sequences, if needed.
     *
     * @param string[] $affectedTables
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function resetSequences(array $affectedTables): void
    {
        foreach ($this->getSequenceResetStatements($affectedTables) as $statement) {
            $this->connection->executeStatement($statement);
        }
    }

    /**
     * Obtain SQL statements for resetting sequences associated with affected tables.
     *
     * Note: current implementation is probably not the best way to do it.
     *       It should be DBMS-specific, but let's avoid over-engineering it (more) until needed.
     *
     * @param string[] $affectedTables the list of tables which need their sequences reset
     *
     * @return iterable<string, string> list of SQL statements
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function getSequenceResetStatements(array $affectedTables): iterable
    {
        // note: prepared statements don't work for table names
        $queryTemplate = 'SELECT setval(\'%s\', %s) FROM %s';

        $unvisitedTables = array_diff($affectedTables, array_keys(self::$resetSequenceStatements));
        $schemaManager = $this->connection->createSchemaManager();

        foreach ($unvisitedTables as $tableName) {
            $columns = $schemaManager->listTableColumns($tableName);
            $column = $this->findAutoincrementColumn($columns);

            if ($column === null) {
                self::$resetSequenceStatements[$tableName] = null;

                continue;
            }

            $columnName = $column->getName();
            $sequenceName = "{$tableName}_{$columnName}_seq";

            self::$resetSequenceStatements[$tableName] = sprintf(
                $queryTemplate,
                $sequenceName,
                sprintf('MAX(%s)', $this->connection->quoteIdentifier($columnName)),
                $this->connection->quoteIdentifier($tableName)
            );
        }

        // Return sequence change commands for affected tables
        $result = array_intersect_key(self::$resetSequenceStatements, array_fill_keys($affectedTables, true));

        return array_filter($result);
    }

    /**
     * @param array<\Doctrine\DBAL\Schema\Column> $columns
     */
    private function findAutoincrementColumn(array $columns): ?Column
    {
        foreach ($columns as $column) {
            if ($column->getAutoincrement()) {
                return $column;
            }
        }

        return null;
    }
}
