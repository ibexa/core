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
 * Fixes 3 stale legacy identifier values ("ez_lock"/"ezstring") left over from before the
 * 5.0 renames, on the tables RenameSchemaTo5_0Migration renames them onto. Split out of that
 * migration (rather than guarded by its schema-presence check) because this is a data fix,
 * not a schema change: an install that built its schema via schema.yaml directly already has
 * the renamed tables (so RenameSchemaTo5_0Migration skips), but its actual row data may still
 * hold the old identifier strings, since schema.yaml only defines structure, not content.
 * Runs unconditionally -- each UPDATE is a no-op via its own WHERE clause once already fixed,
 * and by running strictly after RenameSchemaTo5_0Migration (same target version, later
 * creation date), the tables it touches are always at their final names by the time this
 * runs, whether the rename happened moments earlier in this same run or already existed.
 */
final class FixLegacyIdentifiersMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    public function getDescription(): string
    {
        return 'Fixes stale "ez_lock"/"ezstring" legacy identifier values left over from the 5.0 renames';
    }

    public static function getTargetVersion(): string
    {
        return '5.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-07-20 00:00:01');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/fix-legacy-identifiers-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/fix-legacy-identifiers-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/fix-legacy-identifiers-sqlite.sql');
        }
    }
}
