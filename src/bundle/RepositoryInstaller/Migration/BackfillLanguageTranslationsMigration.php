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

        $this->addChunkedSql('ibexa_content', 'language_mask', 'id', 'backfill-content-always-available.sql');
        $this->addChunkedSql('ibexa_content_version', 'language_mask', 'id', 'backfill-content-version-always-available.sql');
        $this->addChunkedSql('ibexa_content', 'language_mask', 'id', $this->getPlatformSqlFile('backfill-content-translation'));
        $this->addChunkedSql('ibexa_content_version', 'language_mask', 'id', $this->getPlatformSqlFile('backfill-content-version-translation'));
        $this->addChunkedSql('ibexa_url_alias_ml', 'lang_mask', 'id', $this->getPlatformSqlFile('backfill-url-alias-ml-translation'));
    }

    private function getPlatformSqlFile(string $name): string
    {
        return sprintf('%s-%s.sql', $name, match (true) {
            $this->isSqlite() => 'sqlite',
            $this->isPostgreSQL() => 'postgresql',
            default => 'mysql',
        });
    }

    /**
     * @param non-empty-string $sourceTable
     */
    private function addChunkedSql(
        string $sourceTable,
        string $maskColumn,
        string $pkColumn,
        string $sqlFile
    ): void {
        $schemaManager = $this->connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist([$sourceTable])
            || !$schemaManager->introspectTableByUnquotedName($sourceTable)->hasColumn($maskColumn)
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

        $sql = $this->readSqlFile(__DIR__ . '/sql/' . $sqlFile);

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
}
