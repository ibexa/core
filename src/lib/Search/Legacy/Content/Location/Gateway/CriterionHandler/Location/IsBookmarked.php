<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway\CriterionHandler\Location;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Exception\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Core\Persistence\Legacy\Bookmark\Gateway\DoctrineDatabase;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler;

final class IsBookmarked extends CriterionHandler
{
    private PermissionResolver $permissionResolver;

    public function __construct(
        Connection $connection,
        PermissionResolver $permissionResolver
    ) {
        parent::__construct($connection);

        $this->permissionResolver = $permissionResolver;
    }

    public function accept(Criterion $criterion): bool
    {
        return $criterion instanceof Criterion\Location\IsBookmarked
            && $criterion->operator === Criterion\Operator::EQ;
    }

    /**
     * @param array{languages: string[]} $languageSettings
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function handle(
        CriteriaConverter $converter,
        QueryBuilder $queryBuilder,
        Criterion $criterion,
        array $languageSettings
    ) {
        $userId = $this->getUserId($criterion);

        $subQueryBuilder = $this->connection->createQueryBuilder();
        $subQueryBuilder
            ->select('1')
            ->from(DoctrineDatabase::TABLE_BOOKMARKS, 'b')
            ->andWhere(
                $queryBuilder
                    ->expr()
                    ->eq(
                        'b.' . DoctrineDatabase::COLUMN_USER_ID,
                        $queryBuilder->createNamedParameter($userId, ParameterType::INTEGER)
                    ),
                $queryBuilder
                    ->expr()
                    ->eq('b.node_id', 't.node_id')
            );

        return sprintf(
            'EXISTS (%s)',
            $subQueryBuilder->getSQL()
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Exception\InvalidArgumentException
     */
    private function getUserId(Criterion $criterion): int
    {
        $valueData = $criterion->valueData;
        if (!$valueData instanceof Criterion\Value\IsBookmarkedValue) {
            throw new InvalidArgumentException(
                '$criterion->valueData',
                sprintf(
                    'Is expected to be of type: "%s", got "%s"',
                    Criterion\Value\IsBookmarkedValue::class,
                    get_debug_type($valueData)
                )
            );
        }

        return $valueData->getUserId() ?? $this->permissionResolver->getCurrentUserReference()->getUserId();
    }
}
