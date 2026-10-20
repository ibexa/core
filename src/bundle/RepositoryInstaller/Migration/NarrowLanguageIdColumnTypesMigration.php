<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\BigIntType;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Narrows "ibexa_language.id" and every "language_id"-shaped column in core from BIGINT to INT. A
 * regular install never comes close to the ~2 billion languages an INT allows, so BIGINT, chosen to
 * mirror the legacy bitmask's PHP-integer width, is width carried over from that scheme.
 *
 * On MySQL and MariaDB, which refuse to change either column of a foreign key, the keys pointing at
 * "ibexa_language.id" are dropped right before this migration and re-created right after it: core's
 * own by {@see DropLanguageIdForeignKeysMigration} and {@see RestoreLanguageIdForeignKeysMigration},
 * those of other packages by their own migrations. The statements are in
 * sql/narrow-language-id-column-types-mysql.sql and change only column types, so a retry after a
 * failure can run all of them again. The migration aborts with the list of keys when any key still
 * points at "ibexa_language.id", rather than failing halfway through.
 *
 * PostgreSQL changes the types with the keys in place, and the migration runs there in one
 * transaction (sql/narrow-language-id-column-types-postgresql.sql). SQLite has no fixed-width
 * INTEGER/BIGINT distinction and no `ALTER TABLE ... ALTER COLUMN ... TYPE`, so it's skipped; a fresh
 * SQLite install gets the INT columns from schema.yaml.
 *
 * Language ids under the bitmask scheme are powers of two, so a database with more than 30 languages
 * has ids that don't fit in an INT. The migration aborts then too, instead of failing on the first
 * statement.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema.
 */
final class NarrowLanguageIdColumnTypesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_language';
    private const int INT_MAX = 2147483647;

    /** The columns sql/narrow-language-id-column-types-*.sql narrows, by table. */
    private const array COLUMNS = [
        'ibexa_language' => ['id'],
        'ibexa_content_translation' => ['language_id'],
        'ibexa_content_version_translation' => ['language_id'],
        'ibexa_url_alias_ml_translation' => ['language_id'],
        'ibexa_content_type_field_definition_ml' => ['language_id'],
        'ibexa_content' => ['initial_language_id'],
        'ibexa_content_version' => ['initial_language_id'],
        'ibexa_content_type' => ['initial_language_id'],
        'ibexa_object_state' => ['default_language_id'],
        'ibexa_object_state_group' => ['default_language_id'],
        'ibexa_object_state_language' => ['language_id'],
        'ibexa_object_state_group_language' => ['language_id', 'real_language_id'],
    ];

    public function getDescription(): string
    {
        return 'Narrows "ibexa_language.id" and the "language_id"-shaped columns from BIGINT to INT';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 06:00:02');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if ($this->isSqlite()) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::LANGUAGE_TABLE]) || !$this->hasBigIntColumns($schemaManager)) {
            return;
        }

        $maxLanguageId = (int)$this->connection->fetchOne('SELECT MAX(id) FROM ' . self::LANGUAGE_TABLE);
        $this->abortIf(
            $maxLanguageId > self::INT_MAX,
            sprintf(
                'Language id %d doesn\'t fit in an INT. Ids that large come from the bitmask scheme with more than 30 languages, and need renumbering before the language ids can be narrowed.',
                $maxLanguageId
            )
        );

        if ($this->isMySQL() || $this->isMariaDB()) {
            $foreignKeys = $this->connection->fetchFirstColumn(
                "SELECT CONCAT(TABLE_NAME, '.', CONSTRAINT_NAME) FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? ORDER BY 1",
                [self::LANGUAGE_TABLE]
            );
            $this->abortIf(
                $foreignKeys !== [],
                sprintf(
                    'These foreign keys still point at "%s.id", and MySQL and MariaDB won\'t narrow either of their columns while they exist: %s. Drop them before this migration, and narrow their columns to INT and re-create them after it.',
                    self::LANGUAGE_TABLE,
                    implode(', ', $foreignKeys)
                )
            );

            $this->addSqlFile(__DIR__ . '/sql/narrow-language-id-column-types-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/narrow-language-id-column-types-postgresql.sql');
        }
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     */
    private function hasBigIntColumns(AbstractSchemaManager $schemaManager): bool
    {
        foreach (self::COLUMNS as $table => $columns) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $introspected = $schemaManager->introspectTable($table);
            foreach ($columns as $column) {
                if ($introspected->hasColumn($column) && $introspected->getColumn($column)->getType() instanceof BigIntType) {
                    return true;
                }
            }
        }

        return false;
    }
}
