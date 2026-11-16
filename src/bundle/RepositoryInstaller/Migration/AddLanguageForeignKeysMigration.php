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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Adds foreign keys from every column holding a language id to "ibexa_content_language.id".
 */
final class AddLanguageForeignKeysMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    private const string FIELD_DEFINITION_TRANSLATION_TABLE = 'ibexa_content_type_field_definition_ml';

    /** The ON DELETE CASCADE foreign key {@see FOREIGN_KEYS} replaces on {@see FIELD_DEFINITION_TRANSLATION_TABLE}. */
    private const string REPLACED_FOREIGN_KEY = 'ibexa_content_type_field_definition_ml_lang_fk';

    /**
     * Table => [column, foreign key added to it], as in sql/add-language-foreign-keys/<table>-*.sql.
     *
     * @var array<non-empty-string, list<array{non-empty-string, non-empty-string}>>
     */
    private const array FOREIGN_KEYS = [
        'ibexa_content' => [['initial_language_id', 'ibexa_content_initial_language_fk']],
        'ibexa_content_version' => [['initial_language_id', 'ibexa_content_version_initial_language_fk']],
        'ibexa_content_type' => [['initial_language_id', 'ibexa_content_type_initial_language_fk']],
        'ibexa_content_type_name' => [['language_id', 'ibexa_content_type_name_language_fk']],
        'ibexa_content_field' => [['language_id', 'ibexa_content_field_language_fk']],
        'ibexa_content_name' => [['language_id', 'ibexa_content_name_language_fk']],
        'ibexa_object_state' => [['default_language_id', 'ibexa_object_state_default_language_fk']],
        'ibexa_object_state_group' => [['default_language_id', 'ibexa_object_state_group_default_language_fk']],
        'ibexa_object_state_language' => [['language_id', 'ibexa_object_state_language_language_fk']],
        'ibexa_object_state_group_language' => [
            ['language_id', 'ibexa_object_state_group_language_language_fk'],
            ['real_language_id', 'ibexa_object_state_group_language_real_language_fk'],
        ],
        self::FIELD_DEFINITION_TRANSLATION_TABLE => [['language_id', 'ibexa_content_type_field_definition_ml_language_fk']],
    ];

    public function getDescription(): string
    {
        return 'Adds foreign keys from the language id columns to "ibexa_content_language"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::LANGUAGE_TABLE])) {
            return;
        }

        $missing = $this->findTablesMissingForeignKeys($schemaManager);

        if ($missing === []) {
            return;
        }

        $this->abortIfOrphanedLanguageIds(array_keys($missing));

        if ($this->isSqlite()) {
            $this->abortIf(
                (bool)$this->connection->fetchOne('PRAGMA foreign_keys'),
                'SQLite enforces foreign keys on this connection, so dropping the tables this migration rebuilds would delete the rows referencing them. Run it with PRAGMA foreign_keys = OFF.'
            );

            $this->addSqlFile(__DIR__ . '/sql/add-language-foreign-keys-sqlite.sql');

            return;
        }

        $platform = $this->isPostgreSQL() ? 'postgresql' : 'mysql';
        foreach (array_keys($missing) as $table) {
            $file = $table;
            if (
                $table === self::FIELD_DEFINITION_TRANSLATION_TABLE
                && !$schemaManager->introspectTableByUnquotedName($table)->hasForeignKey(self::REPLACED_FOREIGN_KEY)
            ) {
                $file .= '-without-replaced';
            }

            $this->addSqlFile(__DIR__ . "/sql/add-language-foreign-keys/{$file}-{$platform}.sql");
        }
    }

    /**
     * Tables that exist and lack their foreign keys.
     *
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return array<non-empty-string, list<array{non-empty-string, non-empty-string}>>
     */
    private function findTablesMissingForeignKeys(AbstractSchemaManager $schemaManager): array
    {
        $missing = [];

        foreach (self::FOREIGN_KEYS as $table => $foreignKeys) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            if (!$schemaManager->introspectTableByUnquotedName($table)->hasForeignKey($foreignKeys[0][1])) {
                $missing[$table] = $foreignKeys;
            }
        }

        return $missing;
    }

    /**
     * Refuses while a row holds a language id that isn't in "ibexa_content_language".
     *
     * @param list<non-empty-string> $tables
     */
    private function abortIfOrphanedLanguageIds(array $tables): void
    {
        $orphans = [];

        foreach ($tables as $table) {
            foreach (self::FOREIGN_KEYS[$table] as [$column]) {
                $count = (int)$this->connection->fetchOne(
                    "SELECT COUNT(*) FROM {$table} t WHERE NOT EXISTS (SELECT 1 FROM " . self::LANGUAGE_TABLE . " l WHERE l.id = t.{$column})"
                );

                if ($count > 0) {
                    $orphans[] = sprintf('%s.%s (%d)', $table, $column, $count);
                }
            }
        }

        $this->abortIf(
            $orphans !== [],
            sprintf(
                'These columns hold language ids missing from "%s", so their foreign keys can\'t be added: %s. Delete those rows, or point them at an existing language, before this migration.',
                self::LANGUAGE_TABLE,
                implode(', ', $orphans)
            )
        );
    }
}
