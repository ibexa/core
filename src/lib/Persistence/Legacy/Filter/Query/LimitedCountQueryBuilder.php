<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;

/**
 * Limited Count trait. Used to allow for proper limiting of count queries
 * when using Doctrine DBAL QueryBuilder.
 */
final class LimitedCountQueryBuilder
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    /**
     * Takes a QueryBuilder and wraps it in a count query with a limit if a limit is provided.
     * This performs the following transformation to the passed query.
     * SELECT DISTINCT COUNT(DISTINCT someField) FROM XXX WHERE YYY;
     * To
     * SELECT COUNT(*) FROM (SELECT DISTINCT someField FROM XXX WHERE YYY LIMIT N) AS csub;.
     *
     * @phpstan-param positive-int $limit
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     * @throws \Doctrine\DBAL\Exception
     */
    public function wrap(
        QueryBuilder $queryBuilder,
        string $countableField,
        ?int $limit = null
    ): QueryBuilder {
        if ($limit === null) {
            return $queryBuilder;
        }

        if ($limit <= 0) {
            throw new InvalidArgumentException('$limit', 'Limit must be greater than 0');
        }

        $querySql = $queryBuilder
            ->select($countableField)
            ->setMaxResults($limit)
            ->getSQL();

        $countQuery = $this->connection->createQueryBuilder();

        return $countQuery
            ->select(
                'COUNT(*)'
            )
            ->from('(' . $querySql . ')', 'csub')
            ->setParameters($queryBuilder->getParameters(), $queryBuilder->getParameterTypes());
    }
}
