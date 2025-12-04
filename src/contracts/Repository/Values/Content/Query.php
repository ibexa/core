<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Spellcheck;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;

/**
 * This class is used to perform a Content query.
 */
class Query extends ValueObject implements QueryValidatorInterface
{
    public const string SORT_ASC = 'ascending';
    public const string SORT_DESC = 'descending';

    /**
     * The Query filter.
     *
     * For the storage backend that supports it (Solr) filters the result set
     * without influencing score. It also offers better performance as filter
     * part of the Query can be cached.
     *
     * In case when the backend does not distinguish between query and filter
     * (Legacy Storage implementation), it will simply be combined with Query query
     * using LogicalAnd criterion.
     *
     * Can contain multiple criterion, as items of a logical one (by default
     * AND)
     */
    public ?CriterionInterface $filter = null;

    /**
     * The Query query.
     *
     * For the storage backend that supports it (Solr Storage) query will influence
     * score of the search results.
     *
     * Can contain multiple criterion, as items of a logical one (by default
     * AND). Defaults to MatchAll.
     */
    public ?CriterionInterface $query = null;

    /**
     * Query sorting clauses.
     *
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[]
     */
    public array $sortClauses = [];

    /**
     * @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation[]
     */
    public array $aggregations = [];

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
     * Spellcheck suggestions are returned.
     */
    public ?Spellcheck $spellcheck = null;

    /**
     * If true, search engine should perform count even if that means extra lookup.
     */
    public bool $performCount = true;

    public function isValid(): bool
    {
        return true;
    }
}
