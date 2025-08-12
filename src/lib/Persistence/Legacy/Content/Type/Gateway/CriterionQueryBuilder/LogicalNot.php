<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionQueryBuilder;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalNot as LogicalNotCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @implements \Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionQueryBuilder\CriterionQueryBuilderInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalNot>
 */
final class LogicalNot implements CriterionQueryBuilderInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof LogicalNotCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalNot $criterion
     */
    public function buildQueryConstraint(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        if (empty($criterion->getCriteria())) {
            return '';
        }

        return sprintf(
            'NOT (%s)',
            $criterionVisitor->visitCriteria($qb, $criterion->getCriteria()[0]),
        );
    }
}
