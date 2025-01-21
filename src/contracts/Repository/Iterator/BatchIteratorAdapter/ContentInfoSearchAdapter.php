<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;

/**
 * @extends \Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapter<\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo>
 */
final class ContentInfoSearchAdapter extends AbstractSearchAdapter
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function executeSearch(Query $query): SearchResult
    {
        return $this->searchService->findContentInfo(
            $query,
            $this->languageFilter,
            $this->filterOnUserPermissions
        );
    }
}
