<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location\IsBookmarked;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;

/**
 * @internal for internal use by Repository Filtering
 */
final class IsBookmarkedQueryBuilder extends BaseLocationCriterionQueryBuilder
{
    private PermissionResolver $permissionResolver;

    public function __construct(
        PermissionResolver $permissionResolver
    ) {
        $this->permissionResolver = $permissionResolver;
    }

    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof IsBookmarked;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): string {
        parent::buildQueryConstraint($queryBuilder, $criterion);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Location\IsBookmarked $criterion */
        $isBookmarked = $criterion->value[0] ?? null;
        if (!is_bool($isBookmarked)) {
            throw new InvalidArgumentException(
                '$criterion',
                'IsBookmarked criterion value must be boolean at index 0.'
            );
        }

        $userId = $this->permissionResolver->getCurrentUserReference()->getUserId();

        $subQueryBuilder = new QueryBuilder($queryBuilder->getConnection());
        $subQueryBuilder
            ->select('1')
            ->from(DoctrineDatabase::TABLE_BOOKMARKS, 'bookmark')
            ->where(
                $subQueryBuilder->expr()->eq(
                    'bookmark.' . DoctrineDatabase::COLUMN_USER_ID,
                    $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                ),
                $subQueryBuilder->expr()->eq('bookmark.node_id', 'location.node_id')
            );

        return sprintf(
            $isBookmarked ? 'EXISTS (%s)' : 'NOT EXISTS (%s)',
            $subQueryBuilder->getSQL()
        );
    }
}
