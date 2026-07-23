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

final class InstallSchemaMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Creates the core Ibexa database schema';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-16 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        // Checks the final, post-rename table name (not "ezcontentobject", which this
        // migration creates): an install that built its schema via schema.yaml (rather than
        // through this migration) never has "ezcontentobject" -- it already has
        // "ibexa_content" directly.
        $this->skipIf($schema->hasTable('ibexa_content'), 'Schema already migrated: table "ibexa_content" already exists.');

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/install-schema-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/install-schema-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/install-schema-sqlite.sql');
        }
    }
}
