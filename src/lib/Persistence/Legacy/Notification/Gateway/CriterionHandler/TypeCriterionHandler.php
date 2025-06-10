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
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Type;
use Ibexa\Core\Persistence\Legacy\Notification\Gateway\DoctrineDatabase;

final class TypeCriterionHandler implements CriterionHandlerInterface
{
    public function supports(Criterion $criterion): bool
    {
        return $criterion instanceof Type;
    }

    public function apply(QueryBuilder $qb, Criterion $criterion): void
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Notification\Query\Criterion\Type $criterion */
        $qb->andWhere($qb->expr()->eq(DoctrineDatabase::COLUMN_TYPE, ':type'));
        $qb->setParameter(':type', $criterion->value);
    }
}
