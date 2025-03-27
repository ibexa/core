<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Location subtree criterion handler.
 */
class Subtree extends CriterionHandler
{
    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\Subtree $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $statements = [];
        foreach ($criterion->value as $pattern) {
            $statements[] = $queryBuilder->expr()->like(
                't.path_string',
                $queryBuilder->createNamedParameter($pattern . '%', ParameterType::STRING)
            );
        }

        return $queryBuilder->expr()->or(...$statements);
    }

    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\Subtree;
    }
}
