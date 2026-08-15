<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

/**
 * Renames "ibexa_content_language" to "ibexa_language" - the table holds every language in the
 * system (content, object states, content types, URL aliases), not just "content" languages, so the
 * name shouldn't suggest otherwise - and narrows every "language_id"-shaped column from BIGINT to
 * INTEGER, starting with the renamed table's own "id" column: a regular install never comes close to
 * the ~2 billion languages an INTEGER already allows, so BIGINT (originally chosen to mirror the
 * legacy bitmask's PHP-integer width) is unnecessary column width carried over from that scheme.
 * {@see \Ibexa\Core\Persistence\Legacy\Content\Language\Gateway::CONTENT_LANGUAGE_TABLE} is the only
 * place application code names this table, so this migration plus that one constant change is the
 * entire cutover - every gateway and test fixture that already goes through the constant needs no
 * further changes.
 *
 * The rename itself is a plain, uniformly-supported operation on all 3 platforms (including SQLite,
 * which has no ALTER COLUMN TYPE but does support `ALTER TABLE ... RENAME TO ...`), and none of
 * MySQL/PostgreSQL/SQLite drop foreign keys pointing at a renamed table - they're tracked internally,
 * not by name - so it needs no FK drop/recreate step of its own.
 *
 * The type-narrowing part is trickier: four of the affected columns already carry a live FOREIGN KEY
 * to the language table's "id" column (added earlier in this same migration sequence):
 * "ibexa_content_translation.language_id", "ibexa_content_version_translation.language_id",
 * "ibexa_url_alias_ml_translation.language_id" and "ibexa_content_type_field_definition_ml.language_id".
 * Narrowing a column that's party to a FK isn't reliably portable across MySQL/PostgreSQL without
 * dropping the constraint first, so the narrowing SQL drops those four FKs, narrows every affected
 * column (the four above, the language table's "id" they reference, and every other unconstrained
 * "language_id"-shaped column touched by this migration sequence), then re-creates the four FKs with
 * their original options - all of it referencing the table by its new name, since the rename (queued
 * first) always executes before it.
 *
 * SQLite only gets the rename: it has no fixed-width INTEGER/BIGINT distinction (both use the same
 * dynamic storage class) and no `ALTER TABLE ... ALTER COLUMN ... TYPE`, so there is nothing to gain
 * from a costly full-table-rebuild just to change a declared type that SQLite itself ignores. A fresh
 * SQLite install already gets the narrower declared type directly from schema.yaml.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class NarrowLanguageIdColumnTypesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const OLD_LANGUAGE_TABLE = 'ibexa_content_language';
    private const LANGUAGE_TABLE = 'ibexa_language';
    private const LANGUAGE_TABLE_ID_COLUMN = 'id';

    public function getDescription(): string
    {
        return 'Renames "ibexa_content_language" to "ibexa_language" and narrows "language_id"-shaped columns from BIGINT to INTEGER';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:06');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        // Both branches are checked (rather than assuming "old name gone" means "already renamed by
        // this migration") so re-running after a partial/interrupted execution - or a fresh install,
        // which never has either at this point outside a schema.yaml-driven install - is a no-op.
        $currentTableName = match (true) {
            $schemaManager->tablesExist([self::OLD_LANGUAGE_TABLE]) => self::OLD_LANGUAGE_TABLE,
            $schemaManager->tablesExist([self::LANGUAGE_TABLE]) => self::LANGUAGE_TABLE,
            default => null,
        };

        if ($currentTableName === null) {
            return;
        }

        $needsRename = $currentTableName === self::OLD_LANGUAGE_TABLE;
        $idColumn = $schemaManager->introspectTable($currentTableName)->getColumn(self::LANGUAGE_TABLE_ID_COLUMN);
        $needsNarrowing = $idColumn->getType() instanceof BigIntType;

        if (!$needsRename && !$needsNarrowing) {
            return;
        }

        if ($this->isSqlite()) {
            if ($needsRename) {
                $this->addSql(sprintf('ALTER TABLE %s RENAME TO %s', self::OLD_LANGUAGE_TABLE, self::LANGUAGE_TABLE));
            }

            // No ALTER COLUMN TYPE support on SQLite - see class docblock.
            return;
        }

        if ($needsRename) {
            $this->addSql(
                $this->isMySQL()
                    ? sprintf('RENAME TABLE %s TO %s', self::OLD_LANGUAGE_TABLE, self::LANGUAGE_TABLE)
                    : sprintf('ALTER TABLE %s RENAME TO %s', self::OLD_LANGUAGE_TABLE, self::LANGUAGE_TABLE)
            );
        }

        if ($needsNarrowing) {
            if ($this->isMySQL()) {
                $this->addSqlFile(__DIR__ . '/sql/narrow-language-id-column-types-mysql.sql');
            } elseif ($this->isPostgreSQL()) {
                $this->addSqlFile(__DIR__ . '/sql/narrow-language-id-column-types-postgresql.sql');
            }
        }
    }
}
