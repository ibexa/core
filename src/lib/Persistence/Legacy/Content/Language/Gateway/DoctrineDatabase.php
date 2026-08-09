<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\Gateway;
use RuntimeException;

/**
 * Doctrine database based Language Gateway.
 *
 * @internal Gateway implementation is considered internal. Use Persistence Language Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(private readonly Connection $connection)
    {
    }

    public function insertLanguage(Language $language): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('MAX(id)')
            ->from(self::CONTENT_LANGUAGE_TABLE);

        $statement = $query->executeQuery();

        $lastId = (int)$statement->fetchOne();

        // Legacy only supports 8 * PHP_INT_SIZE - 2 languages:
        // One bit cannot be used because PHP uses signed integers and a second one is reserved for the
        // "always available flag".
        if ($lastId == (2 ** (8 * PHP_INT_SIZE - 2))) {
            throw new RuntimeException('Maximum number of languages reached.');
        }
        // Next power of 2 for bit masks
        $nextId = ($lastId !== 0 ? $lastId << 1 : 2);

        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::CONTENT_LANGUAGE_TABLE)
            ->values(
                [
                    'id' => ':id',
                    'locale' => ':language_code',
                    'name' => ':name',
                    'disabled' => ':disabled',
                ]
            )
            ->setParameter('id', $nextId, ParameterType::INTEGER);

        $this->setLanguageQueryParameters($query, $language);

        $query->executeStatement();

        return $nextId;
    }

    /**
     * Set columns for $query based on $language.
     */
    private function setLanguageQueryParameters(QueryBuilder $query, Language $language): void
    {
        $query
            ->setParameter('language_code', $language->languageCode, ParameterType::STRING)
            ->setParameter('name', $language->name, ParameterType::STRING)
            ->setParameter('disabled', (int)!$language->isEnabled, ParameterType::INTEGER);
    }

    public function updateLanguage(Language $language): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::CONTENT_LANGUAGE_TABLE)
            ->set('locale', ':language_code')
            ->set('name', ':name')
            ->set('disabled', ':disabled');

        $this->setLanguageQueryParameters($query, $language);

        $query->where(
            $query->expr()->eq(
                'id',
                $query->createNamedParameter($language->id, ParameterType::INTEGER, ':id')
            )
        );

        $query->executeStatement();
    }

    public function loadLanguageListData(array $ids): iterable
    {
        $query = $this->createFindQuery();
        $query
            ->where('id IN (:ids)')
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadLanguageListDataByLanguageCode(array $languageCodes): iterable
    {
        $query = $this->createFindQuery();
        $query
            ->where('locale IN (:locale)')
            ->setParameter('locale', $languageCodes, ArrayParameterType::STRING);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * Build a Language find (fetch) query.
     */
    private function createFindQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id', 'locale', 'name', 'disabled')
            ->from(self::CONTENT_LANGUAGE_TABLE);

        return $query;
    }

    public function loadAllLanguagesData(): array
    {
        return $this->createFindQuery()->executeQuery()->fetchAllAssociative();
    }

    public function deleteLanguage(int $id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::CONTENT_LANGUAGE_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    public function canDeleteLanguage(int $id): bool
    {
        if ($this->existsInTranslationTable($id, 'ibexa_content_translation')) {
            return false;
        }

        if ($this->existsInTranslationTable($id, 'ibexa_content_version_translation')) {
            return false;
        }

        // "ibexa_content"/"ibexa_content_version" also count as referencing the language when it's
        // their "initial_language_id" (main language), even without a matching translation row -
        // e.g. right after ContentService::updateContentMetadata() changes the main language code
        // without publishing a new version for it.
        if ($this->existsWithColumnValue($id, ContentGateway::CONTENT_ITEM_TABLE, 'initial_language_id')) {
            return false;
        }

        if ($this->existsWithColumnValue($id, ContentGateway::CONTENT_VERSION_TABLE, 'initial_language_id')) {
            return false;
        }

        // note: at some point this should be delegated to specific gateways
        foreach (self::MULTILINGUAL_TABLES_COLUMNS as $tableName => $columns) {
            // "ibexa_content"/"ibexa_content_version" are checked via the relational join tables
            // and the "initial_language_id" probes above instead - EXISTS probes against indexed
            // columns, rather than a full-table bitwise-AND scan.
            if ($tableName === ContentGateway::CONTENT_ITEM_TABLE || $tableName === ContentGateway::CONTENT_VERSION_TABLE) {
                continue;
            }

            $languageMaskColumn = $columns[0];
            $languageIdColumn = $columns[1] ?? null;
            if (
                $this->countTableData($id, $tableName, $languageMaskColumn, $languageIdColumn) > 0
            ) {
                return false;
            }
        }

        return true;
    }

    private function existsWithColumnValue(int $languageId, string $tableName, string $columnName): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('1')
            ->from($tableName)
            ->where(
                $query->expr()->eq(
                    $columnName,
                    $query->createPositionalParameter($languageId, ParameterType::INTEGER)
                )
            )
            ->setMaxResults(1);

        return $query->executeQuery()->fetchOne() !== false;
    }

    private function existsInTranslationTable(int $languageId, string $tableName): bool
    {
        return $this->existsWithColumnValue($languageId, $tableName, 'language_id');
    }

    /**
     * Count table data rows related to the given language.
     *
     * @param string|null $languageIdColumn optional column name containing explicit language id
     */
    private function countTableData(
        int $languageId,
        string $tableName,
        string $languageMaskColumn,
        ?string $languageIdColumn = null
    ): int {
        $query = $this->connection->createQueryBuilder();
        $query
            // avoiding using "*" as count argument, but don't specify column name because it varies
            ->select('COUNT(1)')
            ->from($tableName)
            ->where(
                $query->expr()->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        $languageMaskColumn,
                        $query->createPositionalParameter($languageId, ParameterType::INTEGER)
                    ),
                    0
                )
            );
        if (null !== $languageIdColumn) {
            $query
                ->orWhere(
                    $query->expr()->eq(
                        $languageIdColumn,
                        $query->createPositionalParameter($languageId, ParameterType::INTEGER)
                    )
                );
        }

        return (int)$query->executeQuery()->fetchOne();
    }

    public function loadContentTranslations(array $contentIds): array
    {
        return $this->loadTranslations('ibexa_content_translation', 'content_id', $contentIds);
    }

    public function loadVersionTranslations(array $versionIds): array
    {
        return $this->loadTranslations('ibexa_content_version_translation', 'content_version_id', $versionIds);
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, int[]>
     */
    private function loadTranslations(string $tableName, string $idColumn, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $query = $this->connection->createQueryBuilder();
        $rows = $query
            ->select($idColumn, 'language_id')
            ->from($tableName)
            ->where(
                $query->expr()->in(
                    $idColumn,
                    $query->createNamedParameter($ids, ArrayParameterType::INTEGER, ':ids')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($rows as $row) {
            $translations[(int)$row[$idColumn]][] = (int)$row['language_id'];
        }

        return $translations;
    }

    private function getDatabasePlatform(): AbstractPlatform
    {
        try {
            return $this->connection->getDatabasePlatform();
        } catch (Exception $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
