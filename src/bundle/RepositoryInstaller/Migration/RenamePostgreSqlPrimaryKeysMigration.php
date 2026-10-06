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

/**
 * Renames the primary keys of the core tables on PostgreSQL after the tables, as a fresh 5.0
 * install names them.
 *
 * PostgreSQL names a primary key "<table>_pkey" when it creates the table, and keeps that name when
 * the table is renamed. {@see RenameSchemaTo5_0Migration} renames the tables, so their primary keys
 * kept their 4.6 names, such as "ezcontentobject_pkey" on "ibexa_content". ibexa/installer's 4.6 to
 * 5.0 upgrade script doesn't rename them either. MySQL and MariaDB name every primary key PRIMARY,
 * and SQLite doesn't name them.
 *
 * A database has either all of the 4.6 names or none of them, so only the first one is checked. The
 * statements are in sql/rename-primary-keys-postgresql.sql.
 */
final class RenamePostgreSqlPrimaryKeysMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Renames the PostgreSQL primary keys of the core tables after the tables';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-06 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->isPostgreSQL()) {
            return;
        }

        $hasOldName = $this->connection->fetchOne(
            "SELECT 1 FROM pg_constraint WHERE conrelid = to_regclass('ibexa_binary_file') AND conname = 'ezbinaryfile_pkey'"
        ) !== false;
        if ($hasOldName) {
            $this->addSqlFile(__DIR__ . '/sql/rename-primary-keys-postgresql.sql');
        }
    }
}
