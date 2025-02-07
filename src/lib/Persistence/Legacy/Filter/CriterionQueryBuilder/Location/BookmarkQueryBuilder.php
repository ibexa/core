<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsBookmarked;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;

/**
 * @internal for internal use by Repository Filtering
 */
final class BookmarkQueryBuilder extends BaseLocationCriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof IsBookmarked;
    }

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): ?string {
        $queryBuilder
            ->joinOnce(
                'location',
                DoctrineDatabase::TABLE_BOOKMARKS,
                'bookmark',
                'location.node_id = bookmark.node_id'
            );

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsBookmarked $criterion */
        return $queryBuilder->expr()->eq(
            'bookmark.user_id',
            $queryBuilder->createNamedParameter(
                (int)$criterion->value[0],
                ParameterType::INTEGER
            )
        );
    }
}
