<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location;

use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;

/**
 * Base class for location search gateways.
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
abstract class Gateway
{
    /**
     * Returns total count and data for all Locations satisfying the parameters.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sortClauses
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @return array{count: int|null, rows: list<array<string,mixed>>|array{}}
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if Criterion is not applicable to its target
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException if a given Criterion Handler or Sort Clause is not implemented
     */
    abstract public function find(
        CriterionInterface $criterion,
        int $offset,
        int $limit,
        array $sortClauses = null,
        array $languageFilter = [],
        bool $doCount = true
    ): array;
}
