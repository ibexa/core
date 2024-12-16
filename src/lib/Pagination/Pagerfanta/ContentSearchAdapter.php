<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Pagerfanta adapter for Ibexa content search.
 * Will return results as Content objects.
 *
 * @implements \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\Content\Content>
 * @implements \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\Content>
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
class ContentSearchAdapter implements AdapterInterface, SearchResultAdapter
{
    private ContentSearchHitAdapter $contentSearchHitAdapter;

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    public function __construct(Query $query, SearchService $searchService, array $languageFilter = [])
    {
        $this->contentSearchHitAdapter = new ContentSearchHitAdapter($query, $searchService, $languageFilter);
    }

    /**
     * Returns a slice of the results as Content objects.
     */
    public function getSlice(int $offset, int $length): iterable
    {
        $list = [];
        foreach ($this->contentSearchHitAdapter->getSlice($offset, $length) as $hit) {
            $list[] = $hit->valueObject;
        }

        return $list;
    }

    public function getNbResults(): int
    {
        return $this->contentSearchHitAdapter->getNbResults();
    }

    public function getAggregations(): AggregationResultCollection
    {
        return $this->contentSearchHitAdapter->getAggregations();
    }

    public function getTime(): ?float
    {
        return $this->contentSearchHitAdapter->getTime();
    }

    public function getTimedOut(): ?bool
    {
        return $this->contentSearchHitAdapter->getTimedOut();
    }

    public function getMaxScore(): ?float
    {
        return $this->contentSearchHitAdapter->getMaxScore();
    }
}
