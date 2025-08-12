<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionQueryBuilder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds as ContentTypeGroupIdsCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

/**
 * @implements \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionHandlerInterface<\Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds>
 */
final class ContentTypeGroupIds implements CriterionQueryBuilderInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof ContentTypeGroupIdsCriterion;
    }

    public function buildQueryConstraint(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ) {
        return $qb->expr()->in(
            'ezcontentclass_classgroup_group_id',
            $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_INT_ARRAY)
        );
    }
}
