<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\WordIndexer\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A service encapsulating database operations on ezsearch* tables.
 */
class SearchIndex
{
    public const SEARCH_WORD_TABLE = 'ibexa_search_word';
    public const SEARCH_OBJECT_WORD_LINK_TABLE = 'ibexa_search_object_word_link';

    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Fetch already indexed words from database (legacy db table: ibexa_search_word).
     *
     * @param string[] $words
     */
    public function getWords(array $words): array
    {
        $query = $this->connection->createQueryBuilder();

        $query
            ->select('*')
            ->from(self::SEARCH_WORD_TABLE)
            ->where($query->expr()->in('word', ':words'))
            // use array_map as some DBMS-es do not cast integers to strings by default
            ->setParameter('words', array_map('strval', $words), Connection::PARAM_STR_ARRAY);

        return $query->executeQuery()->fetchAll(FetchMode::ASSOCIATIVE);
    }

    /**
     * Increase the object count of the given words by one.
     *
     * @param int[] $wordId
     */
    public function incrementWordObjectCount(array $wordId): void
    {
        $this
            ->getWordUpdateQuery($wordId)
            ->set('object_count', 'object_count + 1')
            ->executeStatement();
    }

    /**
     * Decrease the object count of the given words by one.
     *
     * @param int[] $wordId
     */
    public function decrementWordObjectCount(array $wordId): void
    {
        $this
            ->getWordUpdateQuery($wordId)
            ->set('object_count', 'object_count - 1')
            ->executeStatement();
    }

    /**
     * Insert new words (legacy db table: ibexa_search_word).
     *
     * @param string[] $words
     */
    public function addWords(array $words): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::SEARCH_WORD_TABLE)
            ->values(
                [
                    'word' => ':word',
                    'object_count' => ':one',
                ]
            )
            ->setParameter('one', 1, ParameterType::INTEGER);

        foreach ($words as $word) {
            $query->setParameter('word', $word);
            $query->executeStatement();
        }
    }

    /**
     * Remove entire search index.
     */
    public function purge(): void
    {
        $this->connection->beginTransaction();
        $searchIndexTables = [
            self::SEARCH_OBJECT_WORD_LINK_TABLE,
            self::SEARCH_WORD_TABLE,
        ];
        foreach ($searchIndexTables as $tableName) {
            $this->connection
                ->createQueryBuilder()
                ->delete($tableName)
                ->executeStatement();
        }
        $this->connection->commit();
    }

    /**
     * Link word with specific content object (legacy db table: ibexa_search_object_word_link).
     */
    public function addObjectWordLink(
        int $wordId,
        int $contentId,
        float $frequency,
        int $placement,
        int $nextWordId,
        int $prevWordId,
        int $contentTypeId,
        int $fieldTypeId,
        int $published,
        int $sectionId,
        string $identifier,
        int $integerValue,
        int $languageMask
    ): void {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::SEARCH_OBJECT_WORD_LINK_TABLE)
            ->values(
                [
                    'word_id' => $query->createPositionalParameter($wordId, ParameterType::INTEGER),
                    'contentobject_id' => $query->createPositionalParameter(
                        $contentId,
                        ParameterType::INTEGER
                    ),
                    'frequency' => $query->createPositionalParameter($frequency),
                    'placement' => $query->createPositionalParameter(
                        $placement,
                        ParameterType::INTEGER
                    ),
                    'next_word_id' => $query->createPositionalParameter(
                        $nextWordId,
                        ParameterType::INTEGER
                    ),
                    'prev_word_id' => $query->createPositionalParameter(
                        $prevWordId,
                        ParameterType::INTEGER
                    ),
                    'content_type_id' => $query->createPositionalParameter(
                        $contentTypeId,
                        ParameterType::INTEGER
                    ),
                    'content_type_field_definition_id' => $query->createPositionalParameter(
                        $fieldTypeId,
                        ParameterType::INTEGER
                    ),
                    'published' => $query->createPositionalParameter(
                        $published,
                        ParameterType::INTEGER
                    ),
                    'section_id' => $query->createPositionalParameter(
                        $sectionId,
                        ParameterType::INTEGER
                    ),
                    'identifier' => $query->createPositionalParameter(
                        $identifier,
                        ParameterType::STRING
                    ),
                    'integer_value' => $query->createPositionalParameter(
                        $integerValue,
                        ParameterType::INTEGER
                    ),
                    'language_mask' => $query->createPositionalParameter(
                        $languageMask,
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();
    }

    /**
     * Get all words related to the content object (legacy db table: ibexa_search_object_word_link).
     */
    public function getContentObjectWords(int $contentId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('word_id')
            ->from(self::SEARCH_OBJECT_WORD_LINK_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAll(FetchMode::COLUMN);
    }

    /**
     * Delete words not related to any content object.
     */
    public function deleteWordsWithoutObjects(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::SEARCH_WORD_TABLE)
            ->where(
                $query->expr()->eq(
                    'object_count',
                    $query->createPositionalParameter(0, ParameterType::INTEGER)
                )
            );

        return $query->executeStatement();
    }

    /**
     * Delete relation between a word and a content object.
     */
    public function deleteObjectWordsLink(int $contentId): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::SEARCH_OBJECT_WORD_LINK_TABLE)
            ->where(
                $query->expr()->eq(
                    'contentobject_id',
                    $query->createPositionalParameter($contentId, ParameterType::INTEGER)
                )
            );

        return $query->executeStatement();
    }

    /**
     * Build ibexa_search_word update query, without any columns set.
     *
     * @param array $wordIds list of word IDs
     */
    private function getWordUpdateQuery(array $wordIds): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::SEARCH_WORD_TABLE)
            ->where(
                $query->expr()->in(
                    'id',
                    $query->createPositionalParameter($wordIds, Connection::PARAM_INT_ARRAY)
                )
            );

        return $query;
    }
}
