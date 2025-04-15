<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway\CriterionHandler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        $subSelect = $this->connection->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                'ezcontentobject_tree'
            )->where(
                $expr->in(
                    'parent_node_id',
                    $queryBuilder->createNamedParameter(
                        $criterion->value,
                        ArrayParameterType::INTEGER
                    )
                )
            );

        return $expr->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
