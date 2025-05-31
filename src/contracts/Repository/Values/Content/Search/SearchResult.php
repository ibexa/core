<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Search;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Iterator;
use IteratorAggregate;

/**
 * This class represents a search result.
 *
 * @template TSearchHitValueObject of \Ibexa\Contracts\Core\Repository\Values\ValueObject
 *
 * @phpstan-implements \IteratorAggregate<array-key, \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit>
 */
class SearchResult extends ValueObject implements IteratorAggregate, AggregationResultAwareInterface
{
    public AggregationResultCollection $aggregations;

    /**
     * The value objects found for the query.
     *
     * @phpstan-var list<\Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit<TSearchHitValueObject>>
     */
    public array $searchHits = [];

    public ?SpellcheckResult $spellcheck = null;

    /**
     * The duration of the search processing in ms.
     */
    public int $time = 0;

    /**
     * Indicates if the search has timed out.
     */
    public bool $timedOut;

    /**
     * The maximum score of this query.
     */
    public ?float $maxScore = null;

    /**
     * The total number of searchHits.
     *
     * `null` if Query->performCount was set to false and search engine avoids search lookup.
     *
     * @phpstan-var int<0, max>|null
     */
    public ?int $totalCount;

    public function __construct(array $properties = [])
    {
        if (!isset($properties['aggregations'])) {
            $properties['aggregations'] = new AggregationResultCollection();
        }

        parent::__construct($properties);
    }

    public function getSpellcheck(): ?SpellcheckResult
    {
        return $this->spellcheck;
    }

    public function getAggregations(): ?AggregationResultCollection
    {
        return $this->aggregations;
    }

    public function getIterator(): Iterator
    {
        return new ArrayIterator($this->searchHits);
    }
}
