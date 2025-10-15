<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Embedding;

final class EmbeddingQueryBuilder
{
    private ?Embedding $embedding = null;

    private ?int $limit = null;

    private ?int $offset = null;

    private ?Criterion $filter = null;

    /** @var array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation> */
    private array $aggregations = [];

    private bool $performCount = false;

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    public function withEmbedding(Embedding $embed): self
    {
        $this->embedding = $embed;

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->offset = $offset;

        return $this;
    }

    public function setFilter(Criterion $filter): self
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * @param array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation> $aggregations
     */
    public function setAggregations(array $aggregations): self
    {
        $this->aggregations = $aggregations;

        return $this;
    }

    public function setPerformCount(bool $performCount): self
    {
        $this->performCount = $performCount;

        return $this;
    }

    public function build(): EmbeddingQuery
    {
        $query = new EmbeddingQuery();

        if ($this->embedding !== null) {
            $query->setEmbedding($this->embedding);
        }

        if ($this->limit !== null) {
            $query->setLimit($this->limit);
        }

        if ($this->offset !== null) {
            $query->setOffset($this->offset);
        }

        if ($this->filter !== null) {
            $query->setFilter($this->filter);
        }

        if (!empty($this->aggregations)) {
            $query->setAggregations($this->aggregations);
        }

        $query->setPerformCount($this->performCount);

        return $query;
    }
}
