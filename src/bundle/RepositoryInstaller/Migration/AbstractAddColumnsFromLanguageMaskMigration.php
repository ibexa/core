<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\RepositoryInstaller\Migration;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Ibexa\Contracts\DoctrineMigrations\Migrations\AbstractSqlMigration;
use Ibexa\Contracts\DoctrineMigrations\Migrations\IbexaMigrationInterface;
use Ibexa\Contracts\DoctrineMigrations\Migrations\SqlPlatform;
use RuntimeException;

/**
 * Adds columns to a table and backfills them from the table's language mask, in batches of ids.
 *
 * @internal
 */
abstract class AbstractAddColumnsFromLanguageMaskMigration extends AbstractSqlMigration implements IbexaMigrationInterface
{
    private const int BATCH_SIZE = 5000;

    /**
     * @return non-empty-string
     */
    abstract protected function getTable(): string;

    /**
     * @return non-empty-string
     */
    abstract protected function getLanguageMaskColumn(): string;

    /**
     * Returns the SQL file adding each column, by column name, without the file's platform suffix.
     *
     * @return non-empty-array<non-empty-string, non-empty-string>
     */
    abstract protected function getAddColumnSqlFiles(): array;

    /**
     * Returns the SQL file backfilling the rows with ids between ":from" and ":to".
     *
     * @return non-empty-string
     */
    abstract protected function getBackfillSqlFile(): string;

    public static function getTargetVersion(): string
    {
        return '6.0.0';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->abortIfUnsupportedPlatform(SqlPlatform::MYSQL, SqlPlatform::MARIADB, SqlPlatform::POSTGRESQL, SqlPlatform::SQLITE);

        $schemaManager = $this->connection->createSchemaManager();
        if (!$schemaManager->tablesExist([$this->getTable()])) {
            return;
        }

        $table = $schemaManager->introspectTableByUnquotedName($this->getTable());
        foreach ($this->getAddColumnSqlFiles() as $column => $sqlFile) {
            if (!$table->hasColumn($column)) {
                $this->addSqlFile(sprintf('%s/sql/%s-%s.sql', __DIR__, $sqlFile, match (true) {
                    $this->isSqlite() => 'sqlite',
                    $this->isPostgreSQL() => 'postgresql',
                    default => 'mysql',
                }));
            }
        }

        if (!$table->hasColumn($this->getLanguageMaskColumn())) {
            return;
        }

        $range = $this->connection->fetchAssociative(
            sprintf('SELECT MIN(id) AS min_id, MAX(id) AS max_id FROM %s', $this->getTable())
        );
        if ($range === false || $range['min_id'] === null) {
            return;
        }

        $backfillSql = file_get_contents(__DIR__ . '/sql/' . $this->getBackfillSqlFile());
        if ($backfillSql === false) {
            throw new RuntimeException(sprintf('Unable to read the "%s" SQL file.', $this->getBackfillSqlFile()));
        }

        $maxId = (int)$range['max_id'];
        for ($from = (int)$range['min_id']; $from <= $maxId; $from += self::BATCH_SIZE) {
            $this->addSql(
                $backfillSql,
                ['from' => $from, 'to' => min($from + self::BATCH_SIZE - 1, $maxId)],
                ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
            );
        }
    }
}
