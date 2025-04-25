<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Iterator\BatchIteratorAdapter;

use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapter;
use Ibexa\Contracts\Core\Repository\Iterator\BatchIteratorAdapter\LocationSearchAdapter;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;

/**
 * @extends \Ibexa\Tests\Core\Repository\Iterator\BatchIteratorAdapter\AbstractSearchAdapterTestCase<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
 */
final class LocationSearchAdapterTest extends AbstractSearchAdapterTestCase
{
    protected function createAdapterUnderTest(
        SearchService $searchService,
        Query $query,
        array $languageFilter,
        bool $filterOnPermissions
    ): AbstractSearchAdapter {
        self::assertInstanceOf(LocationQuery::class, $query);

        return new LocationSearchAdapter(
            $searchService,
            $query,
            self::EXAMPLE_LANGUAGE_FILTER,
            true
        );
    }

    protected function getExpectedFindMethod(): string
    {
        return 'findLocations';
    }

    protected function newQuery(): Query
    {
        return new LocationQuery();
    }
}
