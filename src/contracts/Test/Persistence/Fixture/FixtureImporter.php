<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Test\Persistence\Fixture;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Column;
use Ibexa\Contracts\Core\Test\Persistence\AppendOnlyFixture;
use Ibexa\Contracts\Core\Test\Persistence\Fixture;

/**
 * Database fixture importer.
 *
 * @internal for internal use by Repository test setup
 */
final class FixtureImporter
{
    /**
     * Tables whose "language_id" the legacy bitmask scheme ORed the "always available" bit into.
     */
    private const array TABLES_WITH_LEGACY_ALWAYS_AVAILABLE_BIT = [
        'ibexa_content_field',
        'ibexa_content_name',
        'ibexa_content_type_name',
        'ibexa_object_state_language',
        'ibexa_object_state_group_language',
    ];

    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * Tables referencing "ibexa_content_language", referenced tables first: language id columns and the owner whose replacement drops the rows.
     *
     * @var array<string, array{list<string>, string|null}>
     */
    private const array LANGUAGE_REFERENCES = [
        'ibexa_content_type' => [['initial_language_id'], null],
        'ibexa_content_type_name' => [['language_id'], null],
        'ibexa_content_type_field_definition_ml' => [['language_id'], null],
        'ibexa_content' => [['initial_language_id'], null],
        'ibexa_content_version' => [['initial_language_id'], null],
        'ibexa_content_field' => [['language_id'], null],
        'ibexa_content_name' => [['language_id'], null],
        'ibexa_object_state_group' => [['default_language_id'], null],
        'ibexa_object_state' => [['default_language_id'], null],
        'ibexa_object_state_group_language' => [['language_id', 'real_language_id'], null],
        'ibexa_object_state_language' => [['language_id'], null],
        'ibexa_content_translation' => [['language_id'], 'ibexa_content'],
        'ibexa_content_version_translation' => [['language_id'], 'ibexa_content_version'],
        'ibexa_url_alias_ml_translation' => [['language_id'], 'ibexa_url_alias_ml'],
        'ibexa_search_object_word_link' => [['language_id'], null],
    ];

    /** @var array<string, string|null> */
    private static array $resetSequenceStatements = [];

    /** @var array<string, string[]> */
    private static array $existingColumnsByTable = [];

    public function __construct(private readonly Connection $connection) {}

    /**
     * @throws Exception
     */
    public function import(Fixture $fixture): void
    {
        $data = $fixture->load();

        $tablesList = array_keys($data);
        if (in_array(self::LANGUAGE_TABLE, $tablesList, true)) {
            $tablesList = [self::LANGUAGE_TABLE, ...array_values(array_diff($tablesList, [self::LANGUAGE_TABLE]))];
        }
        $detachedReferences = [];
        if (!$fixture instanceof AppendOnlyFixture) {
            $detachedReferences = $this->detachLanguageReferences($tablesList);
            // truncate all tables, even the ones initially empty (some tests are affected by this)
            $this->truncateTables(array_reverse($tablesList));
        }

        $nonEmptyTablesData = array_filter(
            array_replace(array_fill_keys($tablesList, []), $data),
            static fn (array $tableData): bool => $tableData !== []
        );
        $languageIds = array_merge(
            $this->loadValidLanguageIds(),
            array_map('intval', array_column($data['ibexa_content_language'] ?? [], 'id'))
        );
        foreach ($nonEmptyTablesData as $table => $rows) {
            // Fixtures predate columns being dropped over time (e.g. the language bitmask
            // columns) - silently drop unknown keys rather than letting every fixture file need
            // updating in lockstep with schema changes.
            $existingColumns = $this->getExistingColumns($table);
            foreach ($rows as $row) {
                $row = $this->stripLegacyAlwaysAvailableBit($table, $row, $languageIds);
                $row = $this->splitLegacySearchIndexLanguageMask($table, $row);
                $this->connection->insert($table, array_intersect_key($row, array_flip($existingColumns)));
            }
        }

        if ($this->connection->getDatabasePlatform()->supportsSequences()) {
            $this->resetSequences($tablesList);
        }

        $this->reattachLanguageReferences($detachedReferences);
        if (!$fixture instanceof AppendOnlyFixture) {
            $this->clearLanguageTranslations($nonEmptyTablesData);
        }
        $this->backfillLanguageBitmaskColumns($nonEmptyTablesData);
    }

