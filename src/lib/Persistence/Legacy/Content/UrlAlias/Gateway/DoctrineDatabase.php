<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlAlias\Gateway;
use RuntimeException;

/**
 * UrlAlias gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence UrlAlias Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\UrlAlias\Handler
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * 2^30, since PHP_INT_MAX can cause overflows in DB systems, if PHP is run
     * on 64 bit systems.
     */
    public const MAX_LIMIT = 1073741824;

    private const URL_ALIAS_DATA_COLUMN_TYPE_MAP = [
        'id' => ParameterType::INTEGER,
        'link' => ParameterType::INTEGER,
        'is_alias' => ParameterType::INTEGER,
        'alias_redirects' => ParameterType::INTEGER,
        'is_original' => ParameterType::INTEGER,
        'action' => ParameterType::STRING,
        'action_type' => ParameterType::STRING,
        'text' => ParameterType::STRING,
        'parent' => ParameterType::INTEGER,
        'text_md5' => ParameterType::STRING,
        'is_always_available' => ParameterType::BOOLEAN,
    ];

    private string $table;

    public function __construct(
        private Connection $connection,
        private LanguageHandler $languageHandler
    ) {
        $this->table = static::TABLE;
    }

    public function setTable(string $name): void
    {
        $this->table = $name;
    }

    /**
     * Loads all list of aliases by given $locationId.
     */
    public function loadAllLocationEntries(int $locationId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(array_keys(self::URL_ALIAS_DATA_COLUMN_TYPE_MAP))
            ->from($this->connection->quoteIdentifier($this->table))
            ->where('action = :action')
            ->andWhere('is_original = :is_original')
            ->setParameter('action', "eznode:{$locationId}", ParameterType::STRING)
            ->setParameter('is_original', 1, ParameterType::INTEGER);

        return $query->executeQuery()->fetchAllAssociative();
    }

    public function loadLocationEntries(
        int $locationId,
        bool $custom = false,
        ?int $languageId = null
    ): array {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'id',
                'link',
                'is_alias',
                'alias_redirects',
                'is_always_available',
                'is_original',
                'parent',
                'text',
                'text_md5',
                'action'
            )
            ->from($this->connection->quoteIdentifier($this->table), 'u')
            ->where(
                $expr->eq(
                    'action',
                    $query->createPositionalParameter(
                        "eznode:{$locationId}",
                        ParameterType::STRING
                    )
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_alias',
                    $query->createPositionalParameter($custom ? 1 : 0, ParameterType::INTEGER)
                )
            )
        ;

        if (null !== $languageId) {
            $query->andWhere($this->buildTranslationExistsCondition($query, 'u.parent', 'u.text_md5', [$languageId]));
        }

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function listGlobalEntries(
        ?string $languageCode = null,
        int $offset = 0,
        int $limit = -1
    ): array {
        $limit = $limit === -1 ? self::MAX_LIMIT : $limit;

        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'action',
                'id',
                'link',
                'is_alias',
                'alias_redirects',
                'is_always_available',
                'is_original',
                'parent',
                'text_md5'
            )
            ->from($this->connection->quoteIdentifier($this->table), 'u')
            ->where(
                $expr->eq(
                    'action_type',
                    $query->createPositionalParameter(
                        'module',
                        ParameterType::STRING
                    )
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_alias',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            )
            ->setMaxResults(
                $limit
            )
            ->setFirstResult($offset);

        if (isset($languageCode)) {
            $languageId = $this->languageHandler->loadByLanguageCode($languageCode)->id;
            $query->andWhere($this->buildTranslationExistsCondition($query, 'u.parent', 'u.text_md5', [$languageId]));
        }
        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function isRootEntry(int $id): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                'text',
                'parent'
            )
            ->from($this->connection->quoteIdentifier($this->table))
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );
        $statement = $query->executeQuery();

        $row = $statement->fetchAssociative();

        if ($row === false) {
            return false;
        }

        return strlen($row['text']) == 0 && $row['parent'] == 0;
    }

    public function cleanupAfterPublish(
        string $action,
        int $languageId,
        int $newId,
        int $parentId,
        string $textMD5
    ): void {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select(
                'parent',
                'text_md5'
            )
            ->from($this->connection->quoteIdentifier($this->table), 'u')
            // 1) Autogenerated aliases that match action and language...
            ->where(
                $expr->eq(
                    'action',
                    $query->createPositionalParameter($action, ParameterType::STRING)
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_alias',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                )
            )
            ->andWhere($this->buildTranslationExistsCondition($query, 'u.parent', 'u.text_md5', [$languageId]))
            // 2) ...but not newly published entry
            ->andWhere(
                sprintf(
                    'NOT (%s)',
                    $expr->and(
                        $expr->eq(
                            'parent',
                            $query->createPositionalParameter($parentId, ParameterType::INTEGER)
                        ),
                        $expr->eq(
                            'text_md5',
                            $query->createPositionalParameter($textMD5, ParameterType::STRING)
                        )
                    )
                )
            );

        $statement = $query->executeQuery();

        $row = $statement->fetchAssociative();

        if (!empty($row)) {
            $this->archiveUrlAliasForDeletedTranslation(
                (int)$row['parent'],
                $row['text_md5'],
                (int)$languageId,
                (int)$newId
            );
        }
    }

    /**
     * Archive (remove or historize) obsolete URL aliases (for translations that were removed).
     *
     * If the alias still carries other real (non-always-available) languages besides the removed
     * one, only that language's translation row is removed; otherwise the whole entry is obsolete
     * and gets historized instead.
     */
    private function archiveUrlAliasForDeletedTranslation(
        int $parent,
        string $textMD5,
        int $languageId,
        int $linkId
    ): void {
        $hasOtherLanguages = (bool)$this->connection->fetchOne(
            'SELECT 1 FROM ibexa_url_alias_ml_translation
             WHERE parent = :parent AND text_md5 = :textMd5 AND language_id != :languageId',
            ['parent' => $parent, 'textMd5' => $textMD5, 'languageId' => $languageId],
            ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING, 'languageId' => ParameterType::INTEGER]
        );

        if ($hasOtherLanguages) {
            $this->removeTranslation($parent, $textMD5, $languageId);
        } else {
            // Otherwise mark entry as history
            $this->historize($parent, $textMD5, $linkId);
        }
    }

    /**
     * @param int[] $languageIds real (non-always-available) language ids the swapped entry carries
     */
    public function historizeBeforeSwap(string $action, array $languageIds): void
    {
        $tableName = $this->connection->quoteIdentifier($this->table);

        $query = $this->connection->createQueryBuilder();
        $query
            ->update($tableName)
            ->set(
                'is_original',
                $query->createPositionalParameter(0, ParameterType::INTEGER)
            )
            ->set(
                'id',
                $query->createPositionalParameter(
                    $this->getNextId(),
                    ParameterType::INTEGER
                )
            )
            ->where(
                $query->expr()->and(
                    $query->expr()->eq(
                        'action',
                        $query->createPositionalParameter($action, ParameterType::STRING)
                    ),
                    $query->expr()->eq(
                        'is_original',
                        $query->createPositionalParameter(1, ParameterType::INTEGER)
                    ),
                    $this->buildTranslationExistsCondition($query, "{$tableName}.parent", "{$tableName}.text_md5", $languageIds)
                )
            );

        $query->executeStatement();
    }

    /**
     * Update single row matched by composite primary key.
     *
     * Sets "is_original" to 0 thus marking entry as history.
     *
     * Re-links history entries.
     *
     * When location alias is published we need to check for new history entries created with self::downgrade()
     * with the same action and language, update their "link" column with id of the published entry.
     * History entry "id" column is moved to next id value so that all active (non-history) entries are kept
     * under the same id.
     */
    private function historize(int $parentId, string $textMD5, int $newId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update($this->connection->quoteIdentifier($this->table))
            ->set(
                'is_original',
                $query->createPositionalParameter(0, ParameterType::INTEGER)
            )
            ->set(
                'link',
                $query->createPositionalParameter($newId, ParameterType::INTEGER)
            )
            ->set(
                'id',
                $query->createPositionalParameter(
                    $this->getNextId(),
                    ParameterType::INTEGER
                )
            )
            ->where(
                $query->expr()->and(
                    $query->expr()->eq(
                        'parent',
                        $query->createPositionalParameter($parentId, ParameterType::INTEGER)
                    ),
                    $query->expr()->eq(
                        'text_md5',
                        $query->createPositionalParameter($textMD5, ParameterType::STRING)
                    )
                )
            );
        $query->executeStatement();
    }

    /**
     * Removes given $languageId from entry's set of translations.
     */
    private function removeTranslation(int $parentId, string $textMD5, int $languageId): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ibexa_url_alias_ml_translation WHERE parent = :parent AND text_md5 = :textMd5 AND language_id = :languageId',
            ['parent' => $parentId, 'textMd5' => $textMD5, 'languageId' => $languageId],
            ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING, 'languageId' => ParameterType::INTEGER]
        );
    }

    public function historizeId(int $id, int $link): void
    {
        if ($id === $link) {
            return;
        }

        $query = $this->connection->createQueryBuilder();
        $query->select(
            'parent',
            'text_md5'
        )->from(
            $this->connection->quoteIdentifier($this->table)
        )->where(
            $query->expr()->and(
                $query->expr()->eq(
                    'is_alias',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                ),
                $query->expr()->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                ),
                $query->expr()->eq(
                    'action_type',
                    $query->createPositionalParameter(
                        'eznode',
                        ParameterType::STRING
                    )
                ),
                $query->expr()->eq(
                    'link',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            )
        );

        $statement = $query->executeQuery();

        $rows = $statement->fetchAllAssociative();

        foreach ($rows as $row) {
            $this->historize((int)$row['parent'], $row['text_md5'], $link);
        }
    }

    public function reparent(int $oldParentId, int $newParentId): void
    {
        $query = $this->connection->createQueryBuilder();
        $query->update(
            $this->connection->quoteIdentifier($this->table)
        )->set(
            'parent',
            $query->createPositionalParameter($newParentId, ParameterType::INTEGER)
        )->where(
            $query->expr()->eq(
                'parent',
                $query->createPositionalParameter(
                    $oldParentId,
                    ParameterType::INTEGER
                )
            )
        );

        $query->executeStatement();
    }

    /**
     * @param array<string, mixed> $values associative array with column names as keys and column
     *        values as values - "language_ids" (int[]) is a pseudo-column: it does not map to a
     *        real table column, and instead replaces the row's translations in
     *        "ibexa_url_alias_ml_translation".
     */
    public function updateRow(int $parentId, string $textMD5, array $values): void
    {
        $languageIds = $values['language_ids'] ?? null;
        unset($values['language_ids']);

        $query = $this->connection->createQueryBuilder();
        $query->update($this->connection->quoteIdentifier($this->table));
        foreach ($values as $columnName => $value) {
            $query->set(
                $columnName,
                $query->createNamedParameter(
                    $value,
                    self::URL_ALIAS_DATA_COLUMN_TYPE_MAP[$columnName],
                    ":{$columnName}"
                )
            );
        }
        $query
            ->where(
                $query->expr()->eq(
                    'parent',
                    $query->createNamedParameter($parentId, ParameterType::INTEGER, ':parent')
                )
            )
            ->andWhere(
                $query->expr()->eq(
                    'text_md5',
                    $query->createNamedParameter($textMD5, ParameterType::STRING, ':text_md5')
                )
            );
        $query->executeStatement();

        if (null !== $languageIds) {
            $this->syncUrlAliasTranslations($parentId, $textMD5, $languageIds);
        }
    }

    public function insertRow(array $values): int
    {
        if (!isset($values['id'])) {
            $values['id'] = $this->getNextId();
        }
        if (!isset($values['link'])) {
            $values['link'] = $values['id'];
        }
        if (!isset($values['is_original'])) {
            $values['is_original'] = ($values['id'] == $values['link'] ? 1 : 0);
        }
        if (!isset($values['is_alias'])) {
            $values['is_alias'] = 0;
        }
        if (!isset($values['alias_redirects'])) {
            $values['alias_redirects'] = 0;
        }
        if (
            !isset($values['action_type'])
            && preg_match('#^(.+):.*#', $values['action'], $matches)
        ) {
            $values['action_type'] = $matches[1];
        }
        if ($values['is_alias']) {
            $values['is_original'] = 1;
        }
        if ($values['action'] === self::NOP_ACTION) {
            $values['is_original'] = 1;
        }

        $languageIds = $values['language_ids'] ?? null;
        unset($values['language_ids']);

        $query = $this->connection->createQueryBuilder();
        $query->insert($this->connection->quoteIdentifier($this->table));
        foreach ($values as $columnName => $value) {
            $query->setValue(
                $columnName,
                $query->createNamedParameter(
                    $value,
                    self::URL_ALIAS_DATA_COLUMN_TYPE_MAP[$columnName],
                    ":{$columnName}"
                )
            );
        }
        $query->executeStatement();

        if (null !== $languageIds) {
            $this->syncUrlAliasTranslations((int)$values['parent'], (string)$values['text_md5'], $languageIds);
        }

        return (int)$values['id'];
    }

    /**
     * Replaces $parentId/$textMD5's rows in "ibexa_url_alias_ml_translation" with $languageIds.
     *
     * @param int[] $languageIds
     */
    private function syncUrlAliasTranslations(int $parentId, string $textMD5, array $languageIds): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ibexa_url_alias_ml_translation WHERE parent = :parent AND text_md5 = :textMd5',
            ['parent' => $parentId, 'textMd5' => $textMD5],
            ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING]
        );

        foreach ($languageIds as $languageId) {
            $this->connection->executeStatement(
                'INSERT INTO ibexa_url_alias_ml_translation (parent, text_md5, language_id) VALUES (:parent, :textMd5, :languageId)',
                ['parent' => $parentId, 'textMd5' => $textMD5, 'languageId' => $languageId],
                ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING, 'languageId' => ParameterType::INTEGER]
            );
        }
    }

    public function getNextId(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::INCR_TABLE)
            ->values(
                [
                    'id' => $this->getDatabasePlatform()->supportsSequences()
                        ? sprintf('NEXTVAL(\'%s\')', self::INCR_TABLE_SEQ)
                        : $query->createPositionalParameter(null, ParameterType::NULL),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId();
    }

    public function loadRow(int $parentId, string $textMD5): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('*')->from(
            $this->connection->quoteIdentifier($this->table)
        )->where(
            $query->expr()->and(
                $query->expr()->eq(
                    'parent',
                    $query->createPositionalParameter(
                        $parentId,
                        ParameterType::INTEGER
                    )
                ),
                $query->expr()->eq(
                    'text_md5',
                    $query->createPositionalParameter(
                        $textMD5,
                        ParameterType::STRING
                    )
                )
            )
        );

        $result = $query->executeQuery()->fetchAssociative();

        return false !== $result ? $result : [];
    }

    public function loadUrlAliasData(array $urlHashes): array
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();

        $count = count($urlHashes);
        foreach ($urlHashes as $level => $urlPartHash) {
            $tableAlias = $level !== $count - 1 ? $this->table . $level : 'u';
            $query
                ->addSelect(
                    array_map(
                        static function (string $columnName) use ($tableAlias): string {
                            // do not alias data for top level url part
                            $columnAlias = 'u' === $tableAlias
                                ? $columnName
                                : "{$tableAlias}_{$columnName}";
                            $columnName = "{$tableAlias}.{$columnName}";

                            return "{$columnName} AS {$columnAlias}";
                        },
                        array_keys(self::URL_ALIAS_DATA_COLUMN_TYPE_MAP)
                    )
                )
                ->from($this->connection->quoteIdentifier($this->table), $tableAlias);

            $query
                ->andWhere(
                    $expr->eq(
                        "{$tableAlias}.text_md5",
                        $query->createPositionalParameter($urlPartHash, ParameterType::STRING)
                    )
                )
                ->andWhere(
                    $expr->eq(
                        "{$tableAlias}.parent",
                        // root entry has parent column set to 0
                        isset($previousTableName) ? $previousTableName . '.link' : $query->createPositionalParameter(
                            0,
                            ParameterType::INTEGER
                        )
                    )
                );

            $previousTableName = $tableAlias;
        }
        $query->setMaxResults(1);

        $result = $query->executeQuery()->fetchAssociative();

        return false !== $result ? $result : [];
    }

    public function loadAutogeneratedEntry(string $action, ?int $parentId = null): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            '*'
        )->from(
            $this->connection->quoteIdentifier($this->table)
        )->where(
            $query->expr()->and(
                $query->expr()->eq(
                    'action',
                    $query->createPositionalParameter($action, ParameterType::STRING)
                ),
                $query->expr()->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                ),
                $query->expr()->eq(
                    'is_alias',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                )
            )
        );

        if (isset($parentId)) {
            $query->andWhere(
                $query->expr()->eq(
                    'parent',
                    $query->createPositionalParameter(
                        $parentId,
                        ParameterType::INTEGER
                    )
                )
            );
        }

        $entry = $query->executeQuery()->fetchAssociative();

        return false !== $entry ? $entry : [];
    }

    public function loadPathData(int $id): array
    {
        $pathData = [];

        while ($id != 0) {
            $query = $this->connection->createQueryBuilder();
            $query->select(
                'parent',
                'text_md5',
                'is_always_available',
                'text'
            )->from(
                $this->connection->quoteIdentifier($this->table)
            )->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

            $statement = $query->executeQuery();

            $rows = $statement->fetchAllAssociative();
            if (empty($rows)) {
                // Normally this should never happen
                $pathDataArray = [];
                foreach ($pathData as $path) {
                    if (!isset($path[0]['text'])) {
                        continue;
                    }

                    $pathDataArray[] = $path[0]['text'];
                }

                $path = implode('/', $pathDataArray);
                throw new BadStateException(
                    'id',
                    "Unable to load path data, path '{$path}' is broken, alias with ID '{$id}' not found. " .
                    'To fix all broken paths run the ezplatform:urls:regenerate-aliases command'
                );
            }

            $id = $rows[0]['parent'];
            array_unshift($pathData, $rows);
        }

        return $pathData;
    }

    public function loadPathDataByHierarchy(array $hierarchyData): array
    {
        $query = $this->connection->createQueryBuilder();

        $hierarchyConditions = [];
        foreach ($hierarchyData as $levelData) {
            $hierarchyConditions[] = $query->expr()->and(
                $query->expr()->eq(
                    'parent',
                    $query->createPositionalParameter(
                        $levelData['parent'],
                        ParameterType::INTEGER
                    )
                ),
                $query->expr()->eq(
                    'action',
                    $query->createPositionalParameter(
                        $levelData['action'],
                        ParameterType::STRING
                    )
                ),
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter(
                        $levelData['id'],
                        ParameterType::INTEGER
                    )
                )
            );
        }

        $query->select(
            'action',
            'parent',
            'text_md5',
            'is_always_available',
            'text'
        )->from(
            $this->connection->quoteIdentifier($this->table)
        )->where(
            $query->expr()->or(...$hierarchyConditions)
        );

        $statement = $query->executeQuery();

        $rows = $statement->fetchAllAssociative();
        $rowsMap = [];
        foreach ($rows as $row) {
            $rowsMap[$row['action']][] = $row;
        }

        if (count($rowsMap) !== count($hierarchyData)) {
            throw new RuntimeException('The path is corrupted.');
        }

        $data = [];
        foreach ($hierarchyData as $levelData) {
            $data[] = $rowsMap[$levelData['action']];
        }

        return $data;
    }

    public function removeCustomAlias(int $parentId, string $textMD5): bool
    {
        $query = $this->connection->createQueryBuilder();
        $query->delete(
            $this->connection->quoteIdentifier($this->table)
        )->where(
            $query->expr()->and(
                $query->expr()->eq(
                    'parent',
                    $query->createPositionalParameter(
                        $parentId,
                        ParameterType::INTEGER
                    )
                ),
                $query->expr()->eq(
                    'text_md5',
                    $query->createPositionalParameter(
                        $textMD5,
                        ParameterType::STRING
                    )
                ),
                $query->expr()->eq(
                    'is_alias',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            )
        );

        return $query->executeStatement() === 1;
    }

    public function remove(string $action, ?int $id = null): void
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->delete($this->connection->quoteIdentifier($this->table))
            ->where(
                $expr->eq(
                    'action',
                    $query->createPositionalParameter($action, ParameterType::STRING)
                )
            );

        if ($id !== null) {
            $query
                ->andWhere(
                    $expr->eq(
                        'is_alias',
                        $query->createPositionalParameter(0, ParameterType::INTEGER)
                    ),
                )
                ->andWhere(
                    $expr->eq(
                        'id',
                        $query->createPositionalParameter(
                            $id,
                            ParameterType::INTEGER
                        )
                    )
                );
        }

        $query->executeStatement();
    }

    public function loadAutogeneratedEntries(int $parentId, bool $includeHistory = false): array
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('*')
            ->from($this->connection->quoteIdentifier($this->table))
            ->where(
                $expr->eq(
                    'parent',
                    $query->createPositionalParameter(
                        $parentId,
                        ParameterType::INTEGER
                    )
                ),
            )
            ->andWhere(
                $expr->eq(
                    'action_type',
                    $query->createPositionalParameter(
                        'eznode',
                        ParameterType::STRING
                    )
                )
            )
            ->andWhere(
                $expr->eq(
                    'is_alias',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                )
            );

        if (!$includeHistory) {
            $query->andWhere(
                $expr->eq(
                    'is_original',
                    $query->createPositionalParameter(1, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    public function getLocationContentMainLanguageId(int $locationId): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->select('c.initial_language_id')
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->join('c', LocationGateway::CONTENT_TREE_TABLE, 't', $expr->eq('t.contentobject_id', 'c.id'))
            ->where(
                $expr->eq('t.node_id', ':locationId')
            )
            ->setParameter('locationId', $locationId, ParameterType::INTEGER);

        $statement = $queryBuilder->executeQuery();
        $languageId = $statement->fetchOne();

        if ($languageId === false) {
            throw new RuntimeException("Could not find Content for Location #{$locationId}");
        }

        return (int)$languageId;
    }

    public function bulkRemoveTranslation(int $languageId, array $actions): void
    {
        $this->connection->executeStatement(
            'DELETE FROM ibexa_url_alias_ml_translation
             WHERE language_id = :languageId
             AND EXISTS (
                 SELECT 1 FROM ' . $this->connection->quoteIdentifier($this->table) . ' u
                 WHERE u.parent = ibexa_url_alias_ml_translation.parent
                 AND u.text_md5 = ibexa_url_alias_ml_translation.text_md5
                 AND u.action IN (:actions)
             )',
            ['languageId' => $languageId, 'actions' => $actions],
            ['languageId' => ParameterType::INTEGER, 'actions' => ArrayParameterType::STRING]
        );

        // cleanup: delete rows left with no real (non-always-available) language at all
        $tableName = $this->connection->quoteIdentifier($this->table);
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($tableName)
            ->where('action IN (:actions)')
            ->andWhere(
                "NOT EXISTS (SELECT 1 FROM ibexa_url_alias_ml_translation ut WHERE ut.parent = {$tableName}.parent AND ut.text_md5 = {$tableName}.text_md5)"
            )
            ->setParameter('actions', $actions, ArrayParameterType::STRING);
        $query->executeStatement();
    }

    public function archiveUrlAliasesForDeletedTranslations(
        int $locationId,
        int $parentId,
        array $languageIds
    ): void {
        // determine proper parent for linking historized entry
        $existingLocationEntry = $this->loadAutogeneratedEntry(
            'eznode:' . $locationId,
            $parentId
        );

        // filter existing URL alias entries by any of the specified removed languages
        $rows = $this->loadLocationEntriesMatchingMultipleLanguages(
            $locationId,
            $languageIds
        );

        // remove each row's actually-present removed languages
        foreach ($rows as $row) {
            $rowLanguageIds = $this->loadTranslationLanguageIds((int)$row['parent'], $row['text_md5']);
            $languageIdsToBeRemoved = array_intersect($languageIds, $rowLanguageIds);

            if (empty($languageIdsToBeRemoved)) {
                continue;
            }

            // use existing entry to link archived alias or use current alias id
            $linkToId = !empty($existingLocationEntry)
                ? (int)$existingLocationEntry['id']
                : (int)$row['id'];
            foreach ($languageIdsToBeRemoved as $languageId) {
                $this->archiveUrlAliasForDeletedTranslation(
                    (int)$row['parent'],
                    $row['text_md5'],
                    (int)$languageId,
                    $linkToId
                );
            }
        }
    }

    public function loadTranslationLanguageIds(int $parent, string $textMD5): array
    {
        return array_map(
            'intval',
            $this->connection->fetchFirstColumn(
                'SELECT language_id FROM ibexa_url_alias_ml_translation WHERE parent = :parent AND text_md5 = :textMd5',
                ['parent' => $parent, 'textMd5' => $textMD5],
                ['parent' => ParameterType::INTEGER, 'textMd5' => ParameterType::STRING]
            )
        );
    }

    /**
     * Load list of aliases for given $locationId matching any of the specified Languages.
     *
     * @param int[] $languageIds
     */
    private function loadLocationEntriesMatchingMultipleLanguages(
        int $locationId,
        array $languageIds
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id', 'parent', 'text_md5')
            ->from($this->connection->quoteIdentifier($this->table), 'u')
            ->where(
                $query->expr()->eq(
                    'action',
                    $query->createPositionalParameter('eznode:' . $locationId, ParameterType::STRING)
                )
            )
            // fetch rows matching any of the given Languages
            ->andWhere($this->buildTranslationExistsCondition($query, 'u.parent', 'u.text_md5', $languageIds));

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteUrlAliasesWithoutLocation(): int
    {
        $subQuery = $this->connection->createQueryBuilder();
        $subQuery
            ->select('node_id')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 't')
            ->where(
                $subQuery->expr()->eq(
                    't.node_id',
                    sprintf(
                        'CAST(%s as %s)',
                        $this->getDatabasePlatform()->getSubstringExpression(
                            $this->connection->quoteIdentifier($this->table) . '.action',
                            8
                        ),
                        $this->getIntegerType()
                    )
                )
            );

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier($this->table))
            ->where(
                $deleteQuery->expr()->eq(
                    'action_type',
                    $deleteQuery->createPositionalParameter('eznode')
                )
            )
            ->andWhere(
                sprintf('NOT EXISTS (%s)', $subQuery->getSQL())
            );

        return $deleteQuery->executeStatement();
    }

    public function deleteUrlAliasesWithoutParent(): int
    {
        $existingAliasesQuery = $this->getAllUrlAliasesQuery();

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($this->connection->quoteIdentifier($this->table))
            ->where(
                $query->expr()->neq(
                    'parent',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $query->expr()->notIn(
                    'parent',
                    $existingAliasesQuery
                )
            );

        return $query->executeStatement();
    }

    public function deleteUrlAliasesWithBrokenLink(): int
    {
        $existingAliasesQuery = $this->getAllUrlAliasesQuery();

        $query = $this->connection->createQueryBuilder();
        $query
            ->delete($this->connection->quoteIdentifier($this->table))
            ->where(
                $query->expr()->neq('id', 'link')
            )
            ->andWhere(
                $query->expr()->notIn(
                    'link',
                    $existingAliasesQuery
                )
            );

        return (int)$query->executeStatement();
    }

    public function repairBrokenUrlAliasesForLocation(int $locationId): void
    {
        $urlAliasesData = $this->getUrlAliasesForLocation($locationId);

        $originalUrlAliases = $this->filterOriginalAliases($urlAliasesData);

        if (count($originalUrlAliases) === count($urlAliasesData)) {
            // no archived aliases - nothing to fix
            return;
        }

        $updateQueryBuilder = $this->connection->createQueryBuilder();
        $expr = $updateQueryBuilder->expr();
        $updateQueryBuilder
            ->update($this->connection->quoteIdentifier($this->table))
            ->set('link', ':linkId')
            ->set('parent', ':newParentId')
            ->where(
                $expr->eq('action', ':action')
            )
            ->andWhere(
                $expr->eq(
                    'is_original',
                    $updateQueryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq('parent', ':oldParentId')
            )
            ->andWhere(
                $expr->eq('text_md5', ':textMD5')
            )
            ->setParameter('action', "eznode:{$locationId}");

        foreach ($urlAliasesData as $urlAliasData) {
            $languageSetKey = $this->buildLanguageSetKey((int)$urlAliasData['parent'], $urlAliasData['text_md5']);
            if ($urlAliasData['is_original'] === 1 || !isset($originalUrlAliases[$languageSetKey])) {
                // ignore non-archived entries and deleted Translations
                continue;
            }

            $originalUrlAlias = $originalUrlAliases[$languageSetKey];

            if ($urlAliasData['link'] === $originalUrlAlias['link']) {
                // ignore correct entries to avoid unnecessary updates
                continue;
            }

            $updateQueryBuilder
                ->setParameter('linkId', $originalUrlAlias['link'], ParameterType::INTEGER)
                // attempt to fix missing parent case
                ->setParameter(
                    'newParentId',
                    $urlAliasData['existing_parent'] ?? $originalUrlAlias['parent'],
                    ParameterType::INTEGER
                )
                ->setParameter('oldParentId', $urlAliasData['parent'], ParameterType::INTEGER)
                ->setParameter('textMD5', $urlAliasData['text_md5']);

            try {
                $updateQueryBuilder->executeStatement();
            } catch (UniqueConstraintViolationException $e) {
                // edge case: if such row already exists, there's no way to restore history
                $this->deleteRow((int) $urlAliasData['parent'], $urlAliasData['text_md5']);
            }
        }
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteUrlNopAliasesWithoutChildren(): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();

        // The wrapper select is needed for SQL "Derived Table Merge" issue for deleting
        $wrapperQueryBuilder = clone $queryBuilder;
        $selectQueryBuilder = clone $queryBuilder;
        $expressionBuilder = $queryBuilder->expr();

        $selectQueryBuilder
            ->select('u_parent.id AS inner_id')
            ->from($this->table, 'u_parent')
            ->leftJoin(
                'u_parent',
                $this->table,
                'u',
                $expressionBuilder->eq('u_parent.id', 'u.parent')
            )
            ->where(
                $expressionBuilder->eq(
                    'u_parent.action_type',
                    ':actionType'
                )
            )
            ->groupBy('u_parent.id')
            ->having(
                $expressionBuilder->eq('COUNT(u.id)', 0)
            );

        $wrapperQueryBuilder
            ->select('inner_id')
            ->from(
                sprintf('(%s)', $selectQueryBuilder),
                'wrapper'
            )
            ->where('id = inner_id');

        $queryBuilder
            ->delete($this->table)
            ->where(
                sprintf('EXISTS (%s)', $wrapperQueryBuilder)
            )
            ->setParameter('actionType', self::NOP);

        return $queryBuilder->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAllChildrenAliases(int $parentId): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expressionBuilder = $queryBuilder->expr();

        $queryBuilder
            ->select('parent', 'text_md5')
            ->from($this->table)
            ->where(
                $expressionBuilder->eq(
                    'parent',
                    $queryBuilder->createPositionalParameter($parentId, ParameterType::INTEGER)
                )
            )->andWhere(
                $expressionBuilder->eq(
                    'is_alias',
                    $queryBuilder->createPositionalParameter(1, ParameterType::INTEGER)
                )
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Filter from the given result set original (current) only URL aliases and index them by their
     * real (non-always-available) language id set - the relational replacement for indexing by
     * "lang_mask" (each distinct language set can have one URL Alias).
     *
     * @param array $urlAliasesData
     */
    private function filterOriginalAliases(array $urlAliasesData): array
    {
        $originalUrlAliases = array_filter(
            $urlAliasesData,
            static function ($urlAliasData): bool {
                // filter is_original=true ignoring broken parent records (cleaned up elsewhere)
                return (bool)$urlAliasData['is_original'] && $urlAliasData['existing_parent'] !== null;
            }
        );

        $keyedUrlAliases = [];
        foreach ($originalUrlAliases as $urlAliasData) {
            $languageSetKey = $this->buildLanguageSetKey((int)$urlAliasData['parent'], $urlAliasData['text_md5']);
            $keyedUrlAliases[$languageSetKey] = $urlAliasData;
        }

        return $keyedUrlAliases;
    }

    /**
     * Builds a stable identity key for the real (non-always-available) language id set a specific
     * alias row is translated into - used to match an archived alias row to the still-current alias
     * row covering the same languages.
     */
    private function buildLanguageSetKey(int $parent, string $textMD5): string
    {
        $languageIds = $this->loadTranslationLanguageIds($parent, $textMD5);
        sort($languageIds);

        return implode(',', $languageIds);
    }

    /**
     * Get sub-query for IDs of all URL aliases.
     */
    private function getAllUrlAliasesQuery(): string
    {
        $existingAliasesQueryBuilder = $this->connection->createQueryBuilder();
        $innerQueryBuilder = $this->connection->createQueryBuilder();

        return $existingAliasesQueryBuilder
            ->select('tmp.id')
            ->from(
                // nest sub-query to avoid same-table update error
                '(' . $innerQueryBuilder->select('id')->from(
                    $this->connection->quoteIdentifier($this->table)
                )->getSQL() . ')',
                'tmp'
            )
            ->getSQL();
    }

    /**
     * Get DBMS-specific integer type.
     */
    private function getIntegerType(): string
    {
        return $this->getDatabasePlatform() instanceof AbstractMySQLPlatform
            ? 'signed'
            : 'integer';
    }

    /**
     * Get all URL aliases for the given Location (including archived ones).
     */
    private function getUrlAliasesForLocation(int $locationId): array
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select(
                't1.id',
                't1.is_original',
                't1.link',
                't1.parent',
                // show existing parent only if its row exists, special case for root parent
                'CASE t1.parent WHEN 0 THEN 0 ELSE t2.id END AS existing_parent',
                't1.text_md5'
            )
            ->from($this->connection->quoteIdentifier($this->table), 't1')
            // selecting t2.id above will result in null if parent is broken
            ->leftJoin(
                't1',
                $this->connection->quoteIdentifier($this->table),
                't2',
                $queryBuilder->expr()->eq('t1.parent', 't2.id')
            )
            ->where(
                $queryBuilder->expr()->eq(
                    't1.action',
                    $queryBuilder->createPositionalParameter("eznode:{$locationId}")
                )
            );

        return $queryBuilder->executeQuery()->fetchAllAssociative();
    }

    /**
     * Delete URL alias row by its primary composite key.
     */
    private function deleteRow(int $parentId, string $textMD5): int
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->delete($this->connection->quoteIdentifier($this->table))
            ->where(
                $expr->eq(
                    'parent',
                    $queryBuilder->createPositionalParameter($parentId, ParameterType::INTEGER)
                )
            )
            ->andWhere(
                $expr->eq(
                    'text_md5',
                    $queryBuilder->createPositionalParameter($textMD5)
                )
            )
        ;

        return $queryBuilder->executeStatement();
    }

    private function getDatabasePlatform(): AbstractPlatform
    {
        try {
            return $this->connection->getDatabasePlatform();
        } catch (Exception $e) {
            throw DatabaseException::wrap($e);
        }
    }

    /**
     * Builds an "at least one of $languageIds is a real translation of this row" condition against
     * "ibexa_url_alias_ml_translation", correlated via $parentColumn/$textMd5Column (must be
     * qualified with $query's own table alias) - the relational replacement for bitwise-AND-ing a
     * row's "lang_mask".
     *
     * $parentColumn/$textMd5Column must be alias-qualified (e.g. "u.parent"), never bare column
     * names - "ibexa_url_alias_ml_translation" has its own same-named columns, so an unqualified
     * reference inside the subquery would resolve to its own column (the innermost scope), silently
     * turning the correlation into a tautology instead of a real join back to the outer row.
     *
     * @param int[] $languageIds
     */
    private function buildTranslationExistsCondition(
        \Doctrine\DBAL\Query\QueryBuilder $query,
        string $parentColumn,
        string $textMd5Column,
        array $languageIds
    ): string {
        if (empty($languageIds)) {
            // No language can ever match an empty set - mirrors the old mask check being
            // vacuously false for a zero mask.
            return '1 = 0';
        }

        $translationExistsSubQuery = $this->connection->createQueryBuilder();
        $translationExistsSubQuery
            ->select('1')
            ->from('ibexa_url_alias_ml_translation', 'ut')
            ->where(
                $translationExistsSubQuery->expr()->and(
                    "ut.parent = {$parentColumn}",
                    "ut.text_md5 = {$textMd5Column}",
                    $translationExistsSubQuery->expr()->in(
                        'ut.language_id',
                        $query->createPositionalParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        return sprintf('EXISTS (%s)', $translationExistsSubQuery->getSQL());
    }
}
