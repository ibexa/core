<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Content\Query;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target;
use InvalidArgumentException;

/**
 * This class is the base for SortClause classes, used to set sorting of content queries.
 */
abstract class SortClause
{
    /**
     * Sort direction
     * One of Query::SORT_ASC or Query::SORT_DESC;.
     */
    public string $direction = Query::SORT_ASC;

    /**
     * Sort target, high level: section_identifier, attribute_value, etc.
     */
    public string $target;

    /**
     * Extra target data, required by some sort clauses, field for instance.
     */
    public ?Target $targetData;

    /**
     * Constructs a new SortClause on $sortTarget in direction $sortDirection.
     *
     * @param string $sortDirection one of Query::SORT_ASC or Query::SORT_DESC
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause\Target|null $targetData Extra target data, used by some clauses (field for instance)
     *
     * @throws \InvalidArgumentException if the given sort order isn't one of Query::SORT_ASC or Query::SORT_DESC
     */
    public function __construct(string $sortTarget, string $sortDirection, ?Target $targetData = null)
    {
        if ($sortDirection !== Query::SORT_ASC && $sortDirection !== Query::SORT_DESC) {
            throw new InvalidArgumentException('Sort direction must be one of Query::SORT_ASC or Query::SORT_DESC');
        }

        $this->direction = $sortDirection;
        $this->target = $sortTarget;

        if ($targetData !== null) {
            $this->targetData = $targetData;
        }
    }
}