    /**
     * Empties the translation tables of the content, versions and URL aliases a fixture replaces.
     *
     * @param array<string, array<array<string, mixed>>> $nonEmptyTablesData
     *
     * @throws Exception
     */
    private function clearLanguageTranslations(array $nonEmptyTablesData): void
    {
        if (!$this->tableExists('ibexa_content_translation')) {
            return;
        }

        if (isset($nonEmptyTablesData['ibexa_content'])) {
            $this->connection->executeStatement('DELETE FROM ibexa_content_translation');
        }
        if (isset($nonEmptyTablesData['ibexa_content_version'])) {
            $this->connection->executeStatement('DELETE FROM ibexa_content_version_translation');
        }
        if (isset($nonEmptyTablesData['ibexa_url_alias_ml']) && $this->tableExists('ibexa_url_alias_ml_translation')) {
            $this->connection->executeStatement('DELETE FROM ibexa_url_alias_ml_translation');
        }
    }

    /**
     * Takes out the rows referencing the languages a fixture replaces, from the tables it leaves alone.
     *
     * @param string[] $tablesList
     *
     * @return array<string, list<array<string, mixed>>>
     *
     * @throws Exception
     */
    private function detachLanguageReferences(array $tablesList): array
    {
        if (!in_array(self::LANGUAGE_TABLE, $tablesList, true)) {
            return [];
        }

        $detached = [];
        foreach (array_reverse(self::LANGUAGE_REFERENCES) as $table => [, $ownerTable]) {
            if (in_array($table, $tablesList, true) || !$this->tableExists($table)) {
                continue;
            }
            if ($ownerTable === null || !in_array($ownerTable, $tablesList, true)) {
                $detached[$table] = $this->connection->fetchAllAssociative('SELECT * FROM ' . $table);
            }
            $this->connection->executeStatement('DELETE FROM ' . $table);
        }

        return array_reverse($detached);
    }

    /**
     * @param array<string, list<array<string, mixed>>> $detachedReferences
     *
     * @throws Exception
     */
    private function reattachLanguageReferences(array $detachedReferences): void
    {
        if ($detachedReferences === []) {
            return;
        }

        $validLanguageIds = $this->loadValidLanguageIds();
        foreach ($detachedReferences as $table => $rows) {
            foreach ($rows as $row) {
                foreach (self::LANGUAGE_REFERENCES[$table][0] as $column) {
                    if (!in_array((int)$row[$column], $validLanguageIds, true)) {
                        continue 2;
                    }
                }
                $this->connection->insert($table, $row);
            }
        }
    }

    /**
     * Clears the legacy "always available" bit from a row's odd "language_id" unless it's a language.
     *
     * @param array<string, mixed> $row
     * @param int[] $languageIds
     *
     * @return array<string, mixed>
     */
    private function stripLegacyAlwaysAvailableBit(
        string $table,
        array $row,
        array $languageIds
    ): array {
        if (!isset($row['language_id']) || !in_array($table, self::TABLES_WITH_LEGACY_ALWAYS_AVAILABLE_BIT, true)) {
            return $row;
        }

        $languageId = (int)$row['language_id'];
        if ($languageId % 2 === 1 && !in_array($languageId, $languageIds, true)) {
            $row['language_id'] = $languageId - 1;
        }

        return $row;
    }

