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
 * Each column is checked and queued independently, and the backfill is unconditional whenever
 * anything is still missing: on MySQL, each `ALTER TABLE`/`UPDATE` auto-commits independently, so a
 * failure partway through (e.g. after adding "ibexa_content.always_available" but before adding it
 * to "ibexa_content_version", or before either backfill runs) must not make a retry mistake "the
 * first column already exists" for "this migration already fully ran" and silently skip the rest.
 * The backfill UPDATE is idempotent (derived only from "language_mask", which this migration never
 * modifies), so re-running it whenever anything else is still missing is always safe.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class AddContentAlwaysAvailableColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';
    private const CONTENT_VERSION_TABLE = 'ibexa_content_version';
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

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE, self::CONTENT_VERSION_TABLE])) {
            return;
        }

        $hasContentColumn = $schemaManager->introspectTable(self::CONTENT_TABLE)
            ->hasColumn(self::ALWAYS_AVAILABLE_COLUMN);
        $hasContentVersionColumn = $schemaManager->introspectTable(self::CONTENT_VERSION_TABLE)
            ->hasColumn(self::ALWAYS_AVAILABLE_COLUMN);

        if ($hasContentColumn && $hasContentVersionColumn) {
            // Already fully applied - avoid an unconditional full-table backfill re-scan once this
            // migration has genuinely completed.
            return;
        }

        if (!$hasContentColumn) {
            $this->addSql($this->buildAddColumnSql(self::CONTENT_TABLE));
        }

        if (!$hasContentVersionColumn) {
            $this->addSql($this->buildAddColumnSql(self::CONTENT_VERSION_TABLE));
        }

        $this->addSql($this->buildBackfillSql(self::CONTENT_TABLE));
        $this->addSql($this->buildBackfillSql(self::CONTENT_VERSION_TABLE));
    }

    private function buildAddColumnSql(string $table): string
    {
        $columnDefinition = match (true) {
            $this->isMySQL() => "TINYINT(1) DEFAULT '0' NOT NULL",
            $this->isPostgreSQL() => "BOOLEAN DEFAULT 'false' NOT NULL",
            default => "BOOLEAN DEFAULT '0' NOT NULL",
        };

        return "ALTER TABLE {$table} ADD COLUMN " . self::ALWAYS_AVAILABLE_COLUMN . " {$columnDefinition}";
    }

    private function buildBackfillSql(string $table): string
    {
        $trueLiteral = $this->isPostgreSQL() ? 'true' : '1';

        return "UPDATE {$table} SET " . self::ALWAYS_AVAILABLE_COLUMN . " = {$trueLiteral} WHERE (language_mask & 1) = 1";
    }
}
