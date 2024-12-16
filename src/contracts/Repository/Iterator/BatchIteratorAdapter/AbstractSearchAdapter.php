<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Iterator;

/**
 * @template TSearchHitValueObject of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
abstract class AbstractSearchAdapter implements BatchIteratorAdapter
{
    protected SearchService $searchService;

    protected Query $query;

    /** @phpstan-var TSearchLanguageFilter */
    protected array $languageFilter;

    /** @var bool */
    protected bool $filterOnUserPermissions;

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     */
    public function __construct(
        SearchService $searchService,
        Query $query,
        array $languageFilter = [],
        bool $filterOnUserPermissions = true
    ) {
        $this->searchService = $searchService;
        $this->query = $query;
        $this->languageFilter = $languageFilter;
        $this->filterOnUserPermissions = $filterOnUserPermissions;
    }

    final public function fetch(int $offset, int $limit): Iterator
    {
        $query = clone $this->query;
        $query->offset = $offset;
        $query->limit = $limit;

        return $this->executeSearch($query)->getIterator();
    }

    /**
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult<TSearchHitValueObject>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    abstract protected function executeSearch(Query $query): SearchResult;
}
