<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Parent location id criterion handler.
 */
class ParentLocationId extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\ParentLocationId;
    }

    /**
     * @param Criterion\ParentLocationId $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $subSelect = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                Gateway::CONTENT_TREE_TABLE
            )->where(
                $expr->in(
                    'parent_node_id',
                    $queryBuilder->createNamedParameter(
                        $criterion->value,
                        Connection::PARAM_INT_ARRAY
                    )
                )
            );

        return $expr->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
