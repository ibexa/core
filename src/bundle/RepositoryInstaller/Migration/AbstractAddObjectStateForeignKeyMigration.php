<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;

/**
 * Adds the foreign key from one column of an object state table to the "id" of the table it references.
 *
 * @internal
 */
abstract class AbstractAddObjectStateForeignKeyMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    protected const string SQL_DIRECTORY = __DIR__ . '/sql/add-object-state-foreign-keys';

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
    abstract protected function getReferencedTable(): string;

    /**
     * @return non-empty-string
     */
    abstract protected function getForeignKey(): string;

    public function getDescription(): string
    {
        return sprintf(
            'Adds the "%s" foreign key from "%s.%s" to "%s.id"',
            $this->getForeignKey(),
            $this->getTable(),
            $this->getColumn(),
            $this->getReferencedTable()
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
            !$schemaManager->tablesExist([$this->getReferencedTable(), $this->getTable()])
            || $schemaManager->introspectTableByUnquotedName($this->getTable())->hasForeignKey($this->getForeignKey())
        ) {
            return;
        }

        $this->handleOrphanedRows($this->countOrphanedRows());

        $foreignKeysEnforced = $this->isSqlite() && (bool)$this->connection->fetchOne('PRAGMA foreign_keys');
        if ($foreignKeysEnforced) {
            $this->addSqlFile(__DIR__ . '/sql/disable-foreign-keys-sqlite.sql');
        }

        $this->addSqlFile(sprintf(
            '%s/%s-%s.sql',
            self::SQL_DIRECTORY,
            $this->getForeignKey(),
            match (true) {
                $this->isSqlite() => 'sqlite',
                $this->isPostgreSQL() => 'postgresql',
                default => 'mysql',
            }
        ));

        if ($foreignKeysEnforced) {
            $this->addSqlFile(__DIR__ . '/sql/enable-foreign-keys-sqlite.sql');
        }
    }

    /**
     * Runs outside a transaction on SQLite too.
     */
    public function isTransactional(): bool
    {
        return parent::isTransactional() && !$this->isSqlite();
    }

    protected function handleOrphanedRows(int $count): void
    {
        $this->abortIf(
            $count > 0,
            sprintf(
                '"%s.%s" holds %d id(s) missing from "%s", so the "%s" foreign key can\'t be added. Delete those rows, or point them at an existing row, before this migration.',
                $this->getTable(),
                $this->getColumn(),
                $count,
                $this->getReferencedTable(),
                $this->getForeignKey()
            )
        );
    }

    private function countOrphanedRows(): int
    {
        return (int)$this->connection->fetchOne(
            sprintf(
                'SELECT COUNT(*) FROM %s t WHERE NOT EXISTS (SELECT 1 FROM %s r WHERE r.id = t.%s)',
                $this->getTable(),
                $this->getReferencedTable(),
                $this->getColumn()
            )
        );
    }
}
