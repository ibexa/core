<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\DoctrineMigrations\Migration\SqlPlatform;

/**
 * Backfills "ibexa_content_translation", "ibexa_content_version_translation" and
 * "ibexa_url_alias_ml_translation" from the legacy "language_mask"/"lang_mask" bitmask columns
 * (step 2 of the language bitmask migration), so DropLanguageBitmaskColumnsMigration can safely
 * drop those columns afterward.
 *
 * Chunked by primary-key range via repeated {@see addSql()} calls, and executed non-transactionally
 * ({@see isTransactional()}), rather than as a single `INSERT ... SELECT` - "ibexa_content"/
 * "ibexa_content_version"/"ibexa_url_alias_ml" can hold tens of millions of rows on a mature
 * install, and one giant statement in one transaction risks an enormous undo log/WAL. With each
 * chunk committed independently, a failed/interrupted run only has to redo the (idempotent, cheap -
 * "INSERT IGNORE"/"ON CONFLICT DO NOTHING") already-completed chunks on retry, not roll everything
 * back. This still uses `addSql()` rather than executing directly, so `--dry-run` previews it like
 * any other migration instead of silently writing anyway.
 *
 * The decomposition needs no PHP-side bit-walking or a recursive CTE: "ibexa_content_language"
 * already contains every valid bit value, so joining against it with a bitwise AND does the
 * decomposition in SQL directly.
 *
 * The manual `ibexa:languages:backfill-translations`/`ibexa:languages:verify-translations` commands
 * remain available for a dry-run preview or manual repair, but running them separately is no longer
 * a required step before the drop migration - this migration already does the same job as part of
 * the standard migration sequence, during the same maintenance window.
 *
 * Guarded via the connection's schema manager rather than the injected $schema, because
 * TaggedMigrationsRunner (the "ibexa:install" path) invokes up() with an empty Schema, so
 * $schema->hasTable()/hasColumn() would always report false there.
 */
final class BackfillLanguageTranslationsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const BATCH_SIZE = 5000;

    public function getDescription(): string
    {
        return 'Backfills the language translation tables from the legacy language bitmask columns';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-08-09 00:00:02');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->connection->createSchemaManager()->tablesExist(['ibexa_content_translation'])) {
            // AddLanguageTranslationTablesMigration hasn't run yet (or "ibexa_content" itself
            // doesn't exist - a project-only install without core's own content tables).
            return;
        }

        $this->backfillTable(
            'ibexa_content',
            'language_mask',
            'id',
            'INSERT %s INTO ibexa_content_translation (content_id, language_id)
             SELECT c.id, l.id FROM ibexa_content c
             JOIN ibexa_content_language l ON (c.language_mask & l.id) = l.id
             WHERE c.id BETWEEN :from AND :to %s'
        );

        $this->backfillTable(
            'ibexa_content_version',
            'language_mask',
            'id',
            'INSERT %s INTO ibexa_content_version_translation (content_version_id, language_id)
             SELECT v.id, l.id FROM ibexa_content_version v
             JOIN ibexa_content_language l ON (v.language_mask & l.id) = l.id
             WHERE v.id BETWEEN :from AND :to %s'
        );

        $this->backfillTable(
            'ibexa_url_alias_ml',
            'lang_mask',
            'parent',
            'INSERT %s INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id)
             SELECT u.parent, u.text_md5, l.id FROM ibexa_url_alias_ml u
             JOIN ibexa_content_language l ON (u.lang_mask & l.id) = l.id
             WHERE u.parent BETWEEN :from AND :to %s'
        );
    }

    private function backfillTable(
        string $sourceTable,
        string $maskColumn,
        string $pkColumn,
        string $insertSqlTemplate
    ): void {
        $schemaManager = $this->connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist([$sourceTable])
            || !$schemaManager->introspectTable($sourceTable)->hasColumn($maskColumn)
        ) {
            // Column already dropped (re-running after a prior upgrade already completed this
            // step), or a fresh install whose schema.yaml never had it.
            return;
        }

        // MIN()/MAX() rather than COUNT()-based emptiness + a hardcoded lower bound of 1: some of
        // these tables (e.g. "ibexa_url_alias_ml" for root-level aliases) legitimately use 0 as a
        // valid primary-key value, so neither "MAX() === 0" nor an assumed start of 1 is safe here.
        $range = $this->connection->fetchAssociative(
            "SELECT MIN({$pkColumn}) AS min_id, MAX({$pkColumn}) AS max_id FROM {$sourceTable}"
        );
        if ($range === false || $range['min_id'] === null) {
            return;
        }

        $minId = (int)$range['min_id'];
        $maxId = (int)$range['max_id'];

        $insertSql = sprintf($insertSqlTemplate, $this->insertIgnoreKeyword(), $this->onConflictClause());

        for ($from = $minId; $from <= $maxId; $from += self::BATCH_SIZE) {
            $to = min($from + self::BATCH_SIZE - 1, $maxId);
            $this->addSql(
                $insertSql,
                ['from' => $from, 'to' => $to],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }

    private function insertIgnoreKeyword(): string
    {
        if ($this->isMySQL()) {
            return 'IGNORE';
        }
        if ($this->isSqlite()) {
            return 'OR IGNORE';
        }

        return '';
    }

    /**
     * Appended after the SELECT to make the insert idempotent on platforms that don't support
     * "INSERT IGNORE" (MySQL is handled via insertIgnoreKeyword() instead, since its "ON DUPLICATE
     * KEY" clause needs different syntax for an INSERT ... SELECT).
     */
    private function onConflictClause(): string
    {
        if ($this->isPostgreSQL()) {
            return 'ON CONFLICT DO NOTHING';
        }

        return '';
    }
}
