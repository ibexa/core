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
 * Adds an "is_always_available" column to "ibexa_url_alias_ml", backfilled from bit 0 of "lang_mask".
 */
final class AddUrlAliasAlwaysAvailableColumnMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string TABLE = 'ibexa_url_alias_ml';
    private const string ALWAYS_AVAILABLE_COLUMN = 'is_always_available';
    private const string LANGUAGE_MASK_COLUMN = 'lang_mask';
    private const int BATCH_SIZE = 5000;

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
        return new DateTimeImmutable('2026-10-19 04:00:00');
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

        $table = $schemaManager->introspectTable(self::TABLE);

        if (!$table->hasColumn(self::ALWAYS_AVAILABLE_COLUMN)) {
            $columnDefinition = match (true) {
                $this->isMySQL(), $this->isMariaDB() => "TINYINT(1) DEFAULT '0' NOT NULL",
                $this->isPostgreSQL() => "BOOLEAN DEFAULT 'false' NOT NULL",
                default => "BOOLEAN DEFAULT '0' NOT NULL",
            };

            $this->addSql(
                'ALTER TABLE ' . self::TABLE . ' ADD COLUMN ' . self::ALWAYS_AVAILABLE_COLUMN . " {$columnDefinition}"
            );
        }

        if (!$table->hasColumn(self::LANGUAGE_MASK_COLUMN)) {
            return;
        }

        $range = $this->connection->fetchAssociative('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM ' . self::TABLE);
        if ($range === false || $range['min_id'] === null) {
            return;
        }

        $maskBit = '((' . self::LANGUAGE_MASK_COLUMN . ' & 1) = 1)';
        $sql = 'UPDATE ' . self::TABLE . ' SET ' . self::ALWAYS_AVAILABLE_COLUMN . " = {$maskBit}"
            . ' WHERE id BETWEEN :from AND :to AND ' . self::ALWAYS_AVAILABLE_COLUMN . " <> {$maskBit}";

        $maxId = (int)$range['max_id'];
        for ($from = (int)$range['min_id']; $from <= $maxId; $from += self::BATCH_SIZE) {
            $this->addSql(
                $sql,
                ['from' => $from, 'to' => min($from + self::BATCH_SIZE - 1, $maxId)],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }
}
