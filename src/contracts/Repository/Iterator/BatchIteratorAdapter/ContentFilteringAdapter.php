<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Iterator;

final class ContentFilteringAdapter implements BatchIteratorAdapter
{
    private ContentService $contentService;

    private Filter $filter;

    /** @var string[]|null */
    private ?array $languages;

    public function __construct(ContentService $contentService, Filter $filter, ?array $languages = null)
    {
        $this->contentService = $contentService;
        $this->filter = $filter;
        $this->languages = $languages;
    }

    public function fetch(int $offset, int $limit): Iterator
    {
        $filter = clone $this->filter;
        $filter->sliceBy($limit, $offset);

        return $this->contentService->find($filter, $this->languages)->getIterator();
    }
}
