<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Values\Notification;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Notification\Query\CriterionInterface;

interface CriterionHandlerInterface
{
    public function supports(CriterionInterface $criterion): bool;

    public function apply(QueryBuilder $qb, CriterionInterface $criterion): void;
}
