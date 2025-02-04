<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;
use RuntimeException;

/**
 * User metadata criterion handler.
 */
class UserMetadata extends CriterionHandler
{
    public function accept(CriterionInterface $criterion): bool
    {
        return $criterion instanceof Criterion\UserMetadata;
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\UserMetadata $criterion
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        CriterionInterface $criterion,
        array $languageSettings
    ) {
        $value = (array)$criterion->value;
        switch ($criterion->target) {
            case Criterion\UserMetadata::MODIFIER:
                return $queryBuilder->expr()->in(
                    'v.creator_id',
                    $queryBuilder->createNamedParameter($value, Connection::PARAM_INT_ARRAY)
                );

            case Criterion\UserMetadata::GROUP:
                $subSelect = $this->connection->createQueryBuilder();
                $subSelect
                    ->select(
                        't1.contentobject_id'
                    )->from(
                        LocationGateway::CONTENT_TREE_TABLE,
                        't1'
                    )->innerJoin(
                        't1',
                        LocationGateway::CONTENT_TREE_TABLE,
                        't2',
                        $queryBuilder->expr()->like(
                            't1.path_string',
                            $this->dbPlatform->getConcatExpression(
                                't2.path_string',
                                $queryBuilder->createNamedParameter('%', ParameterType::STRING)
                            )
                        )
                    )->where(
                        $queryBuilder->expr()->in(
                            't2.contentobject_id',
                            $queryBuilder->createNamedParameter($value, Connection::PARAM_INT_ARRAY)
                        )
                    );

                return $queryBuilder->expr()->in(
                    'c.owner_id',
                    $subSelect->getSQL()
                );

            case Criterion\UserMetadata::OWNER:
                return $queryBuilder->expr()->in(
                    'c.owner_id',
                    $queryBuilder->createNamedParameter($value, Connection::PARAM_INT_ARRAY)
                );
            default:
                break;
        }

        throw new RuntimeException("Invalid target Criterion: '" . $criterion->target . "'");
    }
}
