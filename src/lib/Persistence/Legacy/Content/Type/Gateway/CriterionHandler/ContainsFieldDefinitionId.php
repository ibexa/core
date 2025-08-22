<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContainsFieldDefinitionId as ContainsFieldDefinitionIdCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

final class ContainsFieldDefinitionId implements CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof ContainsFieldDefinitionIdCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContainsFieldDefinitionId $criterion
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        $subQuery = $qb->getConnection()->createQueryBuilder();

        $whereClause = is_array($criterion->getValue())
            ? $subQuery->expr()->in(
                'f_def.id',
                $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_INT_ARRAY)
            ) : $subQuery->expr()->eq(
                'f_def.id',
                $qb->createNamedParameter($criterion->getValue(), ParameterType::INTEGER)
            );

        $subQuery
            ->select('f_def.contentclass_id')
            ->from('ezcontentclass_attribute', 'f_def')
            ->where($whereClause)
            ->andWhere('f_def.contentclass_id = c.id');

        return sprintf('EXISTS (%s)', $subQuery->getSQL());
    }
}
