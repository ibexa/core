<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Iterator;

final class LocationFilteringAdapter implements BatchIteratorAdapter
{
    private LocationService $locationService;

    private Filter $filter;

    /** @var string[]|null */
    private ?array $languages;

    /**
     * @param string[]|null $languages
     */
    public function __construct(LocationService $locationService, Filter $filter, ?array $languages = null)
    {
        $this->locationService = $locationService;
        $this->filter = $filter;
        $this->languages = $languages;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    public function fetch(int $offset, int $limit): Iterator
    {
        $filter = clone $this->filter;
        $filter->sliceBy($limit, $offset);

        return $this->locationService->find($filter, $this->languages)->getIterator();
    }
}
