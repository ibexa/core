<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\IntegerType;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Re-creates, on MySQL and MariaDB, the foreign keys to "ibexa_language" that
 * {@see DropLanguageIdForeignKeysMigration} dropped so {@see NarrowLanguageIdColumnTypesMigration}
 * could narrow their columns.
 *
 * Each key is added by its own statement, in sql/add-foreign-key-<name>-mysql.sql, and only while it's
 * missing and "ibexa_language.id" is an INT. Adding a key fails when a row points at a language that
 * doesn't exist, so a retry after cleaning those rows up adds only the keys still missing.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema.
 */
final class RestoreLanguageIdForeignKeysMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_language';

    public function getDescription(): string
    {
        return 'Re-creates the foreign keys to "ibexa_language" after its id was narrowed to INT';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 06:00:03');
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
            || !$schemaManager->introspectTable(self::LANGUAGE_TABLE)->getColumn('id')->getType() instanceof IntegerType
        ) {
            return;
        }

        foreach (DropLanguageIdForeignKeysMigration::FOREIGN_KEYS as $table => $foreignKey) {
            if (
                $schemaManager->tablesExist([$table])
                && !$schemaManager->introspectTable($table)->hasForeignKey($foreignKey)
            ) {
                $this->addSqlFile(__DIR__ . "/sql/add-foreign-key-{$foreignKey}-mysql.sql");
            }
        }
    }
}
