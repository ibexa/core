<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content;

use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Sibling;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\LogicalAndQueryBuilder;

/**
 * @internal for internal use by Repository Filtering
 */
final class SiblingQueryBuilder implements CriterionQueryBuilder
{
    /** @var LogicalAndQueryBuilder */
    private $logicalAndQueryBuilder;

    /**
     * Sibling is internally a composite LogicalAnd criterion, so is handled by delegation.
     */
    public function __construct(LogicalAndQueryBuilder $logicalAndQueryBuilder)
    {
        $this->logicalAndQueryBuilder = $logicalAndQueryBuilder;
    }

    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof Sibling;
    }

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        /** @var Sibling $criterion */
        /** @var LogicalAnd $_criterion */
        $_criterion = $criterion->criteria;

        return $this->logicalAndQueryBuilder->buildQueryConstraint($queryBuilder, $_criterion);
    }
}
