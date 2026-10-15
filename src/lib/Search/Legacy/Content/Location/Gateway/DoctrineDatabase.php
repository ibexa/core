<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Search\Legacy\Content\Location\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Ibexa\Contracts\Core\Persistence\Content\Language\Handler as LanguageHandler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\CriterionInterface;
use Ibexa\Core\Base\Exceptions\DatabaseException;
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
    ) {}

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
            'c.language_mask',
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
            $selectQuery->andWhere(
                $selectQuery->expr()->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
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
     * @throws NotImplementedException
     * @throws NotFoundException
     */
    private function getTotalCount(
        CriterionInterface $criterion,
        array $languageFilter
    ): int {
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
            $query->andWhere(
                $query->expr()->gt(
                    $this->getDatabasePlatform()->getBitAndComparisonExpression(
                        'c.language_mask',
                        $query->createNamedParameter(
                            $this->getLanguageMask($languageFilter),
                            ParameterType::INTEGER
                        )
                    ),
                    $query->createNamedParameter(0, ParameterType::INTEGER)
                )
            );
        }

        $statement = $query->executeQuery();

        return (int)$statement->fetchOne();
    }

    /**
     * Generates a language mask from the given $languageFilter.
     *
     * @throws NotFoundException
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

    private function getDatabasePlatform(): AbstractPlatform
    {
        try {
            return $this->connection->getDatabasePlatform();
        } catch (Exception $e) {
            throw DatabaseException::wrap($e);
        }
    }
}
