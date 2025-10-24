<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Pagination\Pagerfanta;

use Ibexa\Contracts\Core\Repository\Values\Content\Search\AggregationResultCollection;
use Pagerfanta\Pagerfanta as BasePagerfanta;

/**
 * @template TSearchResultAdapter
 *
 * @extends \Pagerfanta\Pagerfanta<TSearchResultAdapter>
 */
final class Pagerfanta extends BasePagerfanta
{
    /**
     * @phpstan-param SearchResultAdapter<TSearchResultAdapter> $searchResultAdapter
     */
    public function __construct(private readonly SearchResultAdapter $searchResultAdapter)
    {
        parent::__construct($this->searchResultAdapter);
    }

    public function getAggregations(): AggregationResultCollection
    {
        return $this->searchResultAdapter->getAggregations();
    }

    public function getTime(): ?float
    {
        return $this->searchResultAdapter->getTime();
    }

    public function getTimedOut(): ?bool
    {
        return $this->searchResultAdapter->getTimedOut();
    }

    public function getMaxScore(): ?float
    {
        return $this->searchResultAdapter->getMaxScore();
    }
}
