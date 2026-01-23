<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupName as ContentTypeGroupNameCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @implements \Ibexa\Contracts\Core\Persistence\Content\Type\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupName>
 */
final class ContentTypeGroupName implements CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof ContentTypeGroupNameCriterion;
    }

    /**
     * @param ContentTypeGroupNameCriterion $criterion
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        $subQuery = $qb->getConnection()->createQueryBuilder();
        $value = $criterion->getValue();
        if (!is_array($value)) {
            $value = [$value];
        }

        $whereClause = $subQuery->expr()->in(
            'LOWER(ctg.name)',
            $qb->createNamedParameter(array_map('strtolower', $value), Connection::PARAM_STR_ARRAY)
        );

        $subQuery
            ->select('g.content_type_id')
            ->from(Gateway::CONTENT_TYPE_GROUP_TABLE, 'ctg')
            ->leftJoin('ctg', Gateway::CONTENT_TYPE_TO_GROUP_ASSIGNMENT_TABLE, 'c_group', 'ctg.id = c_group.group_id')
            ->andWhere($whereClause)
            ->andWhere('c_group.content_type_id = c.id');

        return sprintf('EXISTS (%s)', $subQuery->getSQL());
    }
}
