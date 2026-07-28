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
 * Adds performance indexes to "ezcontentobject_link", "ezcontentclass_attribute",
 * "ezurl_object_link" and "ezcontentobject_attribute" (originally shipped via installer's
 * upgrade/db/ibexa-4.5.1-to-4.5.2.sql). Split out of InstallSchemaMigration because an install
 * that already had these tables before the indexes were introduced is guarded against re-running
 * the baseline. Guarded on the index, not the table, and runs unconditionally otherwise -- a
 * no-op once already applied.
 */
final class AddContentPerformanceIndexesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Adds performance indexes to ezcontentobject_link, ezcontentclass_attribute, ezurl_object_link and ezcontentobject_attribute';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-29 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if ($schema->getTable('ezcontentobject_link')->hasIndex('ezco_link_cca_id')) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-performance-indexes-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-performance-indexes-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-performance-indexes-sqlite.sql');
        }
    }
}
