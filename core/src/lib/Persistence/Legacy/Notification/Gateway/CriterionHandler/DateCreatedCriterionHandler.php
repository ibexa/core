<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Notification\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Notification\CriterionHandlerInterface;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\DateCreated;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase;

/**
 * @implements \Ibexa\Contracts\Core\Repository\Values\Notification\CriterionHandlerInterface<DateCreated>
 */
final class DateCreatedCriterionHandler implements CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof DateCreated;
    }

    public function apply(QueryBuilder $qb, CriterionInterface $criterion): void
    {
        if ($criterion->getFrom() !== null) {
            $qb->andWhere(
                $qb->expr()->gte(
                    DoctrineDatabase::COLUMN_CREATED,
                    $qb->createNamedParameter($criterion->getFrom()->getTimestamp())
                )
            );
        }

        if ($criterion->getTo() !== null) {
            $qb->andWhere(
                $qb->expr()->lte(
                    DoctrineDatabase::COLUMN_CREATED,
                    $qb->createNamedParameter($criterion->getTo()->getTimestamp())
                )
            );
        }
    }
}
