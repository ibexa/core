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
 * BackfillLanguageTranslationsMigration (which runs earlier in the same sequence) populates
 * ibexa_content_translation/ibexa_content_version_translation/ibexa_url_alias_ml_translation from
 * the existing mask data before this migration runs, so a standard `doctrine:migrations:migrate`
 * upgrade needs no separate manual step. abortIfTranslationsNotBackfilled() is a defensive check
 * against that having been skipped or interrupted (e.g. a manual/partial migration run) - it is not
 * the primary mechanism. The `ibexa:languages:backfill-translations`/`ibexa:languages:verify-translations`
 * commands remain available for a dry-run preview or manual repair.
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
        return new DateTimeImmutable('2026-08-09 00:00:05');
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

        $this->abortIfTranslationsNotBackfilled();

        if ($this->isMySQL()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-postgresql.sql');
        } elseif ($this->isSqlite()) {
            $this->addSqlFile(__DIR__ . '/sql/drop-language-bitmask-columns-sqlite.sql');
        }
    }

    /**
     * Refuses to drop the mask columns if any row still carrying a real (non-always-available)
     * language bit has no corresponding row in the relational replacement it should have been
     * backfilled into - i.e. `ibexa:languages:backfill-translations` was never run, or didn't
     * finish, for this table. Once the mask columns are gone the mask data is unrecoverable, so
     * this check is deliberately a hard abort rather than a warning.
     */
    private function abortIfTranslationsNotBackfilled(): void
    {
        $checks = [
            'ibexa_content' => ['ibexa_content_translation', 'content_id'],
            'ibexa_content_version' => ['ibexa_content_version_translation', 'content_version_id'],
            'ibexa_url_alias_ml' => ['ibexa_url_alias_ml_translation', null],
        ];

        foreach ($checks as $maskTable => [$translationTable, $idColumn]) {
            $maskColumn = $maskTable === 'ibexa_url_alias_ml' ? 'lang_mask' : 'language_mask';

            if ($idColumn !== null) {
                $joinCondition = "t.{$idColumn} = m.id";
            } else {
                // ibexa_url_alias_ml's primary key is (parent, text_md5), not a single "id" column.
                $joinCondition = 't.parent = m.parent AND t.text_md5 = m.text_md5';
            }

            $missingCount = (int)$this->connection->fetchOne(
                "SELECT COUNT(*) FROM {$maskTable} m
                 WHERE m.{$maskColumn} > 1
                 AND NOT EXISTS (SELECT 1 FROM {$translationTable} t WHERE {$joinCondition})"
            );

            $this->abortIf(
                $missingCount > 0,
                "Refusing to drop \"{$maskColumn}\" from \"{$maskTable}\": {$missingCount} row(s) carry a " .
                "real language bit with no matching row in \"{$translationTable}\" - run " .
                '"ibexa:languages:backfill-translations" (and confirm ' .
                '"ibexa:languages:verify-translations" reports clean) before this migration.'
            );
        }
    }
}
