<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

/**
 * Content type group criterion handler.
 */
class ContentTypeGroupId extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\ContentTypeGroupId;
    }

    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $subSelect = $this->connection->createQueryBuilder();
        $subSelect
            ->select(
                'contentclass_id'
            )->from(
                'ezcontentclass_classgroup'
            )->where(
                $queryBuilder->expr()->in(
                    'group_id',
                    $queryBuilder->createNamedParameter($criterion->value, Connection::PARAM_INT_ARRAY)
                )
            );

        return $queryBuilder->expr()->in(
            'c.contentclass_id',
            $subSelect->getSQL()
        );
    }
}
