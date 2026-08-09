<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Persistence\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
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

    /** @var array<string, string[]> */
    private static array $existingColumnsByTable = [];

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
        // truncate all tables, even the ones initially empty (some tests are affected by this)
        $this->truncateTables(array_reverse($tablesList));

        $nonEmptyTablesData = array_filter(
            $data,
            static function ($tableData): bool {
                return !empty($tableData);
            }
        );
        foreach ($nonEmptyTablesData as $table => $rows) {
            // Fixtures predate columns being dropped over time (e.g. the language bitmask
            // columns) - silently drop unknown keys rather than letting every fixture file need
            // updating in lockstep with schema changes.
            $existingColumns = $this->getExistingColumns($table);
            foreach ($rows as $row) {
                $this->connection->insert($table, array_intersect_key($row, array_flip($existingColumns)));
            }
        }

        if ($this->connection->getDatabasePlatform()->supportsSequences()) {
            $this->resetSequences($tablesList);
        }

        $this->backfillLanguageBitmaskColumns($nonEmptyTablesData);
    }

    /**
     * @return string[]
     */
    private function getExistingColumns(string $table): array
    {
        if (!isset(self::$existingColumnsByTable[$table])) {
            $columns = $this->connection->createSchemaManager()->listTableColumns($table);
            self::$existingColumnsByTable[$table] = array_map(
                static fn (Column $column): string => $column->getName(),
                $columns
            );
        }

        return self::$existingColumnsByTable[$table];
    }

    /**
     * Fixture data predates "always_available"/"is_always_available" becoming plain columns and
     * the "ibexa_content_translation"/"ibexa_content_version_translation"/
     * "ibexa_url_alias_ml_translation" join tables - it only ever set "language_mask"/"lang_mask",
     * which import() now silently drops (see getExistingColumns()) since the column may no longer
     * exist. Backfill the modern columns/tables from those same fixture values here, once, so every
     * fixture-loading path behaves like rows actually written through the gateways - mirrors what
     * the real Add*AlwaysAvailableColumns/AddLanguageTranslationTables/
     * AddSearchObjectWordLinkLanguageIdColumns migrations backfill for production upgrades.
     *
     * Reimplements the (small, stable) bitmask decode directly rather than depending on
     * Persistence\Legacy's MaskGenerator - this is a test-only concern in a Contracts package that
     * must not depend on a specific storage engine's internals.
     *
     * @param array<string, array<array<string, mixed>>> $nonEmptyTablesData
     */
    private function backfillLanguageBitmaskColumns(array $nonEmptyTablesData): void
    {
        if (!$this->tableExists('ibexa_content_translation')) {
            // Schema predates this migration entirely (or has already dropped the join tables in
            // some hypothetical future) - nothing to backfill into.
            return;
        }

        $validLanguageIds = null;

        // The join tables aren't part of the fixture's own table list, so import()'s
        // truncate-then-insert loop never clears them directly; do it explicitly rather than rely
        // on FK cascade behavior varying by platform/driver.
        if (!empty($nonEmptyTablesData['ibexa_content'])) {
            $this->connection->executeStatement('DELETE FROM ibexa_content_translation');
        }
        if (!empty($nonEmptyTablesData['ibexa_content_version'])) {
            $this->connection->executeStatement('DELETE FROM ibexa_content_version_translation');
        }
        if (!empty($nonEmptyTablesData['ibexa_url_alias_ml']) && $this->tableExists('ibexa_url_alias_ml_translation')) {
            $this->connection->executeStatement('DELETE FROM ibexa_url_alias_ml_translation');
        }

        if (!empty($nonEmptyTablesData['ibexa_content'])) {
            $validLanguageIds ??= $this->loadValidLanguageIds();
            foreach ($nonEmptyTablesData['ibexa_content'] as $row) {
                if (!array_key_exists('language_mask', $row)) {
                    continue;
                }
                $mask = (int)$row['language_mask'];
                $this->connection->executeStatement(
                    'UPDATE ibexa_content SET always_available = :alwaysAvailable WHERE id = :id',
                    ['alwaysAvailable' => ($mask & 1) === 1, 'id' => $row['id']],
                    ['alwaysAvailable' => ParameterType::BOOLEAN, 'id' => ParameterType::INTEGER]
                );
                foreach ($this->extractLanguageIds($mask, $validLanguageIds) as $languageId) {
                    $this->connection->executeStatement(
                        'INSERT INTO ibexa_content_translation (content_id, language_id) VALUES (:id, :languageId)',
                        ['id' => $row['id'], 'languageId' => $languageId],
                        ['id' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
                    );
                }
            }
        }

        if (!empty($nonEmptyTablesData['ibexa_content_version'])) {
            $validLanguageIds ??= $this->loadValidLanguageIds();
            foreach ($nonEmptyTablesData['ibexa_content_version'] as $row) {
                if (!array_key_exists('language_mask', $row)) {
                    continue;
                }
                $mask = (int)$row['language_mask'];
                $this->connection->executeStatement(
                    'UPDATE ibexa_content_version SET always_available = :alwaysAvailable WHERE id = :id',
                    ['alwaysAvailable' => ($mask & 1) === 1, 'id' => $row['id']],
                    ['alwaysAvailable' => ParameterType::BOOLEAN, 'id' => ParameterType::INTEGER]
                );
                foreach ($this->extractLanguageIds($mask, $validLanguageIds) as $languageId) {
                    $this->connection->executeStatement(
                        'INSERT INTO ibexa_content_version_translation (content_version_id, language_id) VALUES (:id, :languageId)',
                        ['id' => $row['id'], 'languageId' => $languageId],
                        ['id' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
                    );
                }
            }
        }

        if (!empty($nonEmptyTablesData['ibexa_url_alias_ml']) && $this->tableExists('ibexa_url_alias_ml_translation')) {
            $validLanguageIds ??= $this->loadValidLanguageIds();
            foreach ($nonEmptyTablesData['ibexa_url_alias_ml'] as $row) {
                if (!array_key_exists('lang_mask', $row)) {
                    continue;
                }
                $mask = (int)$row['lang_mask'];
                $this->connection->executeStatement(
                    'UPDATE ibexa_url_alias_ml SET is_always_available = :alwaysAvailable WHERE parent = :parent AND text_md5 = :textMd5',
                    ['alwaysAvailable' => ($mask & 1) === 1, 'parent' => $row['parent'], 'textMd5' => $row['text_md5']],
                    ['alwaysAvailable' => ParameterType::BOOLEAN, 'parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING]
                );
                foreach ($this->extractLanguageIds($mask, $validLanguageIds) as $languageId) {
                    $this->connection->executeStatement(
                        'INSERT INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id) VALUES (:parent, :textMd5, :languageId)',
                        ['parent' => $row['parent'], 'textMd5' => $row['text_md5'], 'languageId' => $languageId],
                        ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING, 'languageId' => ParameterType::INTEGER]
                    );
                }
            }
        }

        if (!empty($nonEmptyTablesData['ibexa_search_object_word_link']) && $this->tableExists('ibexa_search_object_word_link') && in_array('language_id', $this->getExistingColumns('ibexa_search_object_word_link'), true)) {
            foreach ($nonEmptyTablesData['ibexa_search_object_word_link'] as $row) {
                if (!array_key_exists('language_mask', $row)) {
                    continue;
                }
                $mask = (int)$row['language_mask'];
                $this->connection->executeStatement(
                    'UPDATE ibexa_search_object_word_link
                     SET language_id = :languageId, is_main_and_always_available = :alwaysAvailable
                     WHERE id = :id',
                    [
                        'languageId' => $mask & ~1,
                        'alwaysAvailable' => ($mask & 1) === 1,
                        'id' => $row['id'],
                    ],
                    [
                        'languageId' => ParameterType::INTEGER,
                        'alwaysAvailable' => ParameterType::BOOLEAN,
                        'id' => ParameterType::INTEGER,
                    ]
                );
            }
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->connection->createSchemaManager()->tablesExist([$table]);
    }

    /**
     * @return int[]
     */
    private function loadValidLanguageIds(): array
    {
        if (!$this->tableExists('ibexa_content_language')) {
            return [];
        }

        return array_map(
            'intval',
            $this->connection->fetchFirstColumn('SELECT id FROM ibexa_content_language')
        );
    }

    /**
     * Decodes real (non-always-available) language ids out of a legacy bitmask, restricted to ids
     * actually present in ibexa_content_language (mirrors the old SQL backfill's implicit
     * JOIN ibexa_content_language filter, needed since ibexa_content_translation's language_id has
     * a real FK to it).
     *
     * @param int[] $validLanguageIds
     *
     * @return int[]
     */
    private function extractLanguageIds(int $mask, array $validLanguageIds): array
    {
        $languageIds = [];
        for ($languageId = 2; $languageId <= $mask; $languageId *= 2) {
            if (($mask & $languageId) === $languageId && in_array($languageId, $validLanguageIds, true)) {
                $languageIds[] = $languageId;
            }
        }

        return $languageIds;
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
