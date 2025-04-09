<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\SortClause;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;
use Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Query\CriteriaConverter;
use RuntimeException;

/**
 * URL wildcard gateway implementation using the Doctrine database.
 *
 * @internal Gateway implementation is considered internal. Use Persistence UrlWildcard Handler instead.
 *
 * @see \Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * 2^30, since PHP_INT_MAX can cause overflows in DB systems, if PHP is run on 64-bit systems.
     */
    private const int MAX_LIMIT = 1073741824;

    private Connection $connection;

    protected CriteriaConverter $criteriaConverter;

    /** @phpstan-var array<\Ibexa\Contracts\Core\Repository\Values\Content\URLWildcard\Query\SortClause::SORT_*, 'ASC'|'DESC'> */
    public const array SORT_DIRECTION_MAP = [
        SortClause::SORT_ASC => 'ASC',
        SortClause::SORT_DESC => 'DESC',
    ];

    public function __construct(Connection $connection, CriteriaConverter $criteriaConverter)
    {
        $this->connection = $connection;
        $this->criteriaConverter = $criteriaConverter;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertUrlWildcard(UrlWildcard $urlWildcard): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::URL_WILDCARD_TABLE)
            ->values(
                [
                    'destination_url' => $query->createPositionalParameter(
                        $this->trimUrl($urlWildcard->destinationUrl)
                    ),
                    'source_url' => $query->createPositionalParameter(
                        $this->trimUrl($urlWildcard->sourceUrl)
                    ),
                    'type' => $query->createPositionalParameter(
                        $urlWildcard->forward ? 1 : 2,
                        ParameterType::INTEGER
                    ),
                ]
            );

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(self::URL_WILDCARD_SEQ);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateUrlWildcard(
        int $id,
        string $sourceUrl,
        string $destinationUrl,
        bool $forward
    ): void {
        $query = $this->connection->createQueryBuilder();

        $query
            ->update(self::URL_WILDCARD_TABLE)
            ->set(
                'destination_url',
                $query->createPositionalParameter(
                    $this->trimUrl($destinationUrl)
                ),
            )->set(
                'source_url',
                $query->createPositionalParameter(
                    $this->trimUrl($sourceUrl)
                ),
            )->set(
                'type',
                $query->createPositionalParameter(
                    $forward ? 1 : 2,
                    ParameterType::INTEGER
                )
            );

        $query->where(
            $query->expr()->eq(
                'id',
                $query->createPositionalParameter(
                    $id,
                    ParameterType::INTEGER
                )
            )
        );

        $query->executeStatement();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function deleteUrlWildcard(int $id): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->delete(self::URL_WILDCARD_TABLE)
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );
        $query->executeStatement();
    }

    private function buildLoadUrlWildcardDataQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('id', 'destination_url', 'source_url', 'type')
            ->from(self::URL_WILDCARD_TABLE);

        return $query;
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUrlWildcardData(int $id): array
    {
        $query = $this->buildLoadUrlWildcardDataQuery();
        $query
            ->where(
                $query->expr()->eq(
                    'id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUrlWildcardsData(int $offset = 0, int $limit = -1): array
    {
        $query = $this->buildLoadUrlWildcardDataQuery();
        $query
            ->setMaxResults($limit > 0 ? $limit : self::MAX_LIMIT)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Doctrine\DBAL\Exception
     */
    public function find(
        Criterion $criterion,
        int $offset,
        int $limit,
        array $sortClauses = [],
        bool $doCount = true
    ): array {
        $count = $doCount ? $this->doCount($criterion) : null;
        if (!$doCount && $limit === 0) {
            throw new RuntimeException('Invalid query. Cannot disable count and request 0 items at the same time');
        }

        if ($limit === 0 || ($count !== null && $count <= $offset)) {
            return [
                'count' => $count,
                'rows' => [],
            ];
        }

        if ($limit < 0) {
            throw new InvalidArgumentException('$limit', 'The limit need be higher than 0');
        }

        $query = $this->buildLoadUrlWildcardDataQuery();
        $query
            ->where($this->criteriaConverter->convertCriteria($query, $criterion))
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        foreach ($sortClauses as $sortClause) {
            $query->addOrderBy($sortClause->target, $this->getQuerySortingDirection($sortClause->direction));
        }

        return [
            'count' => $count,
            'rows' => $query->executeQuery()->fetchAllAssociative(),
        ];
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function loadUrlWildcardBySourceUrl(string $sourceUrl): array
    {
        $query = $this->buildLoadUrlWildcardDataQuery();
        $expr = $query->expr();
        $query
            ->where(
                $expr->eq(
                    'source_url',
                    $query->createPositionalParameter($sourceUrl)
                )
            );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function countAll(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(self::URL_WILDCARD_TABLE);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    protected function doCount(Criterion $criterion): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(url_wildcard.id)')
            ->from(self::URL_WILDCARD_TABLE, 'url_wildcard')
            ->where($this->criteriaConverter->convertCriteria($query, $criterion));

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    private function getQuerySortingDirection(string $direction): string
    {
        if (!isset(self::SORT_DIRECTION_MAP[$direction])) {
            throw new InvalidArgumentException(
                '$sortClause->direction',
                sprintf(
                    'Unsupported "%s" sorting direction, use one of the SortClause::SORT_* constants instead',
                    $direction
                )
            );
        }

        return self::SORT_DIRECTION_MAP[$direction];
    }

    private function trimUrl(string $url): string
    {
        return trim($url, '/');
    }
}
