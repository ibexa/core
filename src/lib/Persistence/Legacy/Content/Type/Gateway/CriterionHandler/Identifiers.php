<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\Type\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Base;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Identifiers as IdentifiersCriterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface;

final class Identifiers extends Base
{
    public function supports(CriterionInterface $criterion): bool
    {
        return $criterion instanceof IdentifiersCriterion;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\Criterion\Identifiers $criterion
     */
    public function apply(QueryBuilder $qb, CriterionInterface $criterion): void
    {
        $qb->andWhere(
            $qb->expr()->in(
                'c.identifier',
                $qb->createNamedParameter($criterion->getValue(), Connection::PARAM_STR_ARRAY)
            ),
        );
    }
}
