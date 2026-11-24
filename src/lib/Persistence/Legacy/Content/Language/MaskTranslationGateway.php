<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Language;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Contracts\DoctrineSchema\Database\DatabasePlatformName;
use Ibexa\Contracts\DoctrineSchema\Database\DatabasePlatformResolver;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Compares the language translation tables and "always_available" with the legacy language bitmask columns, and fixes them.
 *
 * @internal
 */
final class MaskTranslationGateway
{
    public function __construct(private readonly Connection $connection) {}

    /**
     * @throws DBALException
     */
    public function hasMaskColumn(MaskedTable $table): bool
    {
        return $this->connection->createSchemaManager()
            ->introspectTableByUnquotedName($table->sourceTable())
            ->hasColumn($table->maskColumn());
    }

    /**
     * @return array{int, int}|null
     *
     * @throws DBALException
     */
    public function getIdRange(MaskedTable $table): ?array
    {
        return $this->fetchRange($table->sourceTable(), 'id');
    }

    /**
     * @return array{int, int}|null
     *
     * @throws DBALException
     */
    public function getTranslationIdRange(MaskedTable $table): ?array
    {
        return $this->fetchRange($table->translationTable(), $this->getOwnerColumn($table));
    }

    /**
     * @throws DBALException
     */
    public function countMissingTranslations(MaskedTable $table): int
    {
        return (int)$this->connection->fetchOne('SELECT COUNT(*) ' . $this->buildMissingTranslationsSql($table));
    }

    /**
     * @throws DBALException
     * @throws APIInvalidArgumentException
     */
    public function insertMissingTranslations(
        MaskedTable $table,
        int $fromId,
        int $toId
    ): int {
        $translationColumns = array_keys($table->keyColumns());
        $sourceColumns = array_map(
            static fn (string $column): string => "s.{$column}",
            array_values($table->keyColumns())
        );

        return $this->executeForRange(
            sprintf(
                'INSERT %s INTO %s (%s, language_id) SELECT %s, l.id %s AND s.id BETWEEN :from AND :to %s',
                $this->getInsertIgnoreKeyword(),
                $table->translationTable(),
                implode(', ', $translationColumns),
                implode(', ', $sourceColumns),
                $this->buildMissingTranslationsSql($table),
                $this->getOnConflictClause()
            ),
            $fromId,
            $toId
        );
    }

