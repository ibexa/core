<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Persistence\Filter\Doctrine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator;
use Ibexa\Core\Base\Exceptions\DatabaseException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use function sprintf;

/**
 * Repository Filtering query builder wrapper for \Doctrine\DBAL\Query\QueryBuilder.
 *
 * **NOTE:** To be used **only** with Repository Content/Location Filtering feature.
 *
 * @see \Doctrine\DBAL\Query\QueryBuilder
 */
final class FilteringQueryBuilder extends QueryBuilder
{
    public const SORT_ORDER_MAP = [Query::SORT_ASC => 'ASC', Query::SORT_DESC => 'DESC'];

    /** @var array<string, string> Map of join target alias => join condition. */
    private array $joinConditionsByAlias = [];

    /** @var array<string, true> Set of aliases a join has been created FROM. */
    private array $joinedFromAliases = [];

    /** @var array<string, true> Set of root FROM aliases. */
    private array $fromAliases = [];

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::from.
     *
     * @param string $from
     * @param string|null $alias
     *
     * @return $this
     */
    public function from($from, $alias = null): self
    {
        if ($alias !== null) {
            $this->fromAliases[$alias] = true;
        }

        return parent::from($from, $alias);
    }

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::join.
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|\Stringable|null $condition
     *
     * @return $this
     */
    public function join($fromAlias, $join, $alias, $condition = null): self
    {
        $this->trackJoin($fromAlias, $alias, $condition);

        return parent::join($fromAlias, $join, $alias, $condition);
    }

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::innerJoin.
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|\Stringable|null $condition
     *
     * @return $this
     */
    public function innerJoin($fromAlias, $join, $alias, $condition = null): self
    {
        $this->trackJoin($fromAlias, $alias, $condition);

        return parent::innerJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::leftJoin.
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|\Stringable|null $condition
     *
     * @return $this
     */
    public function leftJoin($fromAlias, $join, $alias, $condition = null): self
    {
        $this->trackJoin($fromAlias, $alias, $condition);

        return parent::leftJoin($fromAlias, $join, $alias, $condition);
    }

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::rightJoin.
     *
     * @param string $fromAlias
     * @param string $join
     * @param string $alias
     * @param string|\Stringable|null $condition
     *
     * @return $this
     */
    public function rightJoin($fromAlias, $join, $alias, $condition = null): self
    {
        $this->trackJoin($fromAlias, $alias, $condition);

        return parent::rightJoin($fromAlias, $join, $alias, $condition);
    }

    private function trackJoin(string $fromAlias, string $alias, string|\Stringable|null $condition): void
    {
        $this->joinedFromAliases[$fromAlias] = true;
        $this->joinConditionsByAlias[$alias] = $condition !== null ? (string)$condition : '';
    }

    /**
     * Whether $alias has been registered as a root FROM alias (via {@see from()}).
     */
    public function hasFromAlias(string $alias): bool
    {
        return isset($this->fromAliases[$alias]);
    }

    /**
     * Create table JOIN, but only if it hasn't been already joined (determined based on $tableAlias).
     *
     * @throws \Ibexa\Core\Base\Exceptions\DatabaseException if conditions of pre-existing same alias joins are different
     */
    public function joinOnce(
        string $fromAlias,
        string $tableName,
        string $tableAlias,
        string $conditions
    ): FilteringQueryBuilder {
        $existingJoinConditions = $this->getExistingTableAliasJoinCondition($tableAlias);
        if (null !== $existingJoinConditions) {
            $this->validateJoinOnceConditions(
                $existingJoinConditions,
                $conditions,
                $tableName,
                $tableAlias
            );

            return $this;
        }

        // at this point, if table exists as fromAlias, it means it's a "FROM" table
        if ($this->isJoinedAsFromTableAlias($tableAlias)) {
            return $this;
        }

        $this->join($fromAlias, $tableName, $tableAlias, $conditions);

        return $this;
    }

    /**
     * Create table LEFT JOIN, but only if it hasn't been already joined (determined based on $tableAlias).
     *
     * @throws \Ibexa\Core\Base\Exceptions\DatabaseException if conditions of pre-existing same alias joins are different
     */
    public function leftJoinOnce(
        string $fromAlias,
        string $tableName,
        string $tableAlias,
        string $conditions
    ): FilteringQueryBuilder {
        $existingJoinConditions = $this->getExistingTableAliasJoinCondition($tableAlias);
        if (null !== $existingJoinConditions) {
            $this->validateJoinOnceConditions(
                $existingJoinConditions,
                $conditions,
                $tableName,
                $tableAlias
            );

            return $this;
        }

        // at this point, if table exists as fromAlias, it means it's a "FROM" table
        if ($this->isJoinedAsFromTableAlias($tableAlias)) {
            return $this;
        }

        $this->leftJoin($fromAlias, $tableName, $tableAlias, $conditions);

        return $this;
    }

    /**
     * @return string conditions, null if table is not joined yet.
     */
    public function getExistingTableAliasJoinCondition(string $tableAlias): ?string
    {
        $joinCondition = $this->joinConditionsByAlias[$tableAlias] ?? null;

        return '' !== $joinCondition && null !== $joinCondition ? $joinCondition : null;
    }

    /**
     * Inherited from \Doctrine\DBAL\Query\QueryBuilder::addOrderBy.
     *
     * @param string $sort
     * @param string|null $order
     */
    public function addOrderBy($sort, $order = null): FilteringQueryBuilder
    {
        return parent::addOrderBy($sort, $this->mapLegacyOrderToDoctrine($order));
    }

    private function mapLegacyOrderToDoctrine(?string $order): ?string
    {
        if (null !== $order && isset(self::SORT_ORDER_MAP[$order])) {
            return self::SORT_ORDER_MAP[$order];
        }

        // intentionally pass through
        return $order;
    }

    private function validateJoinOnceConditions(
        string $existingJoinConditions,
        string $conditions,
        string $tableName,
        string $tableAlias
    ): void {
        if ($existingJoinConditions !== $conditions) {
            throw new DatabaseException(
                sprintf(
                    'FilteringQueryBuilder: "%s" table cannot be joined as "%s" ' .
                    'with conditions "%s" because there is a pre-existing join with the same ' .
                    'alias but different conditions: "%s"',
                    $tableName,
                    $tableAlias,
                    $conditions,
                    $existingJoinConditions
                )
            );
        }
    }

    private function isJoinedAsFromTableAlias(string $tableAlias): bool
    {
        return isset($this->joinedFromAliases[$tableAlias]);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function buildOperatorBasedCriterionConstraint(
        string $columnName,
        array $criterionValue,
        string $operator
    ): string {
        switch ($operator) {
            case Operator::IN:
                return $this->expr()->in(
                    $columnName,
                    $this->createNamedParameter($criterionValue, ArrayParameterType::INTEGER)
                );

            case Query\Criterion\Operator::BETWEEN:
                return sprintf(
                    '%s BETWEEN %s AND %s',
                    $columnName,
                    $this->createNamedParameter($criterionValue[0], ParameterType::INTEGER),
                    $this->createNamedParameter($criterionValue[1], ParameterType::INTEGER)
                );

            case Query\Criterion\Operator::EQ:
            case Query\Criterion\Operator::GT:
            case Query\Criterion\Operator::GTE:
            case Query\Criterion\Operator::LT:
            case Query\Criterion\Operator::LTE:
                return $this->expr()->comparison(
                    $columnName,
                    $operator,
                    $this->createNamedParameter(reset($criterionValue), ParameterType::INTEGER)
                );

            default:
                throw new DatabaseException(
                    "Unsupported operator {$operator} for column {$columnName}"
                );
        }
    }

    public function joinPublishedVersion(): FilteringQueryBuilder
    {
        $expressionBuilder = $this->expr();

        $this->joinOnce(
            'content',
            ContentGateway::CONTENT_VERSION_TABLE,
            'version',
            (string)$expressionBuilder->and(
                'content.id = version.contentobject_id',
                'content.current_version = version.version',
                $expressionBuilder->eq(
                    'version.status',
                    $this->createNamedParameter(
                        VersionInfo::STATUS_PUBLISHED,
                        ParameterType::INTEGER
                    )
                )
            )
        );

        return $this;
    }

    public function joinAllLocations(): FilteringQueryBuilder
    {
        $this->joinOnce(
            'content',
            LocationGateway::CONTENT_TREE_TABLE,
            'location',
            'content.id = location.contentobject_id'
        );

        return $this;
    }
}
