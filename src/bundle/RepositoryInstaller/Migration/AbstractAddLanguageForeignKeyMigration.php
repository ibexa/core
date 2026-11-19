<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Adds the foreign key from one language id column to "ibexa_content_language.id".
 *
 * @internal
 */
abstract class AbstractAddLanguageForeignKeyMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    protected const string LANGUAGE_TABLE = 'ibexa_content_language';

    /**
     * @return non-empty-string
     */
    abstract protected function getTable(): string;

    /**
     * @return non-empty-string
     */
    abstract protected function getColumn(): string;

    /**
     * @return non-empty-string
     */
    abstract protected function getForeignKey(): string;

    public function getDescription(): string
    {
        return sprintf(
            'Adds the "%s" foreign key from "%s.%s" to "%s"',
            $this->getForeignKey(),
            $this->getTable(),
            $this->getColumn(),
            self::LANGUAGE_TABLE
        );
    }

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (
            !$schemaManager->tablesExist([self::LANGUAGE_TABLE, $this->getTable()])
            || $schemaManager->introspectTableByUnquotedName($this->getTable())->hasForeignKey($this->getForeignKey())
        ) {
            return;
        }

        $this->abortIfOrphanedLanguageIds();

        if ($this->isSqlite()) {
            $this->abortIf(
                (bool)$this->connection->fetchOne('PRAGMA foreign_keys'),
                sprintf(
                    'SQLite enforces foreign keys on this connection, so dropping "%s" to rebuild it would delete the rows referencing it. Run this migration with PRAGMA foreign_keys = OFF.',
                    $this->getTable()
                )
            );
        }

        $sqlFiles = $this->getSqlFiles($schemaManager, match (true) {
            $this->isSqlite() => 'sqlite',
            $this->isPostgreSQL() => 'postgresql',
            default => 'mysql',
        });
        foreach ($sqlFiles as $sqlFile) {
            $this->addSqlFile($sqlFile);
        }
    }

    /**
     * @param AbstractSchemaManager<AbstractPlatform> $schemaManager
     *
     * @return non-empty-list<non-empty-string>
     */
    protected function getSqlFiles(
        AbstractSchemaManager $schemaManager,
        string $platform
    ): array {
        return [$this->getSqlFile($platform)];
    }

    /**
     * @return non-empty-string
     */
    protected function getSqlFile(
        string $platform,
        string $variant = ''
    ): string {
        return __DIR__ . "/sql/add-language-foreign-keys/{$this->getForeignKey()}{$variant}-{$platform}.sql";
    }

    private function abortIfOrphanedLanguageIds(): void
    {
        $languageIds = $this->connection->fetchFirstColumn(
            sprintf(
                'SELECT DISTINCT t.%2$s FROM %1$s t WHERE NOT EXISTS (SELECT 1 FROM %3$s l WHERE l.id = t.%2$s) ORDER BY t.%2$s',
                $this->getTable(),
                $this->getColumn(),
                self::LANGUAGE_TABLE
            )
        );

        $this->abortIf(
            $languageIds !== [],
            sprintf(
                '"%s.%s" holds language id(s) %s missing from "%s", so the "%s" foreign key can\'t be added. Delete those rows, or point them at an existing language, before this migration.',
                $this->getTable(),
                $this->getColumn(),
                implode(', ', $languageIds),
                self::LANGUAGE_TABLE,
                $this->getForeignKey()
            )
        );
    }
}
