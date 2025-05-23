<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\ObjectState\Gateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

class ObjectStateIdentifier extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\ObjectStateIdentifier;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ObjectStateIdentifier $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $value = (array)$criterion->value;
        $matchStateIdentifier = $queryBuilder->expr()->in(
            't2.identifier',
            $queryBuilder->createNamedParameter($value, Connection::PARAM_STR_ARRAY)
        );

        if (null !== $criterion->target) {
            $criterionTarget = (array)$criterion->target;
            $constraints = $queryBuilder->expr()->and(
                $queryBuilder->expr()->in(
                    't3.identifier',
                    $queryBuilder->createNamedParameter(
                        $criterionTarget,
                        Connection::PARAM_STR_ARRAY
                    )
                ),
                $matchStateIdentifier
            );
        } else {
            $constraints = $matchStateIdentifier;
        }

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select('t1.contentobject_id')
            ->from(Gateway::OBJECT_STATE_LINK_TABLE, 't1')
            ->leftJoin(
                't1',
                Gateway::OBJECT_STATE_TABLE,
                't2',
                't1.contentobject_state_id = t2.id',
            )
            ->leftJoin(
                't2',
                Gateway::OBJECT_STATE_GROUP_TABLE,
                't3',
                't2.group_id = t3.id'
            )
            ->where($constraints);

        return $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );
    }
}
