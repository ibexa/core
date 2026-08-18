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
 * Adds "language_id" and "is_main_and_always_available" columns to
 * "ibexa_search_object_word_link", backfilled from its "language_mask" column (part of step 5 of
 * the language bitmask migration). Unlike other multilingual tables, this one only ever stored a
 * single language id per row (OR-ed with the always-available bit), so the replacement is a plain
 * id column plus a boolean flag rather than a join table. "language_mask" itself is dropped later,
 * once every write path has stopped populating it - see DropLanguageBitmaskColumnsMigration.
 *
 * Existing search index rows are backfilled here for consistency with every other step of this
 * migration, but the recommended (and, for a fully corrected index, mandatory) follow-up is a full
 * search reindex - the word-splitting/normalization logic itself is untouched, but this guarantees
 * newly indexed content never round-trips through the legacy bitmask columns.
 *
 * The two columns (and their backfills) are checked and queued independently: on MySQL, each
 * `ALTER TABLE`/`UPDATE` auto-commits independently, so a failure after adding "language_id" but
 * before adding "is_main_and_always_available" (or before either backfill runs) must not make a
 * retry mistake "the first column already exists" for "this migration already fully ran" and
 * silently skip the rest. Each backfill UPDATE is idempotent (derived only from "language_mask",
 * which this migration never modifies), so re-running it whenever anything else is still missing is
 * always safe.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class AddSearchObjectWordLinkLanguageIdColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const TABLE = 'ibexa_search_object_word_link';
    private const LANGUAGE_ID_COLUMN = 'language_id';
    private const IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN = 'is_main_and_always_available';

    public function getDescription(): string
    {
        return 'Adds "language_id" and "is_main_and_always_available" columns to "ibexa_search_object_word_link", backfilled from the language mask';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:03');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return;
        }

        $table = $schemaManager->introspectTable(self::TABLE);
        $hasLanguageIdColumn = $table->hasColumn(self::LANGUAGE_ID_COLUMN);
        $hasAlwaysAvailableColumn = $table->hasColumn(self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN);

        if ($hasLanguageIdColumn && $hasAlwaysAvailableColumn) {
            // Already fully applied - avoid an unconditional full-table backfill re-scan once this
            // migration has genuinely completed.
            return;
        }

        if (!$hasLanguageIdColumn) {
            $this->addSql($this->buildAddLanguageIdColumnSql());
        }

        if (!$hasAlwaysAvailableColumn) {
            $this->addSql($this->buildAddAlwaysAvailableColumnSql());
        }

        $this->addSql(
            'UPDATE ' . self::TABLE . ' SET ' . self::LANGUAGE_ID_COLUMN . ' = (language_mask & -2)'
        );
        $this->addSql(
            'UPDATE ' . self::TABLE . ' SET ' . self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN . ' = ' .
            ($this->isPostgreSQL() ? 'true' : '1') . ' WHERE (language_mask & 1) = 1'
        );
    }

    private function buildAddLanguageIdColumnSql(): string
    {
        $columnDefinition = $this->isMySQL() ? "INT DEFAULT '0' NOT NULL" : 'INTEGER DEFAULT 0 NOT NULL';

        return 'ALTER TABLE ' . self::TABLE . ' ADD COLUMN ' . self::LANGUAGE_ID_COLUMN . " {$columnDefinition}";
    }

    private function buildAddAlwaysAvailableColumnSql(): string
    {
        $columnDefinition = match (true) {
            $this->isMySQL() => "TINYINT(1) DEFAULT '0' NOT NULL",
            $this->isPostgreSQL() => "BOOLEAN DEFAULT 'false' NOT NULL",
            default => "BOOLEAN DEFAULT '0' NOT NULL",
        };

        return 'ALTER TABLE ' . self::TABLE . ' ADD COLUMN ' . self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN .
            " {$columnDefinition}";
    }
}
