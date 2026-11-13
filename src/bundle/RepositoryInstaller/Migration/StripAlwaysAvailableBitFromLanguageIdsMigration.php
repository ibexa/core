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
 * Strips the legacy "always available" bit (bit 0) from the "language_id" columns that the bitmask
 * scheme folded it into.
 *
 * Each row is checked against the language it names: the locale columns of fields, names and
 * content type names, "real_language_id" of object state groups, and the state's default language
 * for object states, which always have a translation in it. A row whose id already belongs to its
 * own language is left alone, so the rows of a language created with a sequential id before this
 * runs keep it, and a rerun changes nothing.
 *
 * Legacy eZ Publish can leave an object state with two rows for one language, one with the bit and
 * one without: changing a state's default language there inserted rows with the flipped bit instead
 * of updating the existing ones (eZ issue 19169, fixed only for object state groups). Stripping the
 * bit would collide such twins on the primary key, so the stale one is removed first, by the rule
 * legacy's own group fix used: the state's default language keeps the row with the bit, every other
 * language the row without it.
 *
 * Chunked and non-transactional for the same reasons as BackfillLanguageTranslationsMigration.
 */
final class StripAlwaysAvailableBitFromLanguageIdsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const int BATCH_SIZE = 5000;

    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * Table => [locale column, column to chunk by, or null for tables small enough for a single statement].
     *
     * @var array<string, array{0: string, 1: string|null}>
     */
    private const array LOCALE_COLUMNS = [
        'ibexa_content_field' => ['language_code', 'id'],
        'ibexa_content_name' => ['content_translation', 'contentobject_id'],
        'ibexa_content_type_name' => ['language_locale', null],
    ];

    public function getDescription(): string
    {
        return 'Strips the legacy "always available" bit from language_id columns';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 07:00:00');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::LANGUAGE_TABLE])) {
            return;
        }

        if ($schemaManager->tablesExist(['ibexa_object_state', 'ibexa_object_state_language'])) {
            $this->addObjectStateTranslationStrip();
        }

        if ($schemaManager->tablesExist(['ibexa_object_state_group_language'])) {
            $this->addSql(
                'UPDATE ibexa_object_state_group_language SET language_id = real_language_id WHERE language_id <> real_language_id'
            );
        }

        foreach (self::LOCALE_COLUMNS as $table => [$localeColumn, $chunkColumn]) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $updateSql = "UPDATE {$table} SET language_id = language_id - 1 WHERE language_id % 2 = 1"
                . ' AND NOT EXISTS (SELECT 1 FROM ' . self::LANGUAGE_TABLE . " l WHERE l.id = {$table}.language_id AND l.locale = {$table}.{$localeColumn})";
            if ($chunkColumn === null) {
                $this->addSql($updateSql);
                continue;
            }

            $this->addChunkedSql($table, $chunkColumn, $updateSql);
        }
    }

    private function addObjectStateTranslationStrip(): void
    {
        if ($this->isMySQL() || $this->isMariaDB()) {
            $this->addSqlFile(__DIR__ . '/sql/remove-stale-object-state-translations-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/remove-stale-object-state-translations-postgresql.sql');
        } else {
            $this->addSqlFile(__DIR__ . '/sql/remove-stale-object-state-translations-sqlite.sql');
        }

        $this->addSql(
            'UPDATE ibexa_object_state_language SET language_id = language_id - 1 WHERE language_id % 2 = 1'
            . ' AND NOT EXISTS (SELECT 1 FROM ' . self::LANGUAGE_TABLE . ' l WHERE l.id = ibexa_object_state_language.language_id)'
        );

        $defaultTranslations = $this->connection->fetchAllAssociative(
            'SELECT t.contentobject_state_id, t.language_id
             FROM ibexa_object_state_language t
                 JOIN ibexa_object_state s ON s.id = t.contentobject_state_id
                 JOIN ' . self::LANGUAGE_TABLE . ' l ON l.id = t.language_id
             WHERE t.language_id % 2 = 1
                 AND t.language_id - 1 = s.default_language_id
                 AND NOT EXISTS (
                     SELECT 1 FROM ibexa_object_state_language d
                     WHERE d.contentobject_state_id = t.contentobject_state_id AND d.language_id = s.default_language_id
                 )'
        );
        foreach ($defaultTranslations as $row) {
            $this->addSql(
                'UPDATE ibexa_object_state_language SET language_id = :to WHERE contentobject_state_id = :state AND language_id = :from',
                ['to' => (int)$row['language_id'] - 1, 'state' => (int)$row['contentobject_state_id'], 'from' => (int)$row['language_id']],
                ['to' => ParameterType::INTEGER, 'state' => ParameterType::INTEGER, 'from' => ParameterType::INTEGER]
            );
        }
    }

    private function addChunkedSql(
        string $table,
        string $chunkColumn,
        string $updateSql
    ): void {
        $range = $this->connection->fetchAssociative(
            "SELECT MIN({$chunkColumn}) AS min_id, MAX({$chunkColumn}) AS max_id FROM {$table}"
        );
        if ($range === false || $range['min_id'] === null) {
            return;
        }

        $maxId = (int)$range['max_id'];
        for ($from = (int)$range['min_id']; $from <= $maxId; $from += self::BATCH_SIZE) {
            $this->addSql(
                "{$updateSql} AND {$chunkColumn} BETWEEN :from AND :to",
                ['from' => $from, 'to' => min($from + self::BATCH_SIZE - 1, $maxId)],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }
}
