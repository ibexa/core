<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\User\Query;

/**
 * Sorting and pagination shared by {@see UserQuery} and {@see UserGroupQuery}.
 */
abstract class AbstractQuery
{
    public const DEFAULT_LIMIT = 25;

    /** @var list<\Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause> */
    private array $sortClauses;

    private int $offset;

    private ?int $limit;

    /**
     * @param list<\Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause> $sortClauses
     */
    public function __construct(
        array $sortClauses = [],
        int $offset = 0,
        ?int $limit = self::DEFAULT_LIMIT
    ) {
        $this->sortClauses = $sortClauses;
        $this->offset = $offset;
        $this->limit = $limit;
    }

    public function addSortClause(SortClause $sortClause): void
    {
        $this->sortClauses[] = $sortClause;
    }

    /**
     * @return list<\Ibexa\Contracts\Core\Repository\Values\User\Query\SortClause>
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

    /**
     * Returns the maximum number of items to return; `null` means no limit and `0` returns the total count only.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function setLimit(?int $limit): void
    {
        $this->limit = $limit;
    }
}
