<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Base;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd as LogicalAndCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

final class LogicalAnd extends Base
{
    private CriterionVisitor $criterionVisitor;

    public function __construct(
        CriterionVisitor $criterionVisitor
    ) {
        $this->criterionVisitor = $criterionVisitor;
    }

    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof LogicalAndCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd $criterion
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function apply(QueryBuilder $qb, CriterionInterface $criterion): void
    {
        $constraints = [];
        /** @var \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\LogicalAnd $criterion */
        foreach ($criterion->getCriteria() as $criterion) {
            $constraint = $this->criterionVisitor->visitCriteria($qb, $criterion);
            if (null !== $constraint) {
                $constraints[] = $constraint;
            }
        }

        if (empty($constraints)) {
            return;
        }

        $qb->andWhere($qb->expr()->and(...$constraints));
    }
}
