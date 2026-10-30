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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Drops the foreign keys from core's "language_id" columns to "ibexa_language" on MySQL and MariaDB,
 * so {@see NarrowLanguageIdColumnTypesMigration} can narrow both sides of them from BIGINT to INT.
 * MySQL and MariaDB refuse to change the type of either column of a foreign key, even with
 * FOREIGN_KEY_CHECKS=0. {@see RestoreLanguageIdForeignKeysMigration} re-creates the keys right
 * after the narrowing. PostgreSQL changes the types with the keys in place, and SQLite ignores
 * column types, so both keep them.
 *
 * Each key is dropped by its own statement, in sql/drop-foreign-key-<name>-mysql.sql, and only while
 * it exists and "ibexa_language.id" is still a BIGINT. MySQL and MariaDB commit every DDL statement
 * on its own, so a retry after a failure picks up where the previous run stopped.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema.
 */
final class DropLanguageIdForeignKeysMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_language';

    /** Foreign key names by table, each with sql/drop-foreign-key-<name>-mysql.sql. */
    public const array FOREIGN_KEYS = [
        'ibexa_content_translation' => 'ibexa_content_translation_language_fk',
        'ibexa_content_version_translation' => 'ibexa_content_version_translation_language_fk',
        'ibexa_url_alias_ml_translation' => 'ibexa_url_alias_ml_translation_language_fk',
        'ibexa_content_type_field_definition_ml' => 'ibexa_content_type_field_definition_ml_lang_fk',
    ];

    public function getDescription(): string
    {
        return 'Drops the foreign keys to "ibexa_language" before its id is narrowed to INT';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 06:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->isMySQL() && !$this->isMariaDB()) {
            return;
        }

        $schemaManager = $this->connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist([self::LANGUAGE_TABLE])
            || !$schemaManager->introspectTable(self::LANGUAGE_TABLE)->getColumn('id')->getType() instanceof BigIntType
        ) {
            return;
        }

        foreach (self::FOREIGN_KEYS as $table => $foreignKey) {
            if (
                $schemaManager->tablesExist([$table])
                && $schemaManager->introspectTable($table)->hasForeignKey($foreignKey)
            ) {
                $this->addSqlFile(__DIR__ . "/sql/drop-foreign-key-{$foreignKey}-mysql.sql");
            }
        }
    }
}
