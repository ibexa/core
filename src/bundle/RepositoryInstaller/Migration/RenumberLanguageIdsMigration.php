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
 * Renumbers the languages 2, 3, 4… in the order of their ids, as a fresh install numbers them.
 */
final class RenumberLanguageIdsMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const string LANGUAGE_TABLE = 'ibexa_content_language';

    public function getDescription(): string
    {
        return 'Renumbers the languages 2, 3, 4… in the order of their ids';
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public static function getCreationDate(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-11-16 01:00:00');
    }

    public function isTransactional(): bool
    {
        return true;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::LANGUAGE_TABLE])) {
            return;
        }

        $ids = array_map('intval', $this->connection->fetchFirstColumn('SELECT id FROM ' . self::LANGUAGE_TABLE . ' ORDER BY id'));
        if ($ids === [] || $ids === range(2, count($ids) + 1)) {
            return;
        }

        if ($this->isSqlite()) {
            $this->addSqlFile(
                (bool)$this->connection->fetchOne('PRAGMA foreign_keys')
                    ? __DIR__ . '/sql/renumber-language-ids-sqlite.sql'
                    : __DIR__ . '/sql/renumber-language-ids-without-foreign-keys-sqlite.sql'
            );

            return;
        }

        $this->abortIfForeignKeysDontCascade();

        $this->addSqlFile(
            $this->isPostgreSQL()
                ? __DIR__ . '/sql/renumber-language-ids-postgresql.sql'
                : __DIR__ . '/sql/renumber-language-ids-mysql.sql'
        );
    }

    /**
     * Refuses unless every foreign key to the language table, the ones AbstractAddLanguageForeignKeyMigration adds included, cascades updates.
     */
    private function abortIfForeignKeysDontCascade(): void
    {
        if ($this->isPostgreSQL()) {
            $rules = $this->connection->fetchAllKeyValue(
                "SELECT conrelid::regclass::text || '.' || conname, CASE confupdtype WHEN 'c' THEN 'CASCADE' ELSE 'other' END
                 FROM pg_constraint WHERE contype = 'f' AND confrelid = ?::regclass",
                [self::LANGUAGE_TABLE]
            );
        } else {
            $rules = $this->connection->fetchAllKeyValue(
                "SELECT CONCAT(TABLE_NAME, '.', CONSTRAINT_NAME), UPDATE_RULE FROM information_schema.REFERENTIAL_CONSTRAINTS
                 WHERE CONSTRAINT_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ?",
                [self::LANGUAGE_TABLE]
            );
        }

        $notCascading = array_keys(array_filter($rules, static fn (mixed $rule): bool => $rule !== 'CASCADE'));
        $this->abortIf(
            $notCascading !== [],
            sprintf(
                'These foreign keys reference "%s" without ON UPDATE CASCADE, so they would block renumbering the languages: %s. Re-create them with ON UPDATE CASCADE before this migration.',
                self::LANGUAGE_TABLE,
                implode(', ', $notCascading)
            )
        );

        $this->abortIf(
            !isset($rules['ibexa_content_field.ibexa_content_field_language_fk']),
            'The language id columns have no foreign keys to cascade the new ids to them. Run the Add*LanguageForeignKeyMigration migrations before this one.'
        );
    }
}
