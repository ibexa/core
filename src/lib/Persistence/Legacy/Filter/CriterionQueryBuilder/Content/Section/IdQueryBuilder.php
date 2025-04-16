<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Content\Section;

use Doctrine\DBAL\ArrayParameterType;
use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\SectionId;
use Ibexa\Contracts\Core\Repository\Values\Filter\CriterionQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

/**
 * Section ID Filtering Criterion Query Builder.
 *
 * @internal for internal use by Repository Filtering
 */
final class IdQueryBuilder implements CriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof SectionId;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\SectionId $criterion
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): string {
        if (!is_array($criterion->value)) {
            throw new InvalidArgumentException(
                '$criterion->value',
                'SectionId criterion value must be a list of section IDs'
            );
        }

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentId $criterion */
        return $queryBuilder->expr()->in(
            'content.section_id',
            $queryBuilder->createNamedParameter(
                array_map('intval', $criterion->value),
                ArrayParameterType::INTEGER
            )
        );
    }
}
