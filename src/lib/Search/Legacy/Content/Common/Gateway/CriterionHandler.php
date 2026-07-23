<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Doctrine\TracksJoinedTablesTrait;

abstract class CriterionHandler
{
    use TracksJoinedTablesTrait;

    /**
     * Map of criterion operators to the respective function names in the zeta
     * Database abstraction layer.
     *
     * @var array
     */
    protected $comparatorMap = [
        Operator::EQ => 'eq',
        Operator::GT => 'gt',
        Operator::GTE => 'gte',
        Operator::LT => 'lt',
        Operator::LTE => 'lte',
        Operator::LIKE => 'like',
    ];

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform|null */
    protected $dbPlatform;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->dbPlatform = $connection->getDatabasePlatform();
    }

    /**
     * Check if this criterion handler accepts to handle the given criterion.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface $criterion
     *
     * @return bool
     */
    abstract public function accept(CriterionInterface $criterion);

    /**
     * Generate query expression for a Criterion this handler accepts.
     *
     * accept() must be called before calling this method.
     *
     * @param array $languageSettings
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     */
    abstract public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    );

    protected function hasJoinedTableAs(QueryBuilder $queryBuilder, string $tableAlias): bool
    {
        return $this->isTableJoined($queryBuilder, $tableAlias);
    }

    /**
     * Marks $tableAlias as already joined on $queryBuilder, for {@see hasJoinedTableAs()} to detect.
     */
    protected function markTableJoinedAs(QueryBuilder $queryBuilder, string $tableAlias): void
    {
        $this->markTableAsJoined($queryBuilder, $tableAlias);
    }
}
