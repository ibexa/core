<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway;
use RuntimeException;

/**
 * Location gateway implementation using the Doctrine database.
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * 2^30, since PHP_INT_MAX can cause overflows in DB systems, if PHP is run
     * on 64 bit systems.
     */
    public const int MAX_LIMIT = 1073741824;

    public function __construct(
        private readonly Connection $connection,
        private readonly CriteriaConverter $criteriaConverter,
        private readonly SortClauseConverter $sortClauseConverter,
        private readonly LanguageHandler $languageHandler
    ) {
    }

    public function find(
        CriterionInterface $criterion,
        $offset,
        $limit,
        ?array $sortClauses = null,
        array $languageFilter = [],
        $doCount = true
    ): array {
        $count = $doCount ? $this->getTotalCount($criterion, $languageFilter) : null;

        if (!$doCount && $limit === 0) {
            throw new RuntimeException('Invalid query. Cannot disable count and request 0 items at the same time.');
        }

        if ($limit === 0 || ($count !== null && $count <= $offset)) {
            return ['count' => $count, 'rows' => []];
        }

        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery->select(
            't.*',
            'c.always_available',
            'c.initial_language_id'
        );

        if ($sortClauses !== null) {
            $this->sortClauseConverter->applySelect($selectQuery, $sortClauses);
        }

        $selectQuery
            ->from(LocationGateway::CONTENT_TREE_TABLE, 't')
            ->innerJoin(
                't',
                ContentGateway::CONTENT_ITEM_TABLE,
                'c',
                't.contentobject_id = c.id'
            )
            ->innerJoin(
                'c',
                ContentGateway::CONTENT_VERSION_TABLE,
                'v',
                'c.id = v.contentobject_id',
            );

        if ($sortClauses !== null) {
            $this->sortClauseConverter->applyJoin($selectQuery, $sortClauses, $languageFilter);
        }

        $selectQuery->where(
            $this->criteriaConverter->convertCriteria($selectQuery, $criterion, $languageFilter),
            $selectQuery->expr()->eq(
                'c.status',
                //ContentInfo::STATUS_PUBLISHED
                $selectQuery->createNamedParameter(1, ParameterType::INTEGER)
            ),
            $selectQuery->expr()->eq(
                'v.status',
                //VersionInfo::STATUS_PUBLISHED
                $selectQuery->createNamedParameter(1, ParameterType::INTEGER)
            ),
            $selectQuery->expr()->neq(
                't.depth',
                $selectQuery->createNamedParameter(0, ParameterType::INTEGER)
            )
        );

        // If not main-languages query
        if (!empty($languageFilter['languages'])) {
            $selectQuery->andWhere($this->buildTranslationCondition($selectQuery, $languageFilter));
        }

        if ($sortClauses !== null) {
            $this->sortClauseConverter->applyOrderBy($selectQuery);
        }

        $selectQuery->setMaxResults($limit);
        $selectQuery->setFirstResult($offset);

        $statement = $selectQuery->executeQuery();

        return [
            'count' => $count,
            'rows' => $statement->fetchAllAssociative(),
        ];
    }

    /**
     * Returns total results count for $criterion and $sortClauses.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getTotalCount(CriterionInterface $criterion, array $languageFilter): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(t.node_id)')
            ->from(LocationGateway::CONTENT_TREE_TABLE, 't')
            ->innerJoin(
                't',
                ContentGateway::CONTENT_ITEM_TABLE,
                'c',
                't.contentobject_id = c.id'
            )
            ->innerJoin(
                'c',
                ContentGateway::CONTENT_VERSION_TABLE,
                'v',
                'c.id = v.contentobject_id'
            );

        $query->where(
            $this->criteriaConverter->convertCriteria($query, $criterion, $languageFilter),
            $query->expr()->eq(
                'c.status',
                //ContentInfo::STATUS_PUBLISHED
                $query->createNamedParameter(1, ParameterType::INTEGER)
            ),
            $query->expr()->eq(
                'v.status',
                //VersionInfo::STATUS_PUBLISHED
                $query->createNamedParameter(1, ParameterType::INTEGER)
            ),
            $query->expr()->neq(
                't.depth',
                $query->createNamedParameter(0, ParameterType::INTEGER)
            )
        );

        // If not main-languages query
        if (!empty($languageFilter['languages'])) {
            $query->andWhere($this->buildTranslationCondition($query, $languageFilter));
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Builds the "content is translated into one of the requested languages, or it's
     * always-available" condition shared by find() and getTotalCount() - kept as one place so the
     * two queries can't drift out of sync on the always-available fallback.
     *
     * @param \Doctrine\DBAL\Query\QueryBuilder $queryBuilder
     * @param array{languages?: string[], useAlwaysAvailable?: bool} $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function buildTranslationCondition($queryBuilder, array $languageFilter): string
    {
        $languageIds = array_map(
            fn (string $languageCode): int => $this->languageHandler->loadByLanguageCode($languageCode)->id,
            $languageFilter['languages'] ?? []
        );

        $translationExistsSubQuery = $this->connection->createQueryBuilder();
        $translationExistsSubQuery
            ->select('1')
            ->from('ibexa_content_translation', 'ct')
            ->where(
                $translationExistsSubQuery->expr()->and(
                    'ct.content_id = c.id',
                    $translationExistsSubQuery->expr()->in(
                        'ct.language_id',
                        $queryBuilder->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                    )
                )
            );

        $translationCondition = sprintf('EXISTS (%s)', $translationExistsSubQuery->getSQL());

        if ($languageFilter['useAlwaysAvailable'] ?? true) {
            $translationCondition = $queryBuilder->expr()->or(
                $translationCondition,
                $queryBuilder->expr()->eq(
                    'c.always_available',
                    $queryBuilder->createNamedParameter(true, ParameterType::BOOLEAN)
                )
            );
        }

        return $translationCondition;
    }
}
