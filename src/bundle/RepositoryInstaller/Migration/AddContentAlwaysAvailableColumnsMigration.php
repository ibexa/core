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
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Adds "always_available" columns to "ibexa_content" and "ibexa_content_version".
 */
final class AddContentAlwaysAvailableColumnsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string CONTENT_TABLE = 'ibexa_content';
    private const string CONTENT_VERSION_TABLE = 'ibexa_content_version';
    private const string ALWAYS_AVAILABLE_COLUMN = 'always_available';

    public function getDescription(): string
    {
        return 'Adds "always_available" columns to "ibexa_content" and "ibexa_content_version"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 00:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE, self::CONTENT_VERSION_TABLE])) {
            return;
        }

        foreach ([self::CONTENT_TABLE, self::CONTENT_VERSION_TABLE] as $table) {
            if (!$schemaManager->introspectTable($table)->hasColumn(self::ALWAYS_AVAILABLE_COLUMN)) {
                $this->addSql($this->buildAddColumnSql($table));
            }
        }
    }

    private function buildAddColumnSql(string $table): string
    {
        $columnDefinition = match (true) {
            $this->isMySQL(), $this->isMariaDB() => "TINYINT(1) DEFAULT '0' NOT NULL",
            $this->isPostgreSQL() => "BOOLEAN DEFAULT 'false' NOT NULL",
            default => "BOOLEAN DEFAULT '0' NOT NULL",
        };

        return "ALTER TABLE {$table} ADD COLUMN " . self::ALWAYS_AVAILABLE_COLUMN . " {$columnDefinition}";
    }
}
