<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder;

use Ibexa\Contracts\Core\Persistence\Filter\CriterionVisitor;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalOr;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * @internal for internal use by Repository Filtering
 */
final class LogicalOrQueryBuilder implements CriterionQueryBuilder
{
    /** @var CriterionVisitor */
    private $criterionVisitor;

    public function __construct(CriterionVisitor $criterionVisitor)
    {
        $this->criterionVisitor = $criterionVisitor;
    }

    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof LogicalOr;
    }

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        $constraints = [];
        /** @var LogicalOr $criterion */
        foreach ($criterion->criteria as $_criterion) {
            /** @var FilteringCriterion $_criterion */
            $constraint = $this->criterionVisitor->visitCriteria($queryBuilder, $_criterion);
            if (null !== $constraint) {
                $constraints[] = $constraint;
            }
        }

        if (empty($constraints)) {
            return null;
        }

        return (string)$queryBuilder->expr()->or(...$constraints);
    }
}
