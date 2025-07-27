<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta\AdapterFactory;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter;

/**
 * @internal
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
interface SearchHitAdapterFactoryInterface
{
    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @phpstan-return \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\ValueObject>
     */
    public function createAdapter(Query $query, array $languageFilter = []): SearchResultAdapter;

    /**
     * @phpstan-return \Ibexa\Core\Pagination\Pagerfanta\SearchResultAdapter<\Ibexa\Contracts\Core\Repository\Values\ValueObject>
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function createFixedAdapter(Query $query, array $languageFilter = []): SearchResultAdapter;
}
