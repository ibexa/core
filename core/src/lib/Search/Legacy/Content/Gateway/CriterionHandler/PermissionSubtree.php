<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Repository\Values\Content\Query\Criterion\PermissionSubtree as PermissionSubtreeCriterion;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * PermissionSubtree criterion handler.
 */
class PermissionSubtree extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof PermissionSubtreeCriterion;
    }

    /**
     * @param \Ibexa\Core\Repository\Values\Content\Query\Criterion\PermissionSubtree $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $table = 'permission_subtree';

        $statements = [];
        foreach ($criterion->value as $pattern) {
            $statements[] = $queryBuilder->expr()->like(
                "{$table}.path_string",
                $queryBuilder->createNamedParameter($pattern . '%')
            );
        }

        $locationTableAlias = $this->connection->quoteIdentifier($table);
        if (!$this->hasJoinedTableAs($queryBuilder, $locationTableAlias)) {
            $queryBuilder
                ->leftJoin(
                    'c',
                    LocationGateway::CONTENT_TREE_TABLE,
                    $locationTableAlias,
                    $queryBuilder->expr()->eq(
                        "{$locationTableAlias}.contentobject_id",
                        'c.id'
                    )
                );
        }

        return $queryBuilder->expr()->or(...$statements);
    }
}
