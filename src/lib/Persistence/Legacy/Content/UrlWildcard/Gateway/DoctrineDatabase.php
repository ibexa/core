<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Persistence\Legacy\Content\UrlWildcard\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard;
use Ibexa\Contracts\Core\Persistence\Content\UrlWildcard\Handler;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
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
 * @see Handler
 */
final class DoctrineDatabase extends Gateway
{
    /**
     * 2^30, since PHP_INT_MAX can cause overflows in DB systems, if PHP is run
     * on 64 bit systems.
     */
    private const int MAX_LIMIT = 1073741824;

    public const array SORT_DIRECTION_MAP = [
        SortClause::SORT_ASC => 'ASC',
        SortClause::SORT_DESC => 'DESC',
    ];

    public function __construct(
        private readonly Connection $connection,
        protected CriteriaConverter $criteriaConverter
    ) {}

    public function insertUrlWildcard(UrlWildcard $urlWildcard): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->insert(self::URL_WILDCARD_TABLE)
            ->values(
                [
                    'destination_url' => $query->createPositionalParameter(
                        $this->trimUrl($urlWildcard->destinationUrl),
                        ParameterType::STRING
                    ),
                    'source_url' => $query->createPositionalParameter(
                        $this->trimUrl($urlWildcard->sourceUrl),
                        ParameterType::STRING
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
                    $this->trimUrl($destinationUrl),
                    ParameterType::STRING
                ),
            )->set(
                'source_url',
                $query->createPositionalParameter(
                    $this->trimUrl($sourceUrl),
                    ParameterType::STRING
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
        $result = $query->executeQuery()->fetch(FetchMode::ASSOCIATIVE);

        return false !== $result ? $result : [];
    }

    public function loadUrlWildcardsData(
        int $offset = 0,
        int $limit = -1
    ): array {
        $query = $this->buildLoadUrlWildcardDataQuery();
        $query
            ->setMaxResults($limit > 0 ? $limit : self::MAX_LIMIT)
            ->setFirstResult($offset);

        return $query->executeQuery()->fetchAllAssociative();
    }

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

        $statement = $query->executeQuery();

        return [
            'count' => $count,
            'rows' => $statement->fetchAllAssociative(),
        ];
    }

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

        $result = $query->executeQuery()->fetch(FetchMode::ASSOCIATIVE);

        return false !== $result ? $result : [];
    }

    public function countAll(): int
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select('COUNT(id)')
            ->from(self::URL_WILDCARD_TABLE);

        return (int) $query->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws Exception
     * @throws NotImplementedException
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
