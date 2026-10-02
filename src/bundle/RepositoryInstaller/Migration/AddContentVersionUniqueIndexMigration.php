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
 * Core's part of the 5.0 to 6.0 schema change (IBX-12078), converted from ibexa/installer's
 * upgrade/db/{mysql,postgresql}/ibexa-5.0.latest-to-6.0.0.sql: a content version always belongs to
 * a content item, and a content item has at most one version with a given number. Versions without
 * a content item are orphans and are removed first, or the constraints couldn't be added.
 */
final class AddContentVersionUniqueIndexMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Makes "ibexa_content_version.contentobject_id" mandatory and unique per version number';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-09-28 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (
            !$schema->hasTable('ibexa_content_version')
            || $schema->getTable('ibexa_content_version')->hasIndex('ibexa_content_version_coid_version_unique')
        ) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-version-unique-index-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-version-unique-index-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-version-unique-index-sqlite.sql');
        }
    }
}
