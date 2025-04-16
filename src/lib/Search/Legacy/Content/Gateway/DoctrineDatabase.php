<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Gateway;
use LogicException;
use RuntimeException;

/**
 * Content locator gateway implementation using the Doctrine database.
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
final class DoctrineDatabase extends Gateway
{
    private Connection $connection;

    private AbstractPlatform $dbPlatform;

    /**
     * Criteria converter.
     */
    private CriteriaConverter $criteriaConverter;

    /**
     * Sort clause converter.
     */
    private SortClauseConverter $sortClauseConverter;

    /**
     * Language handler.
     */
    private Handler $languageHandler;

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

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function find(
        CriterionInterface $criterion,
        int $offset,
        int $limit,
        array $sort = null,
        array $languageFilter = [],
        bool $doCount = true
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
     * Generates a language mask from the given $languageSettings.
     *
     * @phpstan-param TSearchLanguageFilter $languageSettings
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getLanguageMask(array $languageSettings): int
    {
        $mask = 0;
        if ($languageSettings['useAlwaysAvailable'] ?? false) {
            $mask |= 1;
        }

        foreach ($languageSettings['languages'] ?? [] as $languageCode) {
            $mask |= $this->languageHandler->loadByLanguageCode($languageCode)->id;
        }

        return $mask;
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    private function getQueryCondition(
        CriterionInterface $filter,
        QueryBuilder $query,
        array $languageFilter
    ): CompositeExpression {
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
            $condition = $expr->and(
                $condition,
                $expr->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'c.language_mask',
                        $query->createNamedParameter(
                            $this->getLanguageMask($languageFilter),
                            ParameterType::INTEGER,
                            ':language_mask'
                        )
                    ),
                    $query->createNamedParameter(0, ParameterType::INTEGER, ':zero')
                )
            );
        }

        return $condition;
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
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

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * Get sorted arrays of content IDs, which should be returned.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $sort
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @phpstan-return list<array<string,mixed>>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Doctrine\DBAL\Exception
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

        if ($sort !== null) {
            $this->sortClauseConverter->applyJoin($query, $sort, $languageFilter);
        }

        $query->where(
            $this->getQueryCondition($filter, $query, $languageFilter)
        );

        if ($sort !== null) {
            $this->sortClauseConverter->applyOrderBy($query);
        }

        $query->setMaxResults($limit);
        if (null !== $offset) {
            $query->setFirstResult($offset);
        }

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getDatabasePlatform(): AbstractPlatform
    {
        if (!isset($this->dbPlatform)) {
            $dbPlatform = $this->connection->getDatabasePlatform();
            if (null === $dbPlatform) {
                throw new LogicException('Unable to get database platform');
            }

            $this->dbPlatform = $dbPlatform;
        }

        return $this->dbPlatform;
    }
}
