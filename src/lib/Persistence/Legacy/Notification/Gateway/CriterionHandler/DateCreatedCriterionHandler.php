<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Notification\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\DateCreated;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase;

final class DateCreatedCriterionHandler implements CriterionHandlerInterface
{
    public function supports(Criterion $criterion): bool
    {
        return $criterion instanceof DateCreated;
    }

    public function apply(QueryBuilder $qb, Criterion $criterion): void
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\DateCreated $criterion */
        if ($criterion->from !== null) {
            $qb->andWhere($qb->expr()->gte(DoctrineDatabase::COLUMN_CREATED, ':created_from'));
            $qb->setParameter(':created_from', $criterion->from->getTimestamp());
        }

        if ($criterion->to !== null) {
            $qb->andWhere($qb->expr()->lte(DoctrineDatabase::COLUMN_CREATED, ':created_to'));
            $qb->setParameter(':created_to', $criterion->to->getTimestamp());
        }
    }
}
