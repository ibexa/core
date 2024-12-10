<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta\AdapterFactory;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Pagination\Pagerfanta\ContentSearchHitAdapter;
use Ibexa\Core\Pagination\Pagerfanta\FixedSearchResultHitAdapter;
use Ibexa\Core\Pagination\Pagerfanta\LocationSearchHitAdapter;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * @internal
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
final class SearchHitAdapterFactory implements SearchHitAdapterFactoryInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\SearchService */
    private $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @phpstan-return \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\ValueObject>
     */
    public function createAdapter(Query $query, array $languageFilter = []): AdapterInterface
    {
        if ($query instanceof LocationQuery) {
            return new LocationSearchHitAdapter($query, $this->searchService, $languageFilter);
        }

        return new ContentSearchHitAdapter($query, $this->searchService, $languageFilter);
    }

    /**
     * @phpstan-return \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\ValueObject>
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function createFixedAdapter(Query $query, array $languageFilter = []): AdapterInterface
    {
        if ($query instanceof LocationQuery) {
            $searchResults = $this->searchService->findLocations($query, $languageFilter);
        } else {
            $searchResults = $this->searchService->findContent($query, $languageFilter);
        }

        return new FixedSearchResultHitAdapter($searchResults);
    }
}
