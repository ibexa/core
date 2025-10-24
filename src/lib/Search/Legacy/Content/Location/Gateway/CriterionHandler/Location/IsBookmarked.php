<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway\CriterionHandler\Location;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use LogicException;

final class IsBookmarked extends CriterionHandler
{
    private PermissionResolver $permissionResolver;

    public function __construct(
        Connection $connection,
        PermissionResolver $permissionResolver
    ) {
        parent::__construct($connection);

        $this->permissionResolver = $permissionResolver;
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\Location\IsBookmarked
            && $criterion->operator === Criterion\Operator::EQ;
    }

    /**
     * @param array{languages: string[]} $languageSettings
     * @param Criterion\Location\IsBookmarked $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        if (!is_array($criterion->value)) {
            throw new LogicException(sprintf(
                'Expected %s Criterion value to be an array, %s received',
                IsBookmarked::class,
                get_debug_type($criterion->value),
            ));
        }

        $userId = $this->permissionResolver
            ->getCurrentUserReference()
            ->getUserId();

        $subQueryBuilder = $this->connection->createQueryBuilder();
        $subQueryBuilder
            ->select('1')
            ->from(DoctrineDatabase::TABLE_BOOKMARKS, 'b')
            ->andWhere(
                $queryBuilder
                    ->expr()
                    ->eq(
                        'b.' . DoctrineDatabase::COLUMN_USER_ID,
                        $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                    ),
                $queryBuilder
                    ->expr()
                    ->eq('b.node_id', 't.node_id')
            );

        $query = 'EXISTS (%s)';
        if (!$criterion->value[0]) {
            $query = 'NOT ' . $query;
        }

        return sprintf(
            $query,
            $subQueryBuilder->getSQL()
        );
    }
}
