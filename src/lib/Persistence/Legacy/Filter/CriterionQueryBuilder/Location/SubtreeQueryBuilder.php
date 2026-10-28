<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Filter\CriterionQueryBuilder\Location;

use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Filter\Doctrine\FilteringQueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;

use function array_map;

/**
 * @internal for internal use by Repository Filtering
 */
final class SubtreeQueryBuilder extends BaseLocationCriterionQueryBuilder
{
    public function accepts(FilteringCriterion $criterion): bool
    {
        return $criterion instanceof Subtree;
    }

    protected function buildLocationConstraint(
        FilteringQueryBuilder $queryBuilder,
        FilteringCriterion $criterion,
        string $locationAlias
    ): string {
        /** @var Subtree $criterion */
        $expressionBuilder = $queryBuilder->expr();
        $statements = array_map(
            static function (string $pathString) use ($queryBuilder, $expressionBuilder, $locationAlias): string {
                return $expressionBuilder->like(
                    "{$locationAlias}.path_string",
                    $queryBuilder->createNamedParameter($pathString . '%', ParameterType::STRING)
                );
            },
            $criterion->value
        );

        return (string)$expressionBuilder->or(...$statements);
    }
}
