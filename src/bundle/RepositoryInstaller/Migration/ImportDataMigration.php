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
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

final class ImportDataMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Imports the core Ibexa bootstrap data';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-16 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-sqlite.sql');
        }
    }
}
