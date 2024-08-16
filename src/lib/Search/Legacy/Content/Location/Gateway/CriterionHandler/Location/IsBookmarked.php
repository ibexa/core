<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway\CriterionHandler\Location;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

final class IsBookmarked extends CriterionHandler
{
    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof Criterion\Location\IsBookmarked
            && $criterion->operator === Criterion\Operator::EQ;
    }

    /**
     * @param array{languages: string[]} $languageSettings
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $subQueryBuilder = $this->connection->createQueryBuilder();
        $subQueryBuilder
            ->select(DoctrineDatabase::COLUMN_LOCATION_ID)
            ->from(DoctrineDatabase::TABLE_BOOKMARKS)
            ->andWhere(
                $queryBuilder
                ->expr()
                ->eq(
                    DoctrineDatabase::COLUMN_USER_ID,
                    $queryBuilder->createNamedParameter(
                        $criterion->value[0],
                        Types::INTEGER
                    )
                )
            );

        return $queryBuilder->expr()->in(
            't.node_id',
            $subQueryBuilder->getSQL()
        );
    }
}
