<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

/**
 * The Content Search Gateway provides the implementation for one database to
 * retrieve the desired content objects.
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
abstract class Gateway
{
    /**
     * Returns a list of object satisfying the $criterion.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sort
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @phpstan-return array{count: int|null, rows: list<array<string, mixed>> }
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if Criterion is not applicable to its target
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if a given Criterion Handler or Sort Clause is not implemented
     */
    abstract public function find(
        CriterionInterface $criterion,
        int $offset,
        int $limit,
        array $sort = null,
        array $languageFilter = [],
        bool $doCount = true
    ): array;
}