    /**
     * @throws DBALException
     */
    public function countOrphanedTranslations(MaskedTable $table): int
    {
        return (int)$this->connection->fetchOne(
            sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table->translationTable(), $this->buildOrphanedTranslationCondition($table))
        );
    }

    /**
     * @throws DBALException
     */
    public function deleteOrphanedTranslations(
        MaskedTable $table,
        int $fromId,
        int $toId
    ): int {
        $translationTable = $table->translationTable();

        return $this->executeForRange(
            sprintf(
                'DELETE FROM %s WHERE %s.%s BETWEEN :from AND :to AND %s',
                $translationTable,
                $translationTable,
                $this->getOwnerColumn($table),
                $this->buildOrphanedTranslationCondition($table)
            ),
            $fromId,
            $toId
        );
    }

    /**
     * @throws DBALException
     */
    public function countWrongAlwaysAvailableFlags(MaskedTable $table): int
    {
        $condition = $this->buildWrongAlwaysAvailableCondition($table);
        if ($condition === null) {
            return 0;
        }

        return (int)$this->connection->fetchOne("SELECT COUNT(*) FROM {$table->sourceTable()} WHERE {$condition}");
    }

    /**
     * @throws DBALException
     */
    public function fixAlwaysAvailableFlags(
        MaskedTable $table,
        int $fromId,
        int $toId
    ): int {
        $condition = $this->buildWrongAlwaysAvailableCondition($table);
        if ($condition === null) {
            return 0;
        }

        return $this->executeForRange(
            sprintf(
                'UPDATE %s SET %s = ((%s & 1) = 1) WHERE id BETWEEN :from AND :to AND %s',
                $table->sourceTable(),
                $table->alwaysAvailableColumn(),
                $table->maskColumn(),
                $condition
            ),
            $fromId,
            $toId
        );
    }

    private function buildMissingTranslationsSql(MaskedTable $table): string
    {
        $keyConditions = [];
        foreach ($table->keyColumns() as $translationColumn => $sourceColumn) {
            $keyConditions[] = "t.{$translationColumn} = s.{$sourceColumn}";
        }

        return sprintf(
            'FROM %s s JOIN %s l ON (s.%s & l.id) = l.id WHERE NOT EXISTS (SELECT 1 FROM %s t WHERE %s AND t.language_id = l.id)',
            $table->sourceTable(),
            Gateway::CONTENT_LANGUAGE_TABLE,
            $table->maskColumn(),
            $table->translationTable(),
            implode(' AND ', $keyConditions)
        );
    }

    private function buildOrphanedTranslationCondition(MaskedTable $table): string
    {
        $translationTable = $table->translationTable();
        $keyConditions = [];
        foreach ($table->keyColumns() as $translationColumn => $sourceColumn) {
            $keyConditions[] = "s.{$sourceColumn} = {$translationTable}.{$translationColumn}";
        }

        return sprintf(
            'NOT EXISTS (SELECT 1 FROM %s s WHERE %s AND (s.%s & %s.language_id) = %s.language_id)',
            $table->sourceTable(),
            implode(' AND ', $keyConditions),
            $table->maskColumn(),
            $translationTable,
            $translationTable
        );
    }

    private function buildWrongAlwaysAvailableCondition(MaskedTable $table): ?string
    {
        $alwaysAvailableColumn = $table->alwaysAvailableColumn();
        if ($alwaysAvailableColumn === null) {
            return null;
        }

        return "{$alwaysAvailableColumn} <> (({$table->maskColumn()} & 1) = 1)";
    }

    private function getOwnerColumn(MaskedTable $table): string
    {
        return array_key_first($table->keyColumns());
    }

    /**
     * @return array{int, int}|null
     *
     * @throws DBALException
     */
    private function fetchRange(
        string $table,
        string $column
    ): ?array {
        $range = $this->connection->fetchAssociative(
            "SELECT MIN({$column}) AS min_id, MAX({$column}) AS max_id FROM {$table}"
        );
        if ($range === false || $range['min_id'] === null) {
            return null;
        }

        return [(int)$range['min_id'], (int)$range['max_id']];
    }

    /**
     * @throws DBALException
     */
    private function executeForRange(
        string $sql,
        int $fromId,
        int $toId
    ): int {
        return (int)$this->connection->executeStatement(
            $sql,
            ['from' => $fromId, 'to' => $toId],
            ['from' => ParameterType::INTEGER, 'to' => ParameterType::INTEGER]
        );
    }

    /**
     * @throws APIInvalidArgumentException
     */
    private function getInsertIgnoreKeyword(): string
    {
        return match ($this->resolvePlatformName()) {
            DatabasePlatformName::MySQL => 'IGNORE',
            DatabasePlatformName::SQLite => 'OR IGNORE',
            DatabasePlatformName::PostgreSQL => '',
        };
    }

    /**
     * @throws APIInvalidArgumentException
     */
    private function getOnConflictClause(): string
    {
        return match ($this->resolvePlatformName()) {
            DatabasePlatformName::PostgreSQL => 'ON CONFLICT DO NOTHING',
            DatabasePlatformName::MySQL, DatabasePlatformName::SQLite => '',
        };
    }

    /**
     * @throws APIInvalidArgumentException
     */
    private function resolvePlatformName(): DatabasePlatformName
    {
        $platform = $this->connection->getDatabasePlatform();
        $name = DatabasePlatformResolver::resolveName($platform);
        if ($name === null) {
            throw new InvalidArgumentException(
                'platform',
                sprintf('Unsupported database platform: %s', $platform::class)
            );
        }

        return $name;
    }
}
