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
 * Location id criterion handler.
 */
class LocationId extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\LocationId;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationId $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $locationIds = (array)$criterion->value;
        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                Gateway::CONTENT_TREE_TABLE
            )->where(
                $queryBuilder->expr()->in(
                    'node_id',
                    $queryBuilder->createNamedParameter($locationIds, Connection::PARAM_INT_ARRAY)
                )
            );

        return $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
