<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Base;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalOr as LogicalOrCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

final class LogicalOr extends Base
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof LogicalOrCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalOr $criterion
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): CompositeExpression {
        $subexpressions = [];
        foreach ($criterion->getCriteria() as $subCriterion) {
            $subexpressions[] = $criterionVisitor->visitCriteria($qb, $subCriterion);
        }

        return $qb->expr()->orX(...$subexpressions);
    }
}
