<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Logical and criterion handler.
 */
class LogicalAnd extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\LogicalAnd;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd $criterion
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ): CompositeExpression {
        $subexpressions = [];
        foreach ($criterion->criteria as $subCriterion) {
            $subexpressions[] = $converter->convertCriteria(
                $queryBuilder,
                $subCriterion,
                $languageSettings
            );
        }

        return $queryBuilder->expr()->and(...$subexpressions);
    }
}
