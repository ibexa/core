<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway;
use RuntimeException;

/**
 * @phpstan-type TKeywordIdMap array<string, int>
 *
 * A keyword map (TKeywordIdMap) has the following format:
 * ```
 * [
 *     '<keyword>' => <id>,
 *     // ...
 * ];
 * ```
 */
class DoctrineStorage extends Gateway
{
    public const string KEYWORD_TABLE = 'ezkeyword';
    public const string KEYWORD_ATTRIBUTE_LINK_TABLE = 'ezkeyword_attribute_link';
    private const string CONTENT_TYPE_ID_PARAM_NAME = 'contentTypeId';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Stores the keyword list from $field->value->externalData.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function storeFieldData(Field $field, int $contentTypeId): void
    {
        if (empty($field->value->externalData) && !empty($field->id)) {
            $this->deleteFieldData($field->id, $field->versionNo);

            return;
        }

        $existingKeywordMap = $this->getExistingKeywords(
            $field->value->externalData,
            $contentTypeId
        );

        $this->deleteOldKeywordAssignments($field->id, $field->versionNo);

        $this->assignKeywords(
            $field->id,
            $this->insertKeywords(
                array_diff_key(
                    array_fill_keys($field->value->externalData, 0),
                    $existingKeywordMap
                ),
                $contentTypeId
            ) + $existingKeywordMap,
            $field->versionNo
        );

        $this->deleteOrphanedKeywords();
    }

