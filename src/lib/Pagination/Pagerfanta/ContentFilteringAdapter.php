<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Pagerfanta adapter for content filtering.
 *
 * @implements \Pagerfanta\Adapter\AdapterInterface<\Ibexa\Contracts\Core\Repository\Values\Content\Content>
 *
 * @phpstan-import-type TFilteringLanguageFilter from \Ibexa\Contracts\Core\Repository\ContentService
 */
final class ContentFilteringAdapter implements AdapterInterface
{
    private ContentService $contentService;

    private Filter $filter;

    /** @var TFilteringLanguageFilter|null */
    private ?array $languageFilter;

    /** @phpstan-var int<0, max>|null */
    private ?int $totalCount = null;

    /**
     * @param array<int, string>|null $languageFilter
     */
    public function __construct(
        ContentService $contentService,
        Filter $filter,
        ?array $languageFilter = null
    ) {
        $this->contentService = $contentService;
        $this->filter = $filter;
        $this->languageFilter = $languageFilter;
    }

    public function getNbResults(): int
    {
        if ($this->totalCount === null) {
            $this->totalCount = $this->contentService->count($this->filter, $this->languageFilter);
        }

        return $this->totalCount;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentList
     */
    public function getSlice($offset, $length): iterable
    {
        $selectFilter = clone $this->filter;
        $selectFilter->sliceBy($length, $offset);

        $results = $this->contentService->find($selectFilter, $this->languageFilter);
        if ($this->totalCount === null) {
            $this->totalCount = $results->getTotalCount();
        }

        return $results;
    }
}
