<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Visibility criterion handler.
 */
class Visibility extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\Visibility;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Visibility $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $subSelect = $this->connection->createQueryBuilder();

        if ($criterion->value[0] === Criterion\Visibility::VISIBLE) {
            $expression = $queryBuilder->expr()->and(
                $queryBuilder->expr()->eq(
                    'subquery_location.is_hidden',
                    0
                ),
                $queryBuilder->expr()->eq(
                    'subquery_location.is_invisible',
                    0
                )
            );
        } else {
            $expression = $queryBuilder->expr()->or(
                $queryBuilder->expr()->eq(
                    'subquery_location.is_hidden',
                    1
                ),
                $queryBuilder->expr()->eq(
                    'subquery_location.is_invisible',
                    1
                )
            );
        }

        $subSelect
            ->select('contentobject_id')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 'subquery_location')
            ->where($expression);

        return $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
