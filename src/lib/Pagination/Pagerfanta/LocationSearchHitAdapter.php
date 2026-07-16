<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

/**
 * Pagerfanta adapter for Ibexa location search.
 * Will return results as SearchHit objects.
 *
 * @extends \Ibexa\Core\Pagination\Pagerfanta\AbstractSearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 */
class LocationSearchHitAdapter extends AbstractSearchResultAdapter
{
    public function __construct(
        LocationQuery $query,
        SearchService $searchService,
        array $languageFilter = []
    ) {
        parent::__construct($query, $searchService, $languageFilter);
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function executeQuery(
        SearchService $searchService,
        Query $query,
        array $languageFilter
    ): SearchResult {
        assert($query instanceof LocationQuery);

        return $searchService->findLocations($query, $languageFilter);
    }
}
