<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\ContentType\Query;

use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @template T of \Ibexa\Contracts\Core\Repository\Values\ContentType\Query\CriterionInterface
 */
interface CriterionHandlerInterface
{
    /**
     * @param T $criterion
     */
    public function supports(CriterionInterface $criterion): bool;

    /**
     * @param T $criterion
     */
    public function apply(QueryBuilder $qb, CriterionInterface $criterion): void;
}
