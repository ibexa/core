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

        // This migration inserts into "ezcontentobject" (the pre-rename name) since it runs
        // before the 5.0.0 rename. Migrations execute in a strict, deterministic order, so if
        // that table is missing here, this system never went through this migration at all --
        // it built its schema (and presumably its own bootstrap data) via schema.yaml directly,
        // straight to the final "ibexa_content" naming.
        // Two different "already done" signals, since "ezcontentobject" behaves
        // differently depending on the branch: on 5.0/6.0 it's renamed away to
        // "ibexa_content", so its outright absence means a legacy install already skipped
        // straight to that final name. On 4.6 it's the table's real, permanent name (never
        // renamed), so it always exists once the baseline has run -- there, only the actual
        // bootstrap row (the root content object always has id = 1) distinguishes "not yet
        // imported" from "already imported".
        if (!$schema->hasTable('ezcontentobject') || $this->connection->fetchOne('SELECT 1 FROM ezcontentobject WHERE id = 1') !== false) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/import-data-sqlite.sql');
        }
    }
}
