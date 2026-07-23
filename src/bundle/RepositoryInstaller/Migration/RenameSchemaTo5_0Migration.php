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

/**
 * Renames the legacy eZ Publish-style core database schema (ez*) to the Ibexa naming
 * scheme (ibexa_*) introduced in 5.0. This is the incremental diff on top of
 * InstallSchemaMigration's 4.6.0 baseline; a fresh 6.0 install runs both in sequence,
 * while an existing 4.6 install only needs this one to reach the 5.0/6.0 shape.
 */
final class RenameSchemaTo5_0Migration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Renames the legacy core database schema to the Ibexa naming scheme (introduced in 5.0)';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-20 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $this->skipIf($schema->hasTable('ibexa_content'), 'Schema already migrated: table "ibexa_content" already exists.');

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/rename-schema-to-5-0-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/rename-schema-to-5-0-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/rename-schema-to-5-0-sqlite.sql');
        }
    }
}
