<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\IsSystem as IsSystemCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

final class IsSystem implements CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof IsSystemCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\IsSystem $criterion
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        $subQuery = $qb->getConnection()->createQueryBuilder();
        $subQuery
            ->select('g.contentclass_id')
            ->from('ezcontentclassgroup', 'ctg')
            ->leftJoin('ctg', 'ezcontentclass_classgroup', 'c_group', 'ctg.id = c_group.group_id')
            ->andWhere($subQuery->expr()->eq(
                'ctg.is_system',
                $qb->createNamedParameter($criterion->getValue(), ParameterType::BOOLEAN)
            ))
            ->andWhere('c_group.contentclass_id = c.id');

        return sprintf('EXISTS (%s)', $subQuery->getSQL());
    }
}
