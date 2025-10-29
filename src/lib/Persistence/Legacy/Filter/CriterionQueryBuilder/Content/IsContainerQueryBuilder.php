<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsContainer;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Content\Type\Gateway;

/**
 * @internal for internal use by Repository Filtering
 */
final class IsContainerQueryBuilder implements CriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof IsContainer;
    }

    /**
     * @param IsContainer $criterion
     */
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        $queryBuilder
            ->joinOnce(
                'content',
                Gateway::CONTENT_TYPE_TABLE,
                'content_type',
                'content.content_type_id = content_type.id',
            );

        /** @var array{bool} $criterionValue */
        $criterionValue = $criterion->value;
        $isContainer = reset($criterionValue);

        return $queryBuilder->expr()->in(
            'content_type.is_container',
            $queryBuilder->createNamedParameter((int)$isContainer, ParameterType::INTEGER)
        );
    }
}
