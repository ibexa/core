<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\LanguagePriorityConditionBuilder;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Gateway;
use RuntimeException;

/**
 * Content locator gateway implementation using the Doctrine database.
 */
final class DoctrineDatabase extends Gateway
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CriteriaConverter $criteriaConverter,
        private readonly SortClauseConverter $sortClauseConverter,
        private readonly LanguagePriorityConditionBuilder $languagePriorityConditionBuilder
    ) {}

    public function find(
        CriterionInterface $criterion,
        $offset,
        $limit,
        ?array $sort = null,
        array $languageFilter = [],
        $doCount = true
    ): array {
        $count = $doCount ? $this->getResultCount($criterion, $languageFilter) : null;

        if (!$doCount && $limit === 0) {
            throw new RuntimeException('Invalid query. Cannot disable count and request 0 items at the same time.');
        }

        if ($limit === 0 || ($count !== null && $count <= $offset)) {
            return ['count' => $count, 'rows' => []];
        }

        $contentInfoList = $this->getContentInfoList($criterion, $sort, $offset, $limit, $languageFilter);

        return [
            'count' => $count,
            'rows' => $contentInfoList,
        ];
    }

    /**
     * @param array $languageFilter
     *
     * @return string
     *
     * @throws NotImplementedException
     */
    private function getQueryCondition(
        CriterionInterface $filter,
        QueryBuilder $query,
        array $languageFilter
    ): string {
        $expr = $query->expr();
        $condition = (string)$expr->and(
            $this->criteriaConverter->convertCriteria($query, $filter, $languageFilter),
            $expr->eq(
                'c.status',
                (string) ContentInfo::STATUS_PUBLISHED
            ),
            $expr->eq(
                'v.status',
                (string) VersionInfo::STATUS_PUBLISHED
            )
        );

        // If not main-languages query
        if (!empty($languageFilter['languages'])) {
            $languageIds = $this->languagePriorityConditionBuilder->loadLanguageIds($languageFilter['languages']);

            $languageCondition = $this->languagePriorityConditionBuilder->buildTranslationCondition(
                $query,
                $languageIds,
                !empty($languageFilter['useAlwaysAvailable'])
            );

            $condition = (string)$expr->and($condition, $languageCondition);
        }

        return $condition;
    }

    /**
     * @param array $languageFilter
     *
     * @return int
     */
    private function getResultCount(
        CriterionInterface $filter,
        array $languageFilter
    ): int {
        $query = $this->connection->createQueryBuilder();

        $columnName = 'c.id';
        $query
            ->select("COUNT( DISTINCT $columnName )")
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin(
                'c',
                ContentGateway::CONTENT_VERSION_TABLE,
                'v',
                'c.id = v.contentobject_id',
            );

        $query->where(
            $this->getQueryCondition($filter, $query, $languageFilter)
        );

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Get sorted arrays of content IDs, which should be returned.
     *
     * @param array $sort
     * @param mixed $offset
     * @param mixed $limit
     * @param array $languageFilter
     *
     * @return int[]
     *
     * @throws NotImplementedException
     */
    private function getContentInfoList(
        CriterionInterface $filter,
        ?array $sort,
        ?int $offset,
        ?int $limit,
        array $languageFilter
    ): array {
        $query = $this->connection->createQueryBuilder();
        $query->select(
            'DISTINCT c.*, main_tree.main_node_id AS main_tree_main_node_id',
            'c.initial_language_id AS matched_language_id',
        );

        if ($sort !== null) {
            $this->sortClauseConverter->applySelect($query, $sort);
        }

        $query
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin(
                'c',
                ContentGateway::CONTENT_VERSION_TABLE,
                'v',
                'c.id = v.contentobject_id'
            )
            ->leftJoin(
                'c',
                LocationGateway::CONTENT_TREE_TABLE,
                'main_tree',
                (string)$query->expr()->and(
                    'main_tree.contentobject_id = c.id',
                    'main_tree.main_node_id = main_tree.node_id'
                )
            );

        if (!empty($sort)) {
            $this->sortClauseConverter->applyJoin($query, $sort, $languageFilter);
        }

        $query->where(
            $this->getQueryCondition($filter, $query, $languageFilter)
        );

        if (!empty($sort)) {
            $this->sortClauseConverter->applyOrderBy($query);
        }

        $query->setMaxResults($limit);
        $query->setFirstResult($offset ?? 0);

        return $this->languagePriorityConditionBuilder->resolveMatchedLanguageIds(
            $query->executeQuery()->fetchAllAssociative(),
            $languageFilter,
            'id'
        );
    }
}
