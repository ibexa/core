<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Field;
use Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway as ContentTypeGateway;
use RuntimeException;

class DoctrineStorage extends Gateway
{
    public const KEYWORD_TABLE = 'ibexa_keyword';
    public const KEYWORD_ATTRIBUTE_LINK_TABLE = 'ibexa_keyword_field_link';

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Stores the keyword list from $field->value->externalData.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field
     * @param int $contentTypeId
     */
    public function storeFieldData(Field $field, $contentTypeId)
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
                    array_fill_keys($field->value->externalData, true),
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
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     */
    public function getFieldData(Field $field)
    {
        $field->value->externalData = $this->getAssignedKeywords($field->id, $field->versionNo);
    }

    /**
     * Retrieve the ContentType ID for the given $field.
     *
     * @param \Ibexa\Contracts\Core\Persistence\Content\Field $field
     *
     * @return int
     */
    public function getContentTypeId(Field $field)
    {
        return $this->loadContentTypeId($field->fieldDefinitionId);
    }

    /**
     * Deletes keyword data for the given $fieldId.
     *
     * @param int $fieldId
     * @param int $versionNo
     */
    public function deleteFieldData($fieldId, $versionNo)
    {
        $this->deleteOldKeywordAssignments($fieldId, $versionNo);
        $this->deleteOrphanedKeywords();
    }

    /**
     * Returns a list of keywords assigned to $fieldId.
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
     * @param int $fieldDefinitionId
     *
     * @return int
     */
    protected function loadContentTypeId($fieldDefinitionId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select($this->connection->quoteIdentifier('content_type_id'))
            ->from($this->connection->quoteIdentifier(ContentTypeGateway::FIELD_DEFINITION_TABLE))
            ->where(
                $query->expr()->eq('id', ':fieldDefinitionId')
            )
            ->setParameter('fieldDefinitionId', $fieldDefinitionId);

        $statement = $query->executeQuery();

        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            throw new RuntimeException(
                sprintf(
                    'Content type ID cannot be retrieved based on the Field definition ID "%s"',
                    $fieldDefinitionId
                )
            );
        }

        return (int) $row['content_type_id'];
    }

    /**
     * Returns already existing keywords from $keywordList as a map.
     *
     * The map has the following format:
     * <code>
     *  array(
     *      '<keyword>' => <id>,
     *      // ...
     *  );
     * </code>
     *
     * @param string[] $keywordList
     * @param int $contentTypeId
     *
     * @return int[]
     */
    protected function getExistingKeywords($keywordList, $contentTypeId)
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
                        ':contentTypeId'
                    )
                )
            )
            ->setParameter('keywordList', $keywordList, Connection::PARAM_STR_ARRAY)
            ->setParameter('contentTypeId', $contentTypeId);

        $statement = $query->executeQuery();

        $existingKeywordMap = [];
        foreach ($statement->fetchAllAssociative() as $row) {
            // filter out keywords that aren't the exact match (e.g. differ by case)
            if (!in_array($row['keyword'], $keywordList)) {
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
     * The returned array has the following format:
     * <code>
     *  array(
     *      '<keyword>' => <id>,
     *      // ...
     *  );
     * </code>
     *
     * @param string[] $keywordsToInsert
     * @param int $contentTypeId
     *
     * @return int[]
     */
    protected function insertKeywords(array $keywordsToInsert, $contentTypeId)
    {
        $keywordIdMap = [];
        // Inserting keywords not yet registered
        if (!empty($keywordsToInsert)) {
            $insertQuery = $this->connection->createQueryBuilder();
            $insertQuery
                ->insert($this->connection->quoteIdentifier(self::KEYWORD_TABLE))
                ->values(
                    [
                        $this->connection->quoteIdentifier('class_id') => ':contentTypeId',
                        $this->connection->quoteIdentifier('keyword') => ':keyword',
                    ]
                )
                ->setParameter('contentTypeId', $contentTypeId, \PDO::PARAM_INT);

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
     * $keywordMap has the format:
     * <code>
     *  array(
     *      '<keyword>' => <id>,
     *      // ...
     *  );
     * </code>
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

        foreach ($keywordMap as $keyword => $keywordId) {
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
     * Keyword is orphaned if it is not linked to a content attribute through
     * ibexa_keyword_field_link table.
     */
    protected function deleteOrphanedKeywords()
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
            ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

        $deleteQuery->executeStatement();
    }
}
