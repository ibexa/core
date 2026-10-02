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
 * scheme folded it into, as the final step of the language bitmask migration.
 *
 * Under the bitmask scheme every language id was a power of two, so an odd "language_id" can only be
 * an id with the always-available bit ORed in. That bit carries nothing worth keeping here - it
 * duplicates "ibexa_content.always_available", "ibexa_content_type.always_available" and object
 * state (group) "default_language_id" - but left in place it collides with the sequential ids new
 * languages now get: the first language added after the upgrade is allocated MAX(id) + 1, exactly
 * the tainted value of the highest existing language.
 *
 * Relies on the whole migration sequence running in one go, before any new language can be created:
 * once a sequential (odd) language id exists, an odd "language_id" is no longer necessarily tainted.
 *
 * Chunked and non-transactional for the same reasons as BackfillLanguageTranslationsMigration;
 * re-running a chunk is a no-op, since stripped values are even.
 */
final class StripAlwaysAvailableBitFromLanguageIdsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const BATCH_SIZE = 5000;

    /**
     * Table => column to chunk by, or null for tables small enough for a single statement.
     */
    private const TABLES = [
        'ibexa_content_field' => 'id',
        'ibexa_content_name' => 'contentobject_id',
        'ibexa_content_type_name' => null,
        'ibexa_object_state_language' => null,
        'ibexa_object_state_group_language' => null,
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
        return new DateTimeImmutable('2026-09-29 00:00:07');
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        foreach (self::TABLES as $table => $chunkColumn) {
            if (!$schemaManager->tablesExist([$table])) {
                continue;
            }

            $updateSql = "UPDATE {$table} SET language_id = language_id - 1 WHERE language_id % 2 = 1";
            if ($chunkColumn === null) {
                $this->addSql($updateSql);
                continue;
            }

            $this->addChunkedSql($table, $chunkColumn, $updateSql);
        }
    }

    private function addChunkedSql(string $table, string $chunkColumn, string $updateSql): void
    {
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
