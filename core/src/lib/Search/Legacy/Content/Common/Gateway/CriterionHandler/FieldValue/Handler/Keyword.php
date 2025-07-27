<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\Handler;

use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\FieldType\Keyword\KeywordStorage\Gateway\DoctrineStorage;

/**
 * FieldValue CriterionHandler handling ibexa_keyword External Storage for Legacy/SQL Search.
 */
class Keyword extends Collection
{
    public function handle(
        QueryBuilder $outerQuery,
        QueryBuilder $subQuery,
        Criterion $criterion,
        string $column
    ) {
        $subQuery
            ->innerJoin(
                'f_def',
                DoctrineStorage::KEYWORD_ATTRIBUTE_LINK_TABLE,
                'kwd_lnk',
                'f_def.id = kwd_lnk.objectattribute_id'
            )
            ->innerJoin(
                'kwd_lnk',
                DoctrineStorage::KEYWORD_TABLE,
                'kwd',
                'kwd.id = kwd_lnk.keyword_id'
            );

        return parent::handle($outerQuery, $subQuery, $criterion, 'keyword');
    }
}
