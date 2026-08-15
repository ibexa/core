<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

/**
 * Drops the language bitmask columns ("language_mask"/"lang_mask") from every table that carried
 * one - the final step of the language bitmask migration. By this point nothing reads them:
 * always-available is a plain boolean column (Step 1), Content/Version/UrlAlias translations are
 * relational join tables (Steps 2-3, 6-7), Legacy Search's word index carries "language_id"
 * directly (Step 5), and ContentType's languages come from "ibexa_content_type_name" (Step 7).
 *
 * ObjectState/ObjectStateGroup's "language_mask" was never read anywhere to begin with - it
 * duplicated data already available via the "ibexa_object_state(_group)_language" join tables.
 *
 * BackfillLanguageTranslationsMigration (which runs earlier in the same sequence) populates
 * ibexa_content_translation/ibexa_content_version_translation/ibexa_url_alias_ml_translation from
 * the existing mask data before this migration runs, so a standard `doctrine:migrations:migrate`
 * upgrade needs no separate manual step. abortIfTranslationsNotBackfilled() is a defensive check
 * against that having been skipped or interrupted (e.g. a manual/partial migration run) - it is not
 * the primary mechanism. The `ibexa:languages:backfill-translations`/`ibexa:languages:verify-translations`
 * commands remain available for a dry-run preview or manual repair.
 *
 * Each table's column (and, where present, its associated index) is checked and dropped
 * independently: on MySQL, every `DROP INDEX`/`DROP COLUMN`/`ADD INDEX` auto-commits independently,
 * so a failure partway through this destructive sequence must not make a retry mistake "the first
 * table's column is already gone" for "everything already ran" - it would leave every later table's
 * mask column and index dangling forever, undetected, since a subsequent run would return early at
 * the first check.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class DropLanguageBitmaskColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';

    /**
     * Not yet renamed to "ibexa_language" at this point in the migration sequence - that rename
     * happens later, in {@see NarrowLanguageIdColumnTypesMigration}.
     */
    private const LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * Table => [mask column, index to drop (if any), whether the index needs replacing with a
     * lang-less equivalent rather than just dropped (only "ibexa_url_alias_ml")].
     *
     * @var array<string, array{0: string, 1: string|null, 2: bool}>
     */
    private const DROPS = [
        'ibexa_object_state' => ['language_mask', 'ibexa_object_state_lmask', false],
        'ibexa_object_state_group' => ['language_mask', 'ibexa_object_state_group_lmask', false],
        'ibexa_content_type' => ['language_mask', null, false],
        self::CONTENT_TABLE => ['language_mask', 'ibexa_content_lmask', false],
        'ibexa_content_version' => ['language_mask', null, false],
        'ibexa_search_object_word_link' => ['language_mask', null, false],
        'ibexa_url_alias_ml' => ['lang_mask', 'ibexa_url_alias_ml_text_lang', true],
    ];

    /**
     * Which of {@see DROPS} also need a `abortIfTranslationsNotBackfilled()` check before their
     * column is dropped, and what to check it against.
     */
    private const BACKFILL_CHECKS = [
        self::CONTENT_TABLE => ['ibexa_content_translation', 'content_id'],
        'ibexa_content_version' => ['ibexa_content_version_translation', 'content_version_id'],
        'ibexa_url_alias_ml' => ['ibexa_url_alias_ml_translation', null],
    ];

    public function getDescription(): string
    {
        return 'Drops the language bitmask ("language_mask"/"lang_mask") columns, now that nothing reads them';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:05');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        $tablesStillCarryingMask = [];

        foreach (self::DROPS as $table => [$maskColumn]) {
            if (
                $schemaManager->tablesExist([$table])
                && $schemaManager->introspectTable($table)->hasColumn($maskColumn)
            ) {
                $tablesStillCarryingMask[$table] = true;
            }
        }

        if ($tablesStillCarryingMask === []) {
            // Already dropped everywhere (or a fresh install whose schema.yaml never had it).
            return;
        }

        $this->abortIfTranslationsNotBackfilled(array_keys($tablesStillCarryingMask));

        foreach (self::DROPS as $table => [$maskColumn, $indexName, $replaceIndex]) {
            if (!isset($tablesStillCarryingMask[$table])) {
                continue;
            }

            $introspectedTable = $schemaManager->introspectTable($table);

            if ($indexName !== null) {
                $indexExists = $introspectedTable->hasIndex($indexName);
                $indexStillHasMaskColumn = $indexExists
                    && in_array($maskColumn, $introspectedTable->getIndex($indexName)->getColumns(), true);

                if ($replaceIndex) {
                    if ($indexStillHasMaskColumn) {
                        // Still the old, mask-including definition - drop it before recreating
                        // without the mask column below.
                        $this->addSql($this->buildDropIndexSql($table, $indexName));
                    }

                    if ($indexStillHasMaskColumn || !$indexExists) {
                        // Either just dropped above, or missing entirely because a prior partial
                        // run dropped it but was interrupted before recreating it - either way it
                        // still needs (re)creating in its final, mask-less form. If neither is true,
                        // the index already exists in that final form and needs no change.
                        $this->addSql($this->buildCreateUrlAliasTextParentIndexSql());
                    }
                } elseif ($indexExists) {
                    $this->addSql($this->buildDropIndexSql($table, $indexName));
                }
            }

            $this->addSql("ALTER TABLE {$table} DROP COLUMN {$maskColumn}");
        }
    }

    private function buildDropIndexSql(string $table, string $indexName): string
    {
        // MySQL ties an index's identity to its table ("DROP INDEX x ON t" / "ALTER TABLE t DROP
        // INDEX x"); PostgreSQL/SQLite index names are unique connection/schema-wide, dropped
        // without referencing the table.
        return $this->isMySQL()
            ? "ALTER TABLE {$table} DROP INDEX {$indexName}"
            : "DROP INDEX {$indexName}";
    }

    private function buildCreateUrlAliasTextParentIndexSql(): string
    {
        // Replaces the dropped "(text(32), parent)"/"(text, parent)" + lang index with a lang-less
        // equivalent - "lang_mask" is gone, but the (text, parent) lookup itself is still needed.
        return $this->isMySQL()
            ? 'ALTER TABLE ibexa_url_alias_ml ADD INDEX ibexa_url_alias_ml_text_lang (text(32), parent)'
            : 'CREATE INDEX ibexa_url_alias_ml_text_lang ON ibexa_url_alias_ml (text, parent)';
    }

    /**
     * Refuses to drop the mask columns if any row still carrying a real (non-always-available)
     * language bit has no corresponding row in the relational replacement it should have been
     * backfilled into - i.e. `ibexa:languages:backfill-translations` was never run, or didn't
     * finish, for this table. Once the mask columns are gone the mask data is unrecoverable, so
     * this check is deliberately a hard abort rather than a warning.
     *
     * Joins against every language bit actually set in the mask (via {@see LANGUAGE_TABLE}, the
     * table of valid bit values) rather than just checking "does any translation row exist for this
     * content id at all" - a row with two bits set but only one backfilled (e.g. mask 2|4 with only
     * language 2's translation row written) must still be caught here, or dropping the mask below
     * would silently and irrecoverably lose language 4's membership for that row.
     *
     * Only checks tables in $tablesStillCarryingMask (rather than unconditionally checking all of
     * {@see BACKFILL_CHECKS}): once a table's mask column is actually dropped, the column this
     * check's SQL references no longer exists, so re-running it unconditionally on a later retry
     * would fail with an unrelated "unknown column" error instead of the intended abort message.
     *
     * @param string[] $tablesStillCarryingMask
     */
    private function abortIfTranslationsNotBackfilled(array $tablesStillCarryingMask): void
    {
        foreach (self::BACKFILL_CHECKS as $maskTable => [$translationTable, $idColumn]) {
            if (!in_array($maskTable, $tablesStillCarryingMask, true)) {
                continue;
            }

            $maskColumn = $maskTable === 'ibexa_url_alias_ml' ? 'lang_mask' : 'language_mask';

            if ($idColumn !== null) {
                $joinCondition = "t.{$idColumn} = m.id AND t.language_id = l.id";
            } else {
                // ibexa_url_alias_ml's primary key is (parent, text_md5), not a single "id" column.
                $joinCondition = 't.parent = m.parent AND t.text_md5 = m.text_md5 AND t.language_id = l.id';
            }

            $missingCount = (int)$this->connection->fetchOne(
                "SELECT COUNT(*) FROM {$maskTable} m
                 JOIN " . self::LANGUAGE_TABLE . " l ON (m.{$maskColumn} & l.id) = l.id
                 WHERE NOT EXISTS (SELECT 1 FROM {$translationTable} t WHERE {$joinCondition})"
            );

            $this->abortIf(
                $missingCount > 0,
                "Refusing to drop \"{$maskColumn}\" from \"{$maskTable}\": {$missingCount} row(s) carry a " .
                "real language bit with no matching row in \"{$translationTable}\" - run " .
                '"ibexa:languages:backfill-translations" (and confirm ' .
                '"ibexa:languages:verify-translations" reports clean) before this migration.'
            );
        }
    }
}
