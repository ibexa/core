<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Gateway;
use RuntimeException;

/**
 * Content locator gateway implementation using the Doctrine database.
 */
final class DoctrineDatabase extends Gateway
{
    /** @var \Doctrine\DBAL\Connection */
    private $connection;

    /**
     * Criteria converter.
     *
     * @var \Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter
     */
    private $criteriaConverter;

    /**
     * Sort clause converter.
     *
     * @var \Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter
     */
    private $sortClauseConverter;

    /**
     * Language handler.
     *
     * @var \Ibexa\Contracts\Core\Persistence\Content\Language\Handler
     */
    private $languageHandler;

    public function __construct(
        Connection $connection,
        CriteriaConverter $criteriaConverter,
        SortClauseConverter $sortClauseConverter,
        LanguageHandler $languageHandler
    ) {
        $this->connection = $connection;
        $this->criteriaConverter = $criteriaConverter;
        $this->sortClauseConverter = $sortClauseConverter;
        $this->languageHandler = $languageHandler;
    }

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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    private function getQueryCondition(
        CriterionInterface $filter,
        QueryBuilder $query,
        array $languageFilter
    ) {
        $expr = $query->expr();
        $condition = $expr->and(
            $this->criteriaConverter->convertCriteria($query, $filter, $languageFilter),
            $expr->eq(
                'c.status',
                ContentInfo::STATUS_PUBLISHED
            ),
            $expr->eq(
                'v.status',
                VersionInfo::STATUS_PUBLISHED
            )
        );

        // If not main-languages query
        if (!empty($languageFilter['languages'])) {
            $languageIds = array_map(
                fn (string $languageCode): int => $this->languageHandler->loadByLanguageCode($languageCode)->id,
                $languageFilter['languages']
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
                            $query->createNamedParameter($languageIds, ArrayParameterType::INTEGER)
                        )
                    )
                );

            $languageCondition = sprintf('EXISTS (%s)', $translationExistsSubQuery->getSQL());

            if (!empty($languageFilter['useAlwaysAvailable'])) {
                $languageCondition = $expr->or($languageCondition, 'c.always_available');
            }

            $condition = $expr->and($condition, $languageCondition);
        }

        return $condition;
    }

    /**
     * @param array $languageFilter
     *
     * @return int
     */
    private function getResultCount(CriterionInterface $filter, array $languageFilter): int
    {
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
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
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
                $query->expr()->and(
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
        $query->setFirstResult($offset);

        $statement = $query->executeQuery();

        return $statement->fetchAllAssociative();
    }
}
