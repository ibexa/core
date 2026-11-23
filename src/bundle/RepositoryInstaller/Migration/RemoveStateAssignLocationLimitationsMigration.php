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
 * Removes the Location and Subtree limitations from "state/assign" policies, and then every
 * limitation value left without its limitation (IBX-6592, originally shipped via installer's
 * upgrade/db/ibexa-4.6.3-to-4.6.4.sql). Since 4.6.4 these limitations can't be set on that
 * policy anymore, so this only changes older policies -- a no-op once applied. Skipped once
 * "ezpolicy_limitation" is renamed (5.0): a database reaches 5.0 only through the latest 4.6,
 * which runs this.
 */
final class RemoveStateAssignLocationLimitationsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Removes the Location and Subtree limitations from "state/assign" policies';
    }

    public static function getTargetVersion(): string
    {
        return '4.6.4';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-29 00:00:00');
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$schema->hasTable('ezpolicy_limitation')) {
            return;
        }

        if ($this->isMySQL() || $this->isMariaDB()) {
            $this->addSqlFile(__DIR__ . '/sql/remove-state-assign-location-limitations-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/remove-state-assign-location-limitations-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/remove-state-assign-location-limitations-sqlite.sql');
        }
    }
}
