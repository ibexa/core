<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionQueryBuilder;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd as LogicalAndCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @implements \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd>
 */
final class LogicalAnd implements CriterionQueryBuilderInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof LogicalAndCriterion;
    }

    /**
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     */
    public function buildQueryConstraint(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ) {
        $subexpressions = [];
        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd $criterion */
        foreach ($criterion->getCriteria() as $subCriterion) {
            $subexpressions[] = $criterionVisitor->visitCriteria($qb, $subCriterion);
        }

        return $qb->expr()->and(...$subexpressions);
    }
}
