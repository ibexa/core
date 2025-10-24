<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Pagerfanta adapter for Ibexa content search.
 * Will return results as Location objects.
 *
 * @implements \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 * @implements \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
class LocationSearchAdapter implements AdapterInterface, SearchResultAdapter
{
    private LocationSearchHitAdapter $locationSearchHitAdapter;

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    public function __construct(
        LocationQuery $query,
        SearchService $searchService,
        array $languageFilter = []
    ) {
        $this->locationSearchHitAdapter = new LocationSearchHitAdapter($query, $searchService, $languageFilter);
    }

    /**
     * Returns a slice of the results as Location objects.
     *
     * @phpstan-return iterable<int<0, max>, Location>
     */
    public function getSlice(
        int $offset,
        int $length
    ): iterable {
        $list = [];
        foreach ($this->locationSearchHitAdapter->getSlice($offset, $length) as $hit) {
            $list[] = $hit->valueObject;
        }

        return $list;
    }

    public function getNbResults(): int
    {
        return $this->locationSearchHitAdapter->getNbResults();
    }

    public function getAggregations(): AggregationResultCollection
    {
        return $this->locationSearchHitAdapter->getAggregations();
    }

    public function getTime(): ?float
    {
        return $this->locationSearchHitAdapter->getTime();
    }

    public function getTimedOut(): ?bool
    {
        return $this->locationSearchHitAdapter->getTimedOut();
    }

    public function getMaxScore(): ?float
    {
        return $this->locationSearchHitAdapter->getMaxScore();
    }
}
