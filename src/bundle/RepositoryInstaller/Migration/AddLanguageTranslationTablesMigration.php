<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Creates "ibexa_content_translation", "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation".
 */
final class AddLanguageTranslationTablesMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string CONTENT_TABLE = 'ibexa_content';

    /** @var array<string, array<string, string>> */
    private const array FOREIGN_KEYS = [
        'ibexa_content_translation' => [
            'ibexa_content_translation_content_fk' => 'FOREIGN KEY (content_id) REFERENCES ibexa_content (id) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_content_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
        'ibexa_content_version_translation' => [
            'ibexa_content_version_translation_version_fk' => 'FOREIGN KEY (content_version_id) REFERENCES ibexa_content_version (id) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_content_version_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
        'ibexa_url_alias_ml_translation' => [
            'ibexa_url_alias_ml_translation_alias_fk' => 'FOREIGN KEY (parent, text_md5) REFERENCES ibexa_url_alias_ml (parent, text_md5) ON DELETE CASCADE ON UPDATE CASCADE',
            'ibexa_url_alias_ml_translation_language_fk' => 'FOREIGN KEY (language_id) REFERENCES ibexa_content_language (id) ON DELETE RESTRICT ON UPDATE CASCADE',
        ],
    ];

    public function getDescription(): string
    {
        return 'Creates "ibexa_content_translation", "ibexa_content_version_translation" and "ibexa_url_alias_ml_translation"';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-10-19 01:00:00');
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([self::CONTENT_TABLE])) {
            return;
        }

        $allTablesExist = $schemaManager->tablesExist(array_keys(self::FOREIGN_KEYS));

        if ($this->isSqlite()) {
            if ($allTablesExist) {
                return;
            }

            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-sqlite.sql');

            return;
        }

        $missingForeignKeys = $this->findMissingForeignKeys($schemaManager);

        if ($allTablesExist && $missingForeignKeys === []) {
            return;
        }

        if ($this->isMySQL() || $this->isMariaDB()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-mysql.sql');
        } elseif ($this->isPostgreSQL()) {
            $this->addSqlFile(__DIR__ . '/sql/add-language-translation-tables-postgresql.sql');
        }

        foreach ($missingForeignKeys as [$table, $constraintName, $definition]) {
            $this->addSql("ALTER TABLE {$table} ADD CONSTRAINT {$constraintName} {$definition}");
        }
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return list<array{0: string, 1: string, 2: string}>
     */
    private function findMissingForeignKeys(AbstractSchemaManager $schemaManager): array
    {
        $missing = [];

        foreach (self::FOREIGN_KEYS as $table => $foreignKeys) {
            $existingTable = $schemaManager->tablesExist([$table])
                ? $schemaManager->introspectTableByUnquotedName($table)
                : null;

            foreach ($foreignKeys as $constraintName => $definition) {
                if ($existingTable !== null && $existingTable->hasForeignKey($constraintName)) {
                    continue;
                }

                $missing[] = [$table, $constraintName, $definition];
            }
        }

        return $missing;
    }
}
