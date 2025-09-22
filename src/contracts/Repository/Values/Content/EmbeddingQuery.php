<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Embedding;
use InvalidArgumentException;

/**
 * This class is used to perform an embedding query.
 */
class EmbeddingQuery extends Query
{
    private ?Embedding $embedding = null;

    public function getEmbedding(): ?Embedding
    {
        return $this->embedding;
    }

    public function setEmbedding(?Embedding $embedding): void
    {
        $this->embedding = $embedding;
    }

    public function getFilter(): ?Criterion
    {
        return $this->filter;
    }

    public function setFilter(Criterion $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation[] $aggregations
     */
    public function setAggregations(array $aggregations): void
    {
        $this->aggregations = $aggregations;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function setPerformCount(bool $performCount): void
    {
        $this->performCount = $performCount;
    }

    public function getPerformCount(): bool
    {
        return $this->performCount;
    }

    public function isValid(): bool
    {
        $invalid = [];

        if ($this->query !== null) {
            $invalid[] = 'query';
        }
        if (!empty($this->sortClauses)) {
            $invalid[] = 'sortClauses';
        }
        if (!empty($this->facetBuilders)) {
            $invalid[] = 'facetBuilders';
        }
        if ($this->spellcheck !== null) {
            $invalid[] = 'spellcheck';
        }

        if (count($invalid) > 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'EmbeddingQuery may not set [%s].',
                    implode(', ', $invalid)
                )
            );
        }

        return true;
    }
}
