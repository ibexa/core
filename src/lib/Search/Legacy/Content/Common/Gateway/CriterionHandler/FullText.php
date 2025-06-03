<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Language\MaskGenerator;
use Ibexa\Core\Persistence\TransformationProcessor;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use Ibexa\Core\Search\Legacy\Content\WordIndexer\Repository\SearchIndex;

/**
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
class FullText extends CriterionHandler
{
    /** @var array<string, mixed> */
    protected array $configuration = [
        // @see getStopWordThresholdValue()
        'stopWordThresholdFactor' => 0.66,
        'enableWildcards' => true,
        'commands' => [
            'apostrophe_normalize',
            'apostrophe_to_doublequote',
            'ascii_lowercase',
            'ascii_search_cleanup',
            'cyrillic_diacritical',
            'cyrillic_lowercase',
            'cyrillic_search_cleanup',
            'cyrillic_transliterate_ascii',
            'doublequote_normalize',
            'endline_search_normalize',
            'greek_diacritical',
            'greek_lowercase',
            'greek_normalize',
            'greek_transliterate_ascii',
            'hebrew_transliterate_ascii',
            'hyphen_normalize',
            'inverted_to_normal',
            'latin1_diacritical',
            'latin1_lowercase',
            'latin1_transliterate_ascii',
            'latin-exta_diacritical',
            'latin-exta_lowercase',
            'latin-exta_transliterate_ascii',
            'latin_lowercase',
            'latin_search_cleanup',
            'latin_search_decompose',
            'math_to_ascii',
            'punctuation_normalize',
            'space_normalize',
            'special_decompose',
            'specialwords_search_normalize',
            'tab_search_normalize',
        ],
    ];

    /**
     * @see getStopWordThresholdValue()
     */
    private ?int $stopWordThresholdValue = null;

    /** @param array<string, mixed> $configuration */
    public function __construct(
        Connection $connection,
        protected TransformationProcessor $processor,
        private readonly MaskGenerator $languageMaskGenerator,
        array $configuration = []
    ) {
        parent::__construct($connection);

        $this->configuration = $configuration + $this->configuration;

        if (
            $this->configuration['stopWordThresholdFactor'] < 0 ||
            $this->configuration['stopWordThresholdFactor'] > 1
        ) {
            throw new InvalidArgumentException(
                "\$configuration['stopWordThresholdFactor']",
                'Stop Word Threshold Factor needs to be between 0 and 1, got: ' . $this->configuration['stopWordThresholdFactor']
            );
        }
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\FullText;
    }

    /**
     * Tokenize String.
     *
     * @return array<string>
     */
    protected function tokenizeString(string $string): array
    {
        $tokens = preg_split('/[^\w|*]/u', $string, -1, PREG_SPLIT_NO_EMPTY);

        return false !== $tokens ? $tokens : [];
    }

    /**
     * Get single word query expression.
     *
     * Depending on the configuration of the full text search criterion
     * converter wildcards are either transformed into the respective LIKE
     * queries, or everything is just compared using equal.
     */
    protected function getWordExpression(QueryBuilder $query, string $token): string
    {
        if ($this->configuration['enableWildcards'] && $token[0] === '*') {
            return $query->expr()->like(
                'word',
                $query->createNamedParameter('%' . substr($token, 1))
            );
        }

        if ($this->configuration['enableWildcards'] && $token[strlen($token) - 1] === '*') {
            return $query->expr()->like(
                'word',
                $query->createNamedParameter(substr($token, 0, -1) . '%')
            );
        }

        return $query->expr()->eq('word', $query->createNamedParameter($token));
    }

    /**
     * Get sub query to select relevant word IDs.
     *
     * @uses getStopWordThresholdValue To get threshold for words we would like to ignore in query.
     */
    protected function getWordIdSubquery(QueryBuilder $query, string $string): string
    {
        $subQuery = $this->connection->createQueryBuilder();
        $tokens = $this->tokenizeString(
            $this->processor->transform($string, $this->configuration['commands'])
        );
        $wordExpressions = [];
        foreach ($tokens as $token) {
            $wordExpressions[] = $this->getWordExpression($query, $token);
        }

        // Search for provided string itself as well
        $wordExpressions[] = $this->getWordExpression($query, $string);

        $whereCondition = $subQuery->expr()->or(...$wordExpressions);

        // If stop word threshold is below 100%, make it part of $whereCondition
        if ($this->configuration['stopWordThresholdFactor'] < 1) {
            $whereCondition = $subQuery->expr()->and(
                $whereCondition,
                $subQuery->expr()->lt(
                    'object_count',
                    $query->createNamedParameter($this->getStopWordThresholdValue(), ParameterType::STRING)
                )
            );
        }

        $subQuery
            ->select('id')
            ->from(SearchIndex::SEARCH_WORD_TABLE)
            ->where($whereCondition);

        return $subQuery->getSQL();
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\FullText $criterion
     *
     * @phpstan-param TSearchLanguageFilter $languageSettings
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        if (!$criterion instanceof Criterion\FullText) {
            throw new InvalidArgumentException('$criterion', 'Expected Criterion\FullText');
        }

        if (!is_string($criterion->value)) {
            throw new InvalidArgumentException('$criterion->value', 'must be a string');
        }

        $subSelect = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                SearchIndex::SEARCH_OBJECT_WORD_LINK_TABLE
            )->where(
                $expr->in(
                    'word_id',
                    // pass main Query Builder to set query parameters
                    $this->getWordIdSubquery($queryBuilder, $criterion->value)
                )
            );

        if (!empty($languageSettings['languages'])) {
            $languageMask = $this->languageMaskGenerator->generateLanguageMaskFromLanguageCodes(
                $languageSettings['languages'],
                $languageSettings['useAlwaysAvailable'] ?? true
            );

            $subSelect->andWhere(
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'ibexa_search_object_word_link.language_mask',
                        $queryBuilder->createNamedParameter($languageMask, ParameterType::INTEGER)
                    ),
                    $queryBuilder->createNamedParameter(0, ParameterType::INTEGER)
                )
            );
        }

        return $expr->in(
            'c.id',
            $subSelect->getSQL()
        );
    }

    /**
     * Returns an exact content object count threshold to ignore common terms on.
     *
     * Common terms will be skipped if used in more then a given percentage of the total amount of content
     * objects in the database. Percentage is defined by stopWordThresholdFactor configuration.
     *
     * Example: If stopWordThresholdFactor is 0.66 (66%), and a term like "the" exists in more then 66% of the content, it
     *          will ignore the phrase as it is assumed to not add any value ot the search.
     *
     * Caches the result for the instance used as we don't need this to be super accurate as it is based on percentage,
     * set by stopWordThresholdFactor.
     *
     * @return int
     */
    protected function getStopWordThresholdValue(): int
    {
        if ($this->stopWordThresholdValue !== null) {
            return $this->stopWordThresholdValue;
        }

        // Cached value does not exists, do a simple count query on ibexa_content table
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(ContentGateway::CONTENT_ITEM_TABLE);

        $count = (int)$query->executeQuery()->fetchOne();

        // Calculate the int stopWordThresholdValue based on count (first column) * factor
        return $this->stopWordThresholdValue = (int)($count * $this->configuration['stopWordThresholdFactor']);
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
