<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard;

use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used to perform a URLWildcard query.
 */
class URLWildcardQuery extends ValueObject
{
    /**
     * The Query filter.
     */
    public Criterion $filter;

    /**
     * Query sorting clauses.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\SortClause[]
     */
    public array $sortClauses = [];

    /**
     * Query offset.
     *
     * Sets the offset for search hits, used for paging the results.
     */
    public int $offset = 0;

    /**
     * Query limit.
     *
     * Limit for number of search hits to return.
     * If value is `0`, search query will not return any search hits, useful for doing a count.
     */
    public int $limit = 25;

    /**
     * If true, search engine should perform count even if that means extra lookup.
     */
    public bool $performCount = true;
}
