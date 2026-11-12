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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Backfills "always_available" and the language translation tables from the legacy language bitmask columns.
 */
final class BackfillLanguageTranslationsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const int BATCH_SIZE = 5000;

    public function getDescription(): string
    {
        return 'Backfills "always_available" and the language translation tables from the legacy language bitmask columns';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 02:00:00');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        if (!$this->connection->createSchemaManager()->tablesExist(['ibexa_content_translation'])) {
            // AddLanguageTranslationTablesMigration hasn't run yet (or "ibexa_content" itself
            // doesn't exist - a project-only install without core's own content tables).
            return;
        }

        $this->backfillAlwaysAvailable('ibexa_content');
        $this->backfillAlwaysAvailable('ibexa_content_version');

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

    private function backfillAlwaysAvailable(string $sourceTable): void
    {
        $this->addChunkedSql(
            $sourceTable,
            'language_mask',
            'id',
            "UPDATE {$sourceTable} SET always_available = ((language_mask & 1) = 1)
             WHERE id BETWEEN :from AND :to AND always_available <> ((language_mask & 1) = 1)"
        );
    }

    private function backfillTable(
        string $sourceTable,
        string $maskColumn,
        string $pkColumn,
        string $insertSqlTemplate
    ): void {
        $this->addChunkedSql(
            $sourceTable,
            $maskColumn,
            $pkColumn,
            sprintf($insertSqlTemplate, $this->insertIgnoreKeyword(), $this->onConflictClause())
        );
    }

    private function addChunkedSql(
        string $sourceTable,
        string $maskColumn,
        string $pkColumn,
        string $sql
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

        for ($from = $minId; $from <= $maxId; $from += self::BATCH_SIZE) {
            $to = min($from + self::BATCH_SIZE - 1, $maxId);
            $this->addSql(
                $sql,
                ['from' => $from, 'to' => $to],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }

    private function insertIgnoreKeyword(): string
    {
        if ($this->isMySQL() || $this->isMariaDB()) {
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
