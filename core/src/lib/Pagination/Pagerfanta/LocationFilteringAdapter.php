<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * @implements \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 *
 * @phpstan-import-type TFilteringLanguageFilter from \Ibexa\Contracts\Core\Repository\LocationService
 */
final class LocationFilteringAdapter implements AdapterInterface
{
    private LocationService $locationService;

    /** @var \Ibexa\Contracts\Core\Repository\Values\Filter\Filter */
    private Filter $filter;

    /** @phpstan-var TFilteringLanguageFilter|null */
    private ?array $languageFilter;

    /** @phpstan-var int<0, max>|null */
    private ?int $totalCount = null;

    public function __construct(
        LocationService $locationService,
        Filter $filter,
        ?array $languageFilter = null
    ) {
        $this->locationService = $locationService;
        $this->filter = $filter;
        $this->languageFilter = $languageFilter;
    }

    public function getNbResults(): int
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->locationService->count($this->filter, $this->languageFilter);
        }

        return $this->totalCount;
    }

    public function getSlice($offset, $length): iterable
    {
        $selectFilter = clone $this->filter;
        $selectFilter->sliceBy($length, $offset);

        $results = $this->locationService->find($selectFilter, $this->languageFilter);
        if ($this->totalCount === null) {
            $this->totalCount = $results->getTotalCount();
        }

        return $results;
    }
}
