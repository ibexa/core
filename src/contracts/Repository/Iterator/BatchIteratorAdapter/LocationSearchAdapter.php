<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

/**
 * @extends \Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 */
final class LocationSearchAdapter extends AbstractSearchAdapter
{
    public function __construct(
        SearchService $searchService,
        LocationQuery $query,
        array $languageFilter = [],
        bool $filterOnUserPermissions = true
    ) {
        parent::__construct($searchService, $query, $languageFilter, $filterOnUserPermissions);
    }

    protected function executeSearch(Query $query): SearchResult
    {
        if (!$query instanceof LocationQuery) {
            throw new InvalidArgumentException(
                '$query',
                sprintf(
                    'Expected an instance of %s, got %s',
                    LocationQuery::class,
                    get_class($query)
                )
            );
        }

        return $this->searchService->findLocations(
            $query,
            $this->languageFilter,
            $this->filterOnUserPermissions
        );
    }
}
