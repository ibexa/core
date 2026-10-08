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
 * Converts the JSON columns an earlier install created on MariaDB to JSON without a type comment,
 * as 6.0 creates them.
 *
 * On MariaDB, {@see InstallSchemaMigration} creates them as LONGTEXT with a "(DC2Type:json)"
 * comment on 4.6, as Doctrine DBAL 2.13 does, as JSON with that comment on 5.0, as DBAL 3 does,
 * and as JSON without it on 6.0, as DBAL 4 does. It's the same migration, so it doesn't run again
 * on a database upgraded to 6.0, and ibexa/installer's upgrade scripts don't change these columns.
 *
 * The statements are in sql/convert-json-columns-to-6-0-mariadb.sql. Running them on columns
 * already in that shape changes nothing, so there's no check first.
 */
final class ConvertMariaDbJsonColumnsTo6_0Migration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Converts the core JSON columns an earlier install created on MariaDB to JSON without a type comment';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
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

        $this->addSqlFile(__DIR__ . '/sql/convert-json-columns-to-6-0-mariadb.sql');
    }
}
