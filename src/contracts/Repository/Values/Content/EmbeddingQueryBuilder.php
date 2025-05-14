<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Embedding;

class EmbeddingQueryBuilder
{
    private EmbeddingQuery $query;

    private function __construct()
    {
        $this->query = new EmbeddingQuery();
    }

    public static function create(): self
    {
        return new self();
    }

    public function withEmbedding(Embedding $embed): self
    {
        $this->query->setEmbedding($embed);

        return $this;
    }

    public function setLimit(int $limit): self
    {
        $this->query->setLimit($limit);

        return $this;
    }

    public function setOffset(int $offset): self
    {
        $this->query->setOffset($offset);

        return $this;
    }

    public function setFilter(Criterion $filter): self
    {
        $this->query->setFilter($filter);

        return $this;
    }

    /**
     * @param array<\Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation> $aggregations
     */
    public function setAggregations(array $aggregations): self
    {
        $this->query->setAggregations($aggregations);

        return $this;
    }

    public function setPerformCount(bool $performCount): self
    {
        $this->query->setPerformCount($performCount);

        return $this;
    }

    public function build(): EmbeddingQuery
    {
        return $this->query;
    }
}
