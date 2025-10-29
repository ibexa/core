<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query;

final class ContentTypeQuery
{
    public const DEFAULT_LIMIT = 25;

    private ?CriterionInterface $criterion;

    /** @var SortClause[] */
    private array $sortClauses;

    private int $offset;

    private ?int $limit;

    /**
     * @param SortClause[] $sortClauses
     */
    public function __construct(
        ?CriterionInterface $criterion = null,
        array $sortClauses = [],
        int $offset = 0,
        ?int $limit = self::DEFAULT_LIMIT
    ) {
        $this->criterion = $criterion;
        $this->sortClauses = $sortClauses;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function getCriterion(): ?CriterionInterface
    {
        return $this->criterion;
    }

    public function setCriterion(?CriterionInterface $criterion): void
    {
        $this->criterion = $criterion;
    }

    public function addSortClause(SortClause $sortClause): void
    {
        $this->sortClauses[] = $sortClause;
    }

    /**
     * @return SortClause[]
     */
    public function getSortClauses(): array
    {
        return $this->sortClauses;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }
}
