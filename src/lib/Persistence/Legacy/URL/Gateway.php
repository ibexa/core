<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\URL;

use Ibexa\Contracts\Core\Persistence\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;

abstract class Gateway
{
    /**
     * Update the URL.
     */
    abstract public function updateUrl(URL $url): void;

    /**
     * Selects URLs matching specified criteria.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\URL\Query\SortClause[] $sortClauses
     *
     * @return array{count: int|null, rows: list<array<string, mixed>>}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if Criterion is not applicable to its target
     */
    abstract public function find(
        Criterion $criterion,
        int $offset,
        int $limit,
        array $sortClauses = [],
        bool $doCount = true
    ): array;

    /**
     * Returns IDs of Content Objects using URL identified by $id.
     *
     * @param int $id
     *
     * @return int[]
     */
    abstract public function findUsages(int $id): array;

    /**
     * Loads URL with url id.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadUrlData(int $id): array;

    /**
     * Loads URL with url address.
     *
     * @phpstan-return list<array<string,mixed>>
     */
    abstract public function loadUrlDataByUrl(string $url): array;
}