    /**
     * Sets the list of assigned keywords into $field->value->externalData.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getFieldData(Field $field): void
    {
        $field->value->externalData = $this->getAssignedKeywords($field->id, $field->versionNo);
    }

    /**
     * Retrieve the ContentType ID for the given $field.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function getContentTypeId(Field $field): int
    {
        return $this->loadContentTypeId($field->fieldDefinitionId);
    }

    /**
     * Deletes keyword data for the given $fieldId.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteFieldData(int $fieldId, int $versionNo): void
    {
        $this->deleteOldKeywordAssignments($fieldId, $versionNo);
        $this->deleteOrphanedKeywords();
    }

    /**
     * Returns a list of keywords assigned to $fieldId.
     *
     * @return string[]
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getAssignedKeywords(int $fieldId, int $versionNo): array
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select($this->connection->quoteIdentifier('keyword'))
            ->from($this->connection->quoteIdentifier(self::KEYWORD_TABLE), 'kwd')
            ->innerJoin(
                'kwd',
                $this->connection->quoteIdentifier(self::KEYWORD_ATTRIBUTE_LINK_TABLE),
                'attr',
                $expr->eq(
                    $this->connection->quoteIdentifier('kwd.id'),
                    $this->connection->quoteIdentifier('attr.keyword_id')
                )
            )
            ->where(
                $expr->eq(
                    $this->connection->quoteIdentifier('attr.objectattribute_id'),
                    ':field_id'
                )
            )
            ->andWhere(
                $expr->eq(
                    $this->connection->quoteIdentifier('attr.version'),
                    ':version_no'
                )
            )
            ->orderBy('kwd.id')
            ->setParameter('field_id', $fieldId, ParameterType::INTEGER)
            ->setParameter('version_no', $versionNo, ParameterType::INTEGER);

        return $query->executeQuery()->fetchFirstColumn();
    }

    /**
     * Retrieves the content type ID for the given $fieldDefinitionId.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function loadContentTypeId(int $fieldDefinitionId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select($this->connection->quoteIdentifier('contentclass_id'))
            ->from($this->connection->quoteIdentifier('ezcontentclass_attribute'))
            ->where(
                $query->expr()->eq('id', ':fieldDefinitionId')
            )
            ->setParameter('fieldDefinitionId', $fieldDefinitionId);

        $statement = $query->executeQuery();

        $row = $statement->fetchAssociative();

        if ($row === false) {
            throw new RuntimeException(
                sprintf(
                    'Content type ID cannot be retrieved based on the Field definition ID "%s"',
                    $fieldDefinitionId
                )
            );
        }

        return (int) $row['contentclass_id'];
    }

    /**
     * Returns already existing keywords from $keywordList as a map.
     *
     * @param string[] $keywordList
     *
     * @phpstan-return TKeywordIdMap
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getExistingKeywords(array $keywordList, int $contentTypeId): array
    {
        // Retrieving potentially existing keywords
        $query = $this->connection->createQueryBuilder();
        $query
            ->select(
                $this->connection->quoteIdentifier('id'),
                $this->connection->quoteIdentifier('keyword')
            )
            ->from($this->connection->quoteIdentifier(self::KEYWORD_TABLE))
            ->where(
                $query->expr()->and(
                    $query->expr()->in(
                        $this->connection->quoteIdentifier('keyword'),
                        ':keywordList'
                    ),
                    $query->expr()->eq(
                        $this->connection->quoteIdentifier('class_id'),
                        ':' . self::CONTENT_TYPE_ID_PARAM_NAME
                    )
                )
            )
            ->setParameter('keywordList', $keywordList, ArrayParameterType::STRING)
            ->setParameter(self::CONTENT_TYPE_ID_PARAM_NAME, $contentTypeId);

        $statement = $query->executeQuery();

        $existingKeywordMap = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            // filter out keywords that aren't the exact match (e.g. differ by case)
            if (!in_array($row['keyword'], $keywordList, true)) {
                continue;
            }
            $existingKeywordMap[$row['keyword']] = $row['id'];
        }

        return $existingKeywordMap;
    }

    /**
     * Inserts $keywordsToInsert for $fieldDefinitionId and returns a map of
     * these keywords to their ID.
     *
     * @phpstan-param TKeywordIdMap $keywordsToInsert
     *
     * @phpstan-return TKeywordIdMap
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function insertKeywords(array $keywordsToInsert, int $contentTypeId): array
    {
        $keywordIdMap = [];
        // Inserting keywords not yet registered
        if (!empty($keywordsToInsert)) {
            $insertQuery = $this->connection->createQueryBuilder();
            $insertQuery
                ->insert($this->connection->quoteIdentifier(self::KEYWORD_TABLE))
                ->values(
                    [
                        $this->connection->quoteIdentifier('class_id') => ':' . self::CONTENT_TYPE_ID_PARAM_NAME,
                        $this->connection->quoteIdentifier('keyword') => ':keyword',
                    ]
                )
                ->setParameter(self::CONTENT_TYPE_ID_PARAM_NAME, $contentTypeId, ParameterType::INTEGER);

            foreach (array_keys($keywordsToInsert) as $keyword) {
                $insertQuery->setParameter('keyword', $keyword);
                $insertQuery->executeStatement();
                $keywordIdMap[$keyword] = (int)$this->connection->lastInsertId(
                    $this->getSequenceName(self::KEYWORD_TABLE, 'id')
                );
            }
        }

        return $keywordIdMap;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    protected function deleteOldKeywordAssignments(int $fieldId, int $versionNo): void
    {
        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::KEYWORD_ATTRIBUTE_LINK_TABLE))
            ->where(
                $deleteQuery->expr()->and(
                    $deleteQuery->expr()->eq(
                        $this->connection->quoteIdentifier('objectattribute_id'),
                        ':fieldId'
                    ),
                    $deleteQuery->expr()->eq(
                        $this->connection->quoteIdentifier('version'),
                        ':versionNo'
                    )
                )
            )
            ->setParameter('fieldId', $fieldId, ParameterType::INTEGER)
            ->setParameter('versionNo', $versionNo, ParameterType::INTEGER);

        $deleteQuery->executeStatement();
    }

    /**
     * Assigns keywords from $keywordMap to the field with $fieldId and specific $versionNo.
     *
     * @phpstan-param TKeywordIdMap $keywordMap keyword map
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function assignKeywords(int $fieldId, array $keywordMap, int $versionNo): void
    {
        $insertQuery = $this->connection->createQueryBuilder();
        $insertQuery
            ->insert($this->connection->quoteIdentifier(self::KEYWORD_ATTRIBUTE_LINK_TABLE))
            ->values(
                [
                    $this->connection->quoteIdentifier('keyword_id') => ':keywordId',
                    $this->connection->quoteIdentifier('objectattribute_id') => ':fieldId',
                    $this->connection->quoteIdentifier('version') => ':versionNo',
                ]
            )
        ;

        foreach ($keywordMap as $keywordId) {
            $insertQuery
                ->setParameter('keywordId', $keywordId, ParameterType::INTEGER)
                ->setParameter('fieldId', $fieldId, ParameterType::INTEGER)
                ->setParameter('versionNo', $versionNo, ParameterType::INTEGER);

            $insertQuery->executeStatement();
        }
    }

    /**
     * Deletes all orphaned keywords.
     *
     * Keyword is orphaned if it is not linked to a content attribute through ezkeyword_attribute_link table.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function deleteOrphanedKeywords(): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select($this->connection->quoteIdentifier('kwd.id'))
            ->from($this->connection->quoteIdentifier(self::KEYWORD_TABLE), 'kwd')
            ->leftJoin(
                'kwd',
                $this->connection->quoteIdentifier(self::KEYWORD_ATTRIBUTE_LINK_TABLE),
                'attr',
                $query->expr()->eq(
                    $this->connection->quoteIdentifier('attr.keyword_id'),
                    $this->connection->quoteIdentifier('kwd.id')
                )
            )
            ->where($query->expr()->isNull('attr.id'));

        $statement = $query->executeQuery();
        $ids = $statement->fetchFirstColumn();

        if (empty($ids)) {
            return;
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::KEYWORD_TABLE))
            ->where(
                $deleteQuery->expr()->in($this->connection->quoteIdentifier('id'), ':ids')
            )
            ->setParameter('ids', $ids, ArrayParameterType::INTEGER);

        $deleteQuery->executeStatement();
    }
}
