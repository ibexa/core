<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Content type group criterion handler.
 */
class IsContainer extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof Criterion\IsContainer;
    }

    /**
     * @phpstan-param array{languages: string[]} $languageSettings
     *
     * @param array $languageSettings
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'id'
            )->from(
                'ezcontentclass'
            )->where(
                $queryBuilder->expr()->eq(
                    'is_container',
                    $queryBuilder->createNamedParameter((int)reset($criterion->value), ParameterType::INTEGER)
                )
            );

        return $queryBuilder->expr()->in(
            'c.contentclass_id',
            $subSelect->getSQL()
        );
    }
}