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
 * Adds the missing "link" index to "ezurlalias_ml" (IBX-8125, originally shipped via installer's
 * upgrade/db/ibexa-4.6.20-to-4.6.21.sql). Split out of InstallSchemaMigration because an install
 * that already had this table before the index was introduced is guarded against re-running the
 * baseline. Guarded on the index, not the table, and runs unconditionally otherwise -- a no-op
 * once already applied.
 */
final class AddUrlAliasMlLinkIndexMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Adds the missing "link" index to "ezurlalias_ml"';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-29 00:00:02');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if ($schema->getTable('ezurlalias_ml')->hasIndex('ezurlalias_ml_link')) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-urlalias-ml-link-index-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-urlalias-ml-link-index-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-urlalias-ml-link-index-sqlite.sql');
        }
    }
}
