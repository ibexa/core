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
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupId as ContentTypeGroupIdCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @implements \Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupId>
 */
final class ContentTypeGroupId implements CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof ContentTypeGroupIdCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupId $criterion
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        $subQuery = $qb->getConnection()->createQueryBuilder();
        $whereClause = is_array($criterion->getValue())
            ? $subQuery->expr()->in(
                'c_group.group_id',
                $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_INT_ARRAY)
            ) : $subQuery->expr()->eq(
                'c_group.group_id',
                $qb->createNamedParameter($criterion->getValue(), ParameterType::INTEGER)
            );

        $subQuery
            ->select('c_group.content_type_id')
            ->from(Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE, 'c_group')
            ->andWhere($whereClause)
            ->andWhere('c_group.content_type_id = c.id');

        return sprintf('EXISTS (%s)', $subQuery->getSQL());
    }
}
