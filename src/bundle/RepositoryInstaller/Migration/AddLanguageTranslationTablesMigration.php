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
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

/**
 * Creates "ibexa_content_translation", "ibexa_content_version_translation" and
 * "ibexa_url_alias_ml_translation" - the relational replacement for the
 * "language_mask"/"lang_mask" columns on "ibexa_content", "ibexa_content_version" and
 * "ibexa_url_alias_ml" (step 2 of the language bitmask migration).
 *
 * Purely additive: tables are created empty and stay empty until
 * "ibexa:languages:backfill-translations" populates them, and nothing reads them yet - the mask
 * columns remain authoritative until later steps switch read paths over and eventually drop them.
 *
 * On SQLite, each table's foreign keys are embedded directly in its own `CREATE TABLE IF NOT
 * EXISTS` statement (SQLite has no separate `ALTER TABLE ... ADD CONSTRAINT`), so the table and its
 * constraints are always created atomically together - a table existing at all means it's complete.
 *
 * On MySQL/PostgreSQL, `CREATE TABLE`/`ADD CONSTRAINT` are separate statements that each commit
 * independently, so a failure partway through (e.g. after creating "ibexa_content_translation" but
 * before its constraints, or before the other two tables) must not make a retry mistake "the first
 * table already exists" for "everything already ran" and silently skip the rest - each of the 6
 * foreign keys across the 3 tables is checked and queued independently.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable() would always report false there.
 */
final class AddLanguageTranslationTablesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';

    /**
     * Not yet renamed to "ibexa_language" at this point in the migration sequence - that rename
     * happens later, in {@see NarrowLanguageIdColumnTypesMigration}.
     *
     * @var array<string, array<string, string>>
     */
    private const FOREIGN_KEYS = [
        'ibexa_content_translation' => [
            'ibexa_content_translation_content_fk' => 'FOREIGN KEY (content_id) REFERENCES ibexa_content (id) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_content_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
        'ibexa_content_version_translation' => [
            'ibexa_content_version_translation_version_fk' => 'FOREIGN KEY (content_version_id) REFERENCES ibexa_content_version (id) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_content_version_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
        'ibexa_url_alias_ml_translation' => [
            'ibexa_url_alias_ml_translation_alias_fk' => 'FOREIGN KEY (parent, text_md5) REFERENCES ibexa_url_alias_ml (parent, text_md5) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_url_alias_ml_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
    ];

    public function getDescription(): string
    {
        return 'Creates "ibexa_content_translation", "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        $allTablesExist = $schemaManager->tablesExist(array_keys(self::FOREIGN_KEYS));

        if ($this->isSqlite()) {
            if ($allTablesExist) {
                return;
            }

            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-sqlite.sql');

            return;
        }

        $missingForeignKeys = $this->findMissingForeignKeys($schemaManager);

        if ($allTablesExist && $missingForeignKeys === []) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-postgresql.sql');
        }

        foreach ($missingForeignKeys as [$table, $constraintName, $definition]) {
            $this->addSql("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} {$definition}");
        }
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function findMissingForeignKeys(AbstractSchemaManager $schemaManager): array
    {
        $missing = [];

        foreach (self::FOREIGN_KEYS as $table => $foreignKeys) {
            $existingTable = $schemaManager->tablesExist([$table])
                ? $schemaManager->introspectTable($table)
                : null;

            foreach ($foreignKeys as $constraintName => $definition) {
                if ($existingTable !== null && $existingTable->hasForeignKey($constraintName)) {
                    continue;
                }

                $missing[] = [$table, $constraintName, $definition];
            }
        }

        return $missing;
    }
}
