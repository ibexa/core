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

abstract class CriterionHandler
{
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

    /** @var \WeakMap<QueryBuilder, array<string, true>>|null */
    private static ?\WeakMap $joinedAliasesByQueryBuilder = null;

    protected function hasJoinedTableAs(QueryBuilder $queryBuilder, string $tableAlias): bool
    {
        return isset(self::getJoinedAliases($queryBuilder)[$tableAlias]);
    }

    /**
     * Marks $tableAlias as already joined on $queryBuilder, for {@see hasJoinedTableAs()} to detect.
     */
    protected function markTableJoinedAs(QueryBuilder $queryBuilder, string $tableAlias): void
    {
        $joined = self::getJoinedAliases($queryBuilder);
        $joined[$tableAlias] = true;
        self::$joinedAliasesByQueryBuilder ??= new \WeakMap();
        self::$joinedAliasesByQueryBuilder[$queryBuilder] = $joined;
    }

    /**
     * @return array<string, true>
     */
    private static function getJoinedAliases(QueryBuilder $queryBuilder): array
    {
        self::$joinedAliasesByQueryBuilder ??= new \WeakMap();

        return self::$joinedAliasesByQueryBuilder[$queryBuilder] ?? [];
    }
}
