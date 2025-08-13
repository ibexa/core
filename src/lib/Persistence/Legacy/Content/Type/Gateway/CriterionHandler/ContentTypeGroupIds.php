<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Base;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds as ContentTypeGroupIdsCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;

final class ContentTypeGroupIds  extends Base
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof ContentTypeGroupIdsCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupIds $criterion
     */
    public function apply(
        CriterionVisitor $criterionVisitor,
        QueryBuilder $qb,
        CriterionInterface $criterion
    ): string {
        $this->joinContentTypeGroupAssignmentTable($qb);

        return $qb->expr()->in(
            'g.group_id',
            $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_INT_ARRAY)
        );
    }
}
