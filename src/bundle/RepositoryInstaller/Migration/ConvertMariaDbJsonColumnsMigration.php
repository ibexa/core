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
 *
 * The statements are in sql/convert-json-columns-mariadb.sql. They're skipped when every column
 * already has the json_valid() check, as on a database a 5.0 SchemaBuilderEvent install created.
 */
final class ConvertMariaDbJsonColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    /** The columns sql/convert-json-columns-mariadb.sql converts, by table. */
    private const JSON_COLUMNS = [
        'ibexa_setting' => 'value',
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

        if ($this->areColumnsConverted()) {
            return;
        }

        $this->addSqlFile(__DIR__ . '/sql/convert-json-columns-mariadb.sql');
    }

    /**
     * A converted column has the json_valid() check MariaDB gives a JSON column. It's looked up in
     * information_schema because Doctrine DBAL 3 can't tell: it goes by the "(DC2Type:json)" comment,
     * which the column has before the conversion too, and reports both as JSON.
     */
    private function areColumnsConverted(): bool
    {
        foreach (self::JSON_COLUMNS as $tableName => $columnName) {
            $hasJsonCheck = $this->connection->fetchOne(
                'SELECT 1 FROM information_schema.CHECK_CONSTRAINTS'
                . ' WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CHECK_CLAUSE = ?',
                [$tableName, sprintf('json_valid(`%s`)', $columnName)]
            ) !== false;

            if (!$hasJsonCheck) {
                return false;
            }
        }

        return true;
    }
}
