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
 * Adds "language_id" and "is_main_and_always_available" to "ibexa_search_object_word_link", backfilled from "language_mask".
 */
final class AddSearchObjectWordLinkLanguageIdColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string TABLE = 'ibexa_search_object_word_link';
    private const string LANGUAGE_ID_COLUMN = 'language_id';
    private const string IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN = 'is_main_and_always_available';
    private const string LANGUAGE_MASK_COLUMN = 'language_mask';
    private const int BATCH_SIZE = 5000;

    public function getDescription(): string
    {
        return 'Adds "language_id" and "is_main_and_always_available" columns to "ibexa_search_object_word_link", backfilled from the language mask';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 03:00:00');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::TABLE])) {
            return;
        }

        $table = $schemaManager->introspectTableByUnquotedName(self::TABLE);

        if (!$table->hasColumn(self::LANGUAGE_ID_COLUMN)) {
            $this->addSql($this->buildAddLanguageIdColumnSql());
        }

        if (!$table->hasColumn(self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN)) {
            $this->addSql($this->buildAddAlwaysAvailableColumnSql());
        }

        if (!$table->hasColumn(self::LANGUAGE_MASK_COLUMN)) {
            return;
        }

        $range = $this->connection->fetchAssociative('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM ' . self::TABLE);
        if ($range === false || $range['min_id'] === null) {
            return;
        }

        $languageId = '(' . self::LANGUAGE_MASK_COLUMN . ' & -2)';
        $isMainAndAlwaysAvailable = '((' . self::LANGUAGE_MASK_COLUMN . ' & 1) = 1)';
        $sql = 'UPDATE ' . self::TABLE
            . ' SET ' . self::LANGUAGE_ID_COLUMN . " = {$languageId}, "
            . self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN . " = {$isMainAndAlwaysAvailable}"
            . ' WHERE id BETWEEN :from AND :to'
            . ' AND (' . self::LANGUAGE_ID_COLUMN . " <> {$languageId} OR "
            . self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN . " <> {$isMainAndAlwaysAvailable})";

        $maxId = (int)$range['max_id'];
        for ($from = (int)$range['min_id']; $from <= $maxId; $from += self::BATCH_SIZE) {
            $this->addSql(
                $sql,
                ['from' => $from, 'to' => min($from + self::BATCH_SIZE - 1, $maxId)],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }

    private function buildAddLanguageIdColumnSql(): string
    {
        // BIGINT, like the other columns holding bitmask-era language ids: up to 2^62
        $columnDefinition = $this->isMySQL() || $this->isMariaDB() ? "BIGINT DEFAULT '0' NOT NULL" : 'BIGINT DEFAULT 0 NOT NULL';

        return 'ALTER TABLE ' . self::TABLE . ' ADD COLUMN ' . self::LANGUAGE_ID_COLUMN . " {$columnDefinition}";
    }

    private function buildAddAlwaysAvailableColumnSql(): string
    {
        $columnDefinition = match (true) {
            $this->isMySQL(), $this->isMariaDB() => "TINYINT(1) DEFAULT '0' NOT NULL",
            $this->isPostgreSQL() => "BOOLEAN DEFAULT 'false' NOT NULL",
            default => "BOOLEAN DEFAULT '0' NOT NULL",
        };

        return 'ALTER TABLE ' . self::TABLE . ' ADD COLUMN ' . self::IS_MAIN_AND_ALWAYS_AVAILABLE_COLUMN .
            " {$columnDefinition}";
    }
}
