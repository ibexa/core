<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\User\Gateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

class IsUserBased extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\IsUserBased;
    }

    /**
     * @param Criterion\IsUserBased $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $isUserBased = (bool)reset($criterion->value);

        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'contentobject_id'
            )->from(
                Gateway::USER_TABLE
            );

        $queryExpression = $queryBuilder->expr()->in(
            'c.id',
            $subSelect->getSQL()
        );

        return $isUserBased
            ? $queryExpression
            : "NOT({$queryExpression})";
    }
}
