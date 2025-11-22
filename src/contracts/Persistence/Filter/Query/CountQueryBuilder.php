<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Filter\Query;
use Doctrine\DBAL\Query\QueryBuilder;


interface CountQueryBuilder
{
    public function wrap(QueryBuilder $queryBuilder, string $countableField, ?int $limit = null): QueryBuilder;
}