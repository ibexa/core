<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use RuntimeException;

/**
 * Date metadata criterion handler.
 */
class DateMetadata extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\DateMetadata;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\DateMetadata $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): string {
        $column = $this->getColumnName($criterion);

        $value = (array)$criterion->value;
        switch ($criterion->operator) {
            case Criterion\Operator::IN:
                return $queryBuilder->expr()->in(
                    $column,
                    $queryBuilder->createNamedParameter($value, ArrayParameterType::INTEGER)
                );

            case Criterion\Operator::BETWEEN:
                return sprintf(
                    '%s BETWEEN %s AND %s',
                    $column,
                    $queryBuilder->createNamedParameter($value[0], ParameterType::INTEGER),
                    $queryBuilder->createNamedParameter($value[1], ParameterType::INTEGER)
                );

            case Criterion\Operator::EQ:
            case Criterion\Operator::GT:
            case Criterion\Operator::GTE:
            case Criterion\Operator::LT:
            case Criterion\Operator::LTE:
                $operatorFunction = $this->comparatorMap[$criterion->operator];

                return $queryBuilder->expr()->$operatorFunction(
                    $column,
                    $queryBuilder->createNamedParameter(reset($value), ParameterType::INTEGER)
                );

            default:
                throw new RuntimeException(
                    "Unknown operator '{$criterion->operator}' for DateMetadata Criterion handler."
                );
        }
    }

    private function getColumnName(Criterion $criterion): string
    {
        return match ($criterion->target) {
            Criterion\DateMetadata::TRASHED => 't.' . Criterion\DateMetadata::TRASHED,
            Criterion\DateMetadata::MODIFIED => 'c.' . Criterion\DateMetadata::MODIFIED,
            default => 'c.' . Criterion\DateMetadata::PUBLISHED,
        };
    }
}
