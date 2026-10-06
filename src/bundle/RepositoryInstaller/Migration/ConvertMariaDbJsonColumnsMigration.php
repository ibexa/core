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
 * Converts the JSON columns a 4.6 install created as LONGTEXT on MariaDB to JSON, as 5.0 creates them.
 *
 * On MariaDB, 4.6's {@see InstallSchemaMigration} creates them as LONGTEXT, as Doctrine DBAL 2.13
 * does, and 5.0's as JSON, as DBAL 3 does: MariaDB stores that as LONGTEXT in utf8mb4_bin, with a
 * json_valid() check. It's the same migration, so it doesn't run again on a 4.6 database upgraded
 * to 5.0. ibexa/installer's 4.6 to 5.0 upgrade script doesn't convert these columns either.
 */
final class ConvertMariaDbJsonColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    /**
     * Each column's definition in 5.0's install-schema-json-tables.mariadb.sql.
     */
    private const COLUMNS = [
        'ibexa_setting' => ['value' => "JSON NOT NULL COMMENT '(DC2Type:json)'"],
    ];

    public function getDescription(): string
    {
        return 'Converts the core JSON columns a 4.6 install created as LONGTEXT on MariaDB to JSON';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-06 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->isMariaDB()) {
            return;
        }

        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $definition) {
                if (!$this->isJsonColumn($table, $column)) {
                    $this->addSql(sprintf('ALTER TABLE %s MODIFY %s %s', $table, $column, $definition));
                }
            }
        }
    }

    /**
     * MariaDB stores a JSON column as LONGTEXT, with a json_valid() check named after the column.
     */
    private function isJsonColumn(string $table, string $column): bool
    {
        return $this->connection->fetchOne(
            'SELECT 1 FROM information_schema.CHECK_CONSTRAINTS'
            . ' WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?'
            . " AND CHECK_CLAUSE LIKE 'json_valid(%'",
            [$table, $column]
        ) !== false;
    }
}
