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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;

/**
 * Renames "ibexa_content_language" to "ibexa_language". The table holds every language of the
 * system (content, object states, content types, URL aliases), not just content languages.
 * {@see Gateway::CONTENT_LANGUAGE_TABLE} is the only place application code names this table.
 *
 * MySQL, MariaDB, PostgreSQL and SQLite keep the foreign keys pointing at a renamed table, so none
 * of them need to be re-created. The statement is in sql/rename-language-table-*.sql. It's skipped
 * once the table has its new name, as on a fresh install.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema.
 */
final class RenameLanguageTableMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string OLD_LANGUAGE_TABLE = 'ibexa_content_language';
    private const string LANGUAGE_TABLE = 'ibexa_language';

    public function getDescription(): string
    {
        return 'Renames "ibexa_content_language" to "ibexa_language"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 06:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist([self::OLD_LANGUAGE_TABLE])
            || $schemaManager->tablesExist([self::LANGUAGE_TABLE])
        ) {
            return;
        }

        if ($this->isMySQL() || $this->isMariaDB()) {
            $this->addSqlFile(__DIR__ . '/sql/rename-language-table-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/rename-language-table-postgresql.sql');
        } else {
            $this->addSqlFile(__DIR__ . '/sql/rename-language-table-sqlite.sql');
        }
    }
}
