<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SpellcheckResult;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @template T of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 *
 * @phpstan-implements \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit<T>>
 * @phpstan-implements \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit<T>>
 */
abstract class AbstractSearchResultAdapter implements AdapterInterface, SearchResultAdapter
{
    private SearchService $searchService;

    private Query $query;

    /** @phpstan-var TSearchLanguageFilter */
    private array $languageFilter;

    private ?AggregationResultCollection $aggregations = null;

    private ?float $time;

    private ?bool $timedOut;

    private ?float $maxScore;

    /** @phpstan-var int<0, max>|null */
    private ?int $totalCount;

    private ?SpellcheckResult $spellcheck = null;

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    public function __construct(
        Query $query,
        SearchService $searchService,
        array $languageFilter = []
    ) {
        $this->query = $query;
        $this->searchService = $searchService;
        $this->languageFilter = $languageFilter;
    }

    public function getNbResults(): int
    {
        if (isset($this->totalCount)) {
            return $this->totalCount;
        }

        $countQuery = clone $this->query;
        $countQuery->limit = 0;
        // Skip aggregations & spellcheck computing
        $countQuery->aggregations = [];
        $countQuery->spellcheck = null;

        $searchResults = $this->executeQuery(
            $this->searchService,
            $countQuery,
            $this->languageFilter
        );

        return $this->totalCount = $searchResults->totalCount ?? 0;
    }

    /**
     * Returns a slice of the results, as SearchHit objects.
     */
    public function getSlice(
        int $offset,
        int $length
    ): iterable {
        $query = clone $this->query;
        $query->offset = $offset;
        $query->limit = $length;
        $query->performCount = false;

        $searchResult = $this->executeQuery(
            $this->searchService,
            $query,
            $this->languageFilter
        );

        $this->aggregations = $searchResult->getAggregations();
        $this->time = $searchResult->time;
        $this->timedOut = $searchResult->timedOut;
        $this->maxScore = $searchResult->maxScore;
        $this->spellcheck = $searchResult->getSpellcheck();

        // Set count for further use if returned by search engine despite !performCount (Solr, ES)
        if (!isset($this->totalCount) && isset($searchResult->totalCount)) {
            $this->totalCount = $searchResult->totalCount;
        }

        return $searchResult->searchHits;
    }

    public function getAggregations(): AggregationResultCollection
    {
        if ($this->aggregations === null) {
            $aggregationQuery = clone $this->query;
            $aggregationQuery->offset = 0;
            $aggregationQuery->limit = 0;
            $aggregationQuery->spellcheck = null;

            $searchResults = $this->executeQuery(
                $this->searchService,
                $aggregationQuery,
                $this->languageFilter
            );

            $this->aggregations = $searchResults->aggregations;
        }

        return $this->aggregations;
    }

    public function getSpellcheck(): ?SpellcheckResult
    {
        if ($this->spellcheck === null) {
            $spellcheckQuery = clone $this->query;
            $spellcheckQuery->offset = 0;
            $spellcheckQuery->limit = 0;
            $spellcheckQuery->aggregations = [];

            $searchResults = $this->executeQuery(
                $this->searchService,
                $spellcheckQuery,
                $this->languageFilter
            );

            $this->spellcheck = $searchResults->spellcheck;
        }

        return $this->spellcheck;
    }

    public function getTime(): ?float
    {
        return $this->time;
    }

    public function getTimedOut(): ?bool
    {
        return $this->timedOut;
    }

    public function getMaxScore(): ?float
    {
        return $this->maxScore;
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @phpstan-return SearchResult<T>
     */
    abstract protected function executeQuery(
        SearchService $searchService,
        Query $query,
        array $languageFilter
    ): SearchResult;
}
