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

abstract class AbstractSearchResultAdapter implements AdapterInterface, SearchResultAdapter
{
    /** @var \Ibexa\Contracts\Core\Repository\SearchService */
    private $searchService;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query */
    private $query;

    /** @var array */
    private $languageFilter;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection|null */
    private $aggregations;

    /** @var float|null */
    private $time;

    /** @var bool|null */
    private $timedOut;

    /** @var float|null */
    private $maxScore;

    /** @var int|null */
    private $totalCount;

    private ?SpellcheckResult $spellcheck = null;

    public function __construct(Query $query, SearchService $searchService, array $languageFilter = [])
    {
        $this->query = $query;
        $this->searchService = $searchService;
        $this->languageFilter = $languageFilter;
    }

    /**
     * Returns the number of results.
     *
     * @return int The number of results.
     */
    public function getNbResults()
    {
        if (isset($this->totalCount)) {
            return $this->totalCount;
        }

        $countQuery = clone $this->query;
        $countQuery->limit = 0;
        // Skip facets/aggregations & spellcheck computing
        $countQuery->facetBuilders = [];
        $countQuery->aggregations = [];
        $countQuery->spellcheck = null;

        $searchResults = $this->executeQuery(
            $this->searchService,
            $countQuery,
            $this->languageFilter
        );

        return $this->totalCount = $searchResults->totalCount;
    }

    /**
     * Returns a slice of the results, as SearchHit objects.
     *
     * @param int $offset The offset.
     * @param int $length The length.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit[]
     */
    public function getSlice($offset, $length)
    {
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

    abstract protected function executeQuery(
        SearchService $searchService,
        Query $query,
        array $languageFilter
    ): SearchResult;
}

class_alias(AbstractSearchResultAdapter::class, 'eZ\Publish\Core\Pagination\Pagerfanta\AbstractSearchResultAdapter');
