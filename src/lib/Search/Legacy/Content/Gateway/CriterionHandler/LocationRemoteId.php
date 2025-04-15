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
 * Location remote id criterion handler.
 */
class LocationRemoteId extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\LocationRemoteId;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationRemoteId $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        $subSelect = $this->connection->createQueryBuilder();
        $value = (array)$criterion->value;
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                'ezcontentobject_tree',
                'subquery_location'
            )->where(
                $queryBuilder->expr()->in(
                    'subquery_location.remote_id',
                    $queryBuilder->createNamedParameter($value, ArrayParameterType::STRING)
                )
            );

        return $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
