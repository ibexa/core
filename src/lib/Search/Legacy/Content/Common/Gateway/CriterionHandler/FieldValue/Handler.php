<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Operator as CriterionOperator;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\TransformationProcessor;
use RuntimeException;

/**
 * Content locator gateway implementation using the DoctrineDatabase.
 */
abstract class Handler
{
    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /**
     * Map of criterion operators to the respective function names
     * in the DoctrineDatabase DBAL.
     *
     * @var array
     */
    protected $comparatorMap = [
        CriterionOperator::EQ => 'eq',
        CriterionOperator::GT => 'gt',
        CriterionOperator::GTE => 'gte',
        CriterionOperator::LT => 'lt',
        CriterionOperator::LTE => 'lte',
    ];

    /**
     * Transformation processor.
     *
     * @var \Ibexa\Core\Persistence\TransformationProcessor
     */
    protected $transformationProcessor;

    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform|null */
    protected $dbPlatform;

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function __construct(Connection $connection, TransformationProcessor $transformationProcessor)
    {
        $this->connection = $connection;
        $this->dbPlatform = $connection->getDatabasePlatform();
        $this->transformationProcessor = $transformationProcessor;
    }

    /**
     * Generates query expression for operator and value of a Field Criterion.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $outerQuery to be used only for parameter binding
     * @param \Doctrine\DBAL\Query\QueryBuilder $subQuery to modify Field Value query constraints
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     *
     * @return \Doctrine\DBAL\Query\Expression\CompositeExpression|string
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException If passed more than 1 argument to unary operator.
     * @throws \RuntimeException If operator is not handled.
     */
    public function handle(
        QueryBuilder $outerQuery,
        QueryBuilder $subQuery,
        Criterion $criterion,
        string $column
    ) {
        if (is_array($criterion->value) && Criterion\Operator::isUnary($criterion->operator)) {
            if (count($criterion->value) > 1) {
                throw new InvalidArgumentException('$criterion->value', "Too many arguments for unary operator '$criterion->operator'");
            }

            $criterion->value = reset($criterion->value);
        }

        switch ($criterion->operator) {
            case Criterion\Operator::IN:
                $values = array_map([$this, 'prepareParameter'], $criterion->value);
                $filter = $subQuery->expr()->in(
                    $column,
                    $outerQuery->createNamedParameter(
                        $values,
                        $this->getParamArrayType($values)
                    )
                );
                break;

            case Criterion\Operator::BETWEEN:
                $filter = $this->dbPlatform->getBetweenExpression(
                    $column,
                    $outerQuery->createNamedParameter($this->lowerCase($criterion->value[0])),
                    $outerQuery->createNamedParameter($this->lowerCase($criterion->value[1]))
                );
                break;

            case Criterion\Operator::EQ:
            case Criterion\Operator::GT:
            case Criterion\Operator::GTE:
            case Criterion\Operator::LT:
            case Criterion\Operator::LTE:
                $operatorFunction = $this->comparatorMap[$criterion->operator];
                $filter = $subQuery->expr()->{$operatorFunction}(
                    $column,
                    $this->createNamedParameter($outerQuery, $column, $criterion->value)
                );
                break;

            case Criterion\Operator::LIKE:
                $value = str_replace('*', '%', $this->prepareLikeString($criterion->value));

                $filter = $subQuery->expr()->like(
                    $column,
                    $outerQuery->createNamedParameter($value)
                );
                break;

            case Criterion\Operator::CONTAINS:
                $filter = $subQuery->expr()->like(
                    $column,
                    $outerQuery->createNamedParameter(
                        '%' . $this->prepareLikeString($criterion->value) . '%'
                    )
                );
                break;

            default:
                throw new RuntimeException(
                    "Unknown operator '{$criterion->operator}' for Field Criterion handler."
                );
        }

        return $filter;
    }

    /**
     * Returns the given $string prepared for use in SQL LIKE clause.
     *
     * LIKE clause wildcards '%' and '_' contained in the given $string will be escaped.
     */
    protected function prepareLikeString(string $string): string
    {
        return addcslashes($this->lowerCase($string), '%_');
    }

    /**
     * Downcases a given string using string transformation processor.
     */
    protected function lowerCase(string $string): string
    {
        return $this->transformationProcessor->transformByGroup($string, 'lowercase');
    }

    /**
     * @param scalar|array<scalar> $value
     *
     * @return scalar|array<scalar>
     */
    private function prepareParameter($value)
    {
        if (is_string($value)) {
            return $this->lowerCase($value);
        }

        if (is_array($value)) {
            return array_map([$this, 'prepareParameter'], $value);
        }

        return $value;
    }

    private function createNamedParameter(QueryBuilder $outerQuery, string $column, $value): ?string
    {
        switch ($column) {
            case 'sort_key_string':
                $parameterValue = $this->prepareParameter($value);
                $parameterType = ParameterType::STRING;
                break;
            case 'sort_key_int':
                $parameterValue = (int)$value;
                $parameterType = ParameterType::INTEGER;
                break;
            default:
                $parameterValue = $value;
                $parameterType = null;
        }

        return $outerQuery->createNamedParameter(
            $parameterValue,
            $parameterType
        );
    }

    /**
     * @param array<int, scalar> $values
     */
    private function getParamArrayType(array $values): int
    {
        if (empty($values)) {
            throw new InvalidArgumentException('$values', 'Array cannot be empty');
        }

        $types = [];
        foreach ($values as $value) {
            if (is_bool($value) || ($value !== 0 && is_int($value))) {
                // Ignore 0 as ambiguous (float vs int)
                $types[] = Connection::PARAM_INT_ARRAY;
            } else {
                // Floats are considered strings
                $types[] = Connection::PARAM_STR_ARRAY;
            }
        }

        $arrayValueTypes = array_unique($types);

        // Fallback to Connection::PARAM_STR_ARRAY
        return $arrayValueTypes[0] ?? Connection::PARAM_STR_ARRAY;
    }
}
