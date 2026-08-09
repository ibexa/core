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
 * Adds an "is_always_available" boolean column to "ibexa_url_alias_ml", backfilled from bit 0 of
 * its "lang_mask" column (part of step 6 of the language bitmask migration). "lang_mask" itself is
 * left untouched by this migration - only the always-available flag moves off the bitmask; the mask
 * keeps carrying language-membership bits until Step 7 introduces
 * "ibexa_url_alias_ml_translation" writes/reads and drops the mask column entirely.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class AddUrlAliasAlwaysAvailableColumnMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const TABLE = 'ibexa_url_alias_ml';
    private const ALWAYS_AVAILABLE_COLUMN = 'is_always_available';

    public function getDescription(): string
    {
        return 'Adds "is_always_available" column to "ibexa_url_alias_ml", backfilled from the language mask';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:02');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return;
        }

        if ($schemaManager->introspectTable(self::TABLE)->hasColumn(self::ALWAYS_AVAILABLE_COLUMN)) {
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-url-alias-always-available-column-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-url-alias-always-available-column-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/add-url-alias-always-available-column-sqlite.sql');
        }
    }
}
