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
 * Adds "always_available" boolean columns to "ibexa_content" and "ibexa_content_version",
 * backfilled from bit 0 of their "language_mask" column (step 1 of the language bitmask
 * migration). "language_mask" itself is left untouched by this migration - only the
 * always-available flag moves off the bitmask; the mask keeps carrying language-membership bits
 * until later steps introduce dedicated translation tables.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class AddContentAlwaysAvailableColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';
    private const ALWAYS_AVAILABLE_COLUMN = 'always_available';

    public function getDescription(): string
    {
        return 'Adds "always_available" columns to "ibexa_content" and "ibexa_content_version", backfilled from the language mask';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        if ($schemaManager->introspectTable(self::CONTENT_TABLE)->hasColumn(self::ALWAYS_AVAILABLE_COLUMN)) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-always-available-columns-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-always-available-columns-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-content-always-available-columns-sqlite.sql');
        }
    }
}
