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
use Ibexa\Core\Repository\Permission\PermissionResolver;

/**
 * @internal for internal use by Repository Filtering
 */
final class BookmarkQueryBuilder extends BaseLocationCriterionQueryBuilder
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

    public function buildQueryConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion
    ): string {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\IsBookmarked $criterion */
        $isBookmarked = $criterion->value[0] ?? null;
        if (!is_bool($isBookmarked)) {
            throw new \InvalidArgumentException('IsBookmarked criterion value must be boolean at index 0.');
        }
        $userId = $criterion->userId ?? $this->permissionResolver->getCurrentUserReference()->getUserId();

        if ($isBookmarked) {
            $queryBuilder
                ->joinOnce(
                    'location',
                    DoctrineDatabase::TABLE_BOOKMARKS,
                    'bookmark',
                    'location.node_id = bookmark.node_id'
                );

            return $queryBuilder->expr()->eq(
                'bookmark.user_id',
                $queryBuilder->createNamedParameter(
                    $userId,
                    ParameterType::INTEGER
                )
            );
        } else {
            $queryBuilder
                ->leftJoinOnce(
                    'location',
                    DoctrineDatabase::TABLE_BOOKMARKS,
                    'bookmark',
                    'location.node_id = bookmark.node_id AND bookmark.user_id = :userId'
                )
            ->setParameter('userId', $userId);

            return $queryBuilder->expr()->isNull('bookmark.id');
        }
    }
}
