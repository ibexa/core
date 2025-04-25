<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapter;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\ContentInfoSearchAdapter;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;

/**
 * @extends \Ibexa\Tests\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapterTestCase<\Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo>
 */
final class ContentInfoSearchAdapterTest extends AbstractSearchAdapterTestCase
{
    protected function createAdapterUnderTest(
        SearchService $searchService,
        Query $query,
        array $languageFilter,
        bool $filterOnPermissions
    ): AbstractSearchAdapter {
        return new ContentInfoSearchAdapter(
            $searchService,
            $query,
            self::EXAMPLE_LANGUAGE_FILTER,
            true
        );
    }

    protected function getExpectedFindMethod(): string
    {
        return 'findContentInfo';
    }
}
