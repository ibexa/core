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
 * Drops the language bitmask columns ("language_mask"/"lang_mask") from every table that carried
 * one - the final step of the language bitmask migration. By this point nothing reads them:
 * always-available is a plain boolean column (Step 1), Content/Version/UrlAlias translations are
 * relational join tables (Steps 2-3, 6-7), Legacy Search's word index carries "language_id"
 * directly (Step 5), and ContentType's languages come from "ibexa_content_type_name" (Step 7).
 *
 * ObjectState/ObjectStateGroup's "language_mask" was never read anywhere to begin with - it
 * duplicated data already available via the "ibexa_object_state(_group)_language" join tables.
 *
 * IMPORTANT for installs upgrading from before this migration: this drop is only safe once
 * `ibexa:languages:backfill-translations` has populated ibexa_content_translation/
 * ibexa_content_version_translation/ibexa_url_alias_ml_translation from the existing mask data and
 * `ibexa:languages:verify-translations` reports zero drift - both commands remain available and
 * still read these columns for exactly that purpose, run them before this migration executes.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class DropLanguageBitmaskColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const CONTENT_TABLE = 'ibexa_content';
    private const LANGUAGE_MASK_COLUMN = 'language_mask';

    public function getDescription(): string
    {
        return 'Drops the language bitmask ("language_mask"/"lang_mask") columns, now that nothing reads them';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:03');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        if (!$schemaManager->introspectTable(self::CONTENT_TABLE)->hasColumn(self::LANGUAGE_MASK_COLUMN)) {
            // Already dropped (or a fresh install whose schema.yaml never had it).
            return;
        }

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-sqlite.sql');
        }
    }
}
