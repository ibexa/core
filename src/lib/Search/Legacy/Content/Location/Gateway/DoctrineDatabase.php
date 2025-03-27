<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway;
use RuntimeException;

/**
 * Location gateway implementation using the Doctrine database.
 *
 * @phpstan-import-type TSearchLanguageFilter from \Ibexa\Contracts\Core\Repository\SearchService
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * 2^30, since PHP_INT_MAX can cause overflows in DB systems, if PHP is run on 64-bit systems.
     */
    public const int MAX_LIMIT = 1073741824;

    private Connection $connection;

    private CriteriaConverter $criteriaConverter;

    private SortClauseConverter $sortClauseConverter;

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
        array $sortClauses = null,
        array $languageFilter = [],
        bool $doCount = true
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
            'c.language_mask',
            'c.initial_language_id'
        );

        if ($sortClauses !== null) {
            $this->sortClauseConverter->applySelect($selectQuery, $sortClauses);
        }

        $selectQuery
            ->from('ezcontentobject_tree', 't')
            ->innerJoin(
                't',
                'ezcontentobject',
                'c',
                't.contentobject_id = c.id'
            )
            ->innerJoin(
                'c',
                'ezcontentobject_version',
                'v',
                'c.id = v.contentobject_id',
            );

        if ($sortClauses !== null) {
            $this->sortClauseConverter->applyJoin($selectQuery, $sortClauses, $languageFilter);
        }

        $this->addFindConstraints($selectQuery, $criterion, $languageFilter);

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
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    private function getTotalCount(CriterionInterface $criterion, array $languageFilter): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(t.node_id)')
            ->from('ezcontentobject_tree', 't')
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

        $this->addFindConstraints($query, $criterion, $languageFilter);

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Generates a language mask from the given $languageFilter.
     *
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function getLanguageMask(array $languageFilter): int
    {
        if (!isset($languageFilter['languages'])) {
            $languageFilter['languages'] = [];
        }

        if (!isset($languageFilter['useAlwaysAvailable'])) {
            $languageFilter['useAlwaysAvailable'] = true;
        }

        $mask = 0;
        if ($languageFilter['useAlwaysAvailable']) {
            $mask |= 1;
        }

        foreach ($languageFilter['languages'] as $languageCode) {
            $mask |= $this->languageHandler->loadByLanguageCode($languageCode)->id;
        }

        return $mask;
    }

    /**
     * @phpstan-param TSearchLanguageFilter $languageFilter
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    private function addFindConstraints(
        QueryBuilder $selectQuery,
        CriterionInterface $criterion,
        array $languageFilter
    ): void {
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
            $databasePlatform = $this->connection->getDatabasePlatform();
            if (null === $databasePlatform) {
                throw new \LogicException('Unable to determine DBMS');
            }
            $selectQuery->andWhere(
                $selectQuery->expr()->gt(
                    $databasePlatform->getBitAndComparisonExpression(
                        'c.language_mask',
                        $selectQuery->createNamedParameter(
                            $this->getLanguageMask($languageFilter),
                            ParameterType::INTEGER
                        )
                    ),
                    $selectQuery->createNamedParameter(0, ParameterType::INTEGER)
                )
            );
        }
    }
}
