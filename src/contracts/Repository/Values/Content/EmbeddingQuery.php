<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\Aggregation;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Embedding;
use InvalidArgumentException;

final class EmbeddingQuery extends Query
{
    private ?Embedding $embedding = null;

    public function getEmbedding(): ?Embedding
    {
        return $this->embedding;
    }

    public function setEmbedding(?Embedding $embedding = null): void
    {
        $this->embedding = $embedding;
    }

    public function getFilter(): ?CriterionInterface
    {
        return $this->filter;
    }

    public function setFilter(?CriterionInterface $filter = null): void
    {
        $this->filter = $filter;
    }

    /**
     * @return Aggregation[]
     */
    public function getAggregations(): array
    {
        return $this->aggregations;
    }

    /**
     * @param Aggregation[] $aggregations
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
        if ($this->spellcheck !== null) {
            $invalid[] = 'spellcheck';
        }

        if (count($invalid) > 0) {
            throw new InvalidArgumentException(
                sprintf(
                    'EmbeddingQuery did not set [%s].',
                    implode(', ', $invalid)
                )
            );
        }

        return true;
    }
}
