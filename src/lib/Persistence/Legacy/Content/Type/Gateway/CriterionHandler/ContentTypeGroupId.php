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
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\ContentTypeGroupId as ContentTypeGroupIdCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionVisitor\CriterionVisitor;
use Ibexa\Core\Repository\Values\ContentType\Query\Base;

final class ContentTypeGroupId extends Base
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
        $this->joinContentTypeGroupAssignmentTable($qb);

        $value = $criterion->getValue();
        if (is_array($value)) {
            return $qb->expr()->in(
                'g.group_id',
                $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_INT_ARRAY)
            );
        }

        return $qb->expr()->eq(
            'g.group_id',
            $qb->createNamedParameter($criterion->getValue(), ParameterType::INTEGER)
        );
    }
}
