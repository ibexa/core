<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

/**
 * @template TSearchHitValueObject of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 *
 * @implements \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit<TSearchHitValueObject>>
 */
final class FixedSearchResultHitAdapter implements SearchResultAdapter
{
    /** @phpstan-var \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult<TSearchHitValueObject> */
    private SearchResult $searchResult;

    /**
     * @phpstan-param \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult<TSearchHitValueObject> $searchResult
     */
    public function __construct(SearchResult $searchResult)
    {
        $this->searchResult = $searchResult;
    }

    public function getNbResults(): int
    {
        return $this->searchResult->totalCount ?? -1;
    }

    public function getSlice(int $offset, int $length): iterable
    {
        return $this->searchResult->searchHits;
    }

    public function getAggregations(): AggregationResultCollection
    {
        return $this->searchResult->getAggregations();
    }

    public function getTime(): ?float
    {
        return $this->searchResult->time;
    }

    public function getTimedOut(): ?bool
    {
        return $this->searchResult->timedOut;
    }

    public function getMaxScore(): ?float
    {
        return $this->searchResult->maxScore;
    }
}