    /**
     * Turns a legacy "language_mask" of a search index row into its "language_id" and "is_main_and_always_available".
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function splitLegacySearchIndexLanguageMask(
        string $table,
        array $row
    ): array {
        if ($table !== 'ibexa_search_object_word_link' || !isset($row['language_mask']) || isset($row['language_id'])) {
            return $row;
        }

        $languageMask = (int)$row['language_mask'];
        $row['language_id'] = $languageMask & ~1;
        $row['is_main_and_always_available'] = $languageMask & 1;

        return $row;
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
     * Fixture data predates "always_available" becoming plain columns and
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

        $validLanguageIds = $this->loadValidLanguageIds();
        $this->backfillContentLanguages('ibexa_content', 'ibexa_content_translation', 'content_id', $nonEmptyTablesData['ibexa_content'] ?? [], $validLanguageIds);
        $this->backfillContentLanguages('ibexa_content_version', 'ibexa_content_version_translation', 'content_version_id', $nonEmptyTablesData['ibexa_content_version'] ?? [], $validLanguageIds);

        if (isset($nonEmptyTablesData['ibexa_url_alias_ml']) && $this->tableExists('ibexa_url_alias_ml_translation')) {
            $this->backfillUrlAliasLanguages($nonEmptyTablesData['ibexa_url_alias_ml'], $validLanguageIds);
        }

        if (isset($nonEmptyTablesData['ibexa_search_object_word_link']) && $this->tableExists('ibexa_search_object_word_link') && in_array('language_id', $this->getExistingColumns('ibexa_search_object_word_link'), true)) {
            $this->backfillSearchIndexLanguages($nonEmptyTablesData['ibexa_search_object_word_link']);
        }
    }

    /**
     * Sets "always_available" and the translation rows of content or versions from their fixture "language_mask".
     *
     * @param array<array<string, mixed>> $rows
     * @param int[] $validLanguageIds
     *
     * @throws Exception
     */
    private function backfillContentLanguages(
        string $table,
        string $translationTable,
        string $idColumn,
        array $rows,
        array $validLanguageIds
    ): void {
        foreach ($rows as $row) {
            if (!array_key_exists('language_mask', $row)) {
                continue;
            }
            $mask = (int)$row['language_mask'];
            $this->connection->executeStatement(
                "UPDATE {$table} SET always_available = :alwaysAvailable WHERE id = :id",
                ['alwaysAvailable' => ($mask & 1) === 1, 'id' => $row['id']],
                ['alwaysAvailable' => ParameterType::BOOLEAN, 'id' => ParameterType::INTEGER]
            );
            foreach ($this->extractLanguageIds($mask, $validLanguageIds) as $languageId) {
                $this->connection->executeStatement(
                    "INSERT INTO {$translationTable} ({$idColumn}, language_id) VALUES (:id, :languageId)",
                    ['id' => $row['id'], 'languageId' => $languageId],
                    ['id' => ParameterType::INTEGER, 'languageId' => ParameterType::INTEGER]
                );
            }
        }
    }

    /**
     * Sets the translation rows of URL aliases from their fixture "lang_mask".
     *
     * @param array<array<string, mixed>> $rows
     * @param int[] $validLanguageIds
     *
     * @throws Exception
     */
    private function backfillUrlAliasLanguages(
        array $rows,
        array $validLanguageIds
    ): void {
        foreach ($rows as $row) {
            if (!array_key_exists('lang_mask', $row)) {
                continue;
            }
            $mask = (int)$row['lang_mask'];
            $this->connection->executeStatement(
                'UPDATE ibexa_url_alias_ml SET always_available = :alwaysAvailable WHERE parent = :parent AND text_md5 = :textMd5',
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

    /**
     * Sets "language_id" and "is_main_and_always_available" of search index rows from their fixture "language_mask".
     *
     * @param array<array<string, mixed>> $rows
     *
     * @throws Exception
     */
    private function backfillSearchIndexLanguages(array $rows): void
    {
        foreach ($rows as $row) {
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
    private function extractLanguageIds(
        int $mask,
        array $validLanguageIds
    ): array {
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
     * @throws Exception
     */
    private function truncateTables(array $tables): void
    {
        $dbPlatform = $this->connection->getDatabasePlatform();

        foreach ($tables as $table) {
            try {
                // Cleanup before inserting (using TRUNCATE for speed, however not possible to rollback)
                $this->connection->executeStatement(
                    $dbPlatform->getTruncateTableSql($this->connection->quoteSingleIdentifier($table))
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
     * @throws Exception
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
     * @throws Exception
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
                sprintf('MAX(%s)', $this->connection->quoteSingleIdentifier($columnName)),
                $this->connection->quoteSingleIdentifier($tableName)
            );
        }

        // Return sequence change commands for affected tables
        $result = array_intersect_key(self::$resetSequenceStatements, array_fill_keys($affectedTables, true));

        return array_filter($result);
    }

    /**
     * @param array<Column> $columns
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
