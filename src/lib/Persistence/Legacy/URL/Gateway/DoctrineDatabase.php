<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Persistence\Legacy\URL\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Contracts\Core\Persistence\URL\URL;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\SortClause;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\URL\Gateway;
use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use RuntimeException;

/**
 * URL gateway implementation using the Doctrine.
 */
class DoctrineDatabase extends Gateway
{
    public const URL_TABLE = 'ibexa_url';

    public const URL_LINK_TABLE = 'ibexa_url_content_link';

    public const COLUMN_ID = 'id';
    public const COLUMN_URL = 'url';
    public const COLUMN_ORIGINAL_URL_MD5 = 'original_url_md5';
    public const COLUMN_IS_VALID = 'is_valid';
    public const COLUMN_LAST_CHECKED = 'last_checked';
    public const COLUMN_MODIFIED = 'modified';
    public const COLUMN_CREATED = 'created';

    public const SORT_DIRECTION_MAP = [
        SortClause::SORT_ASC => 'ASC',
        SortClause::SORT_DESC => 'DESC',
    ];

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    /**
     * Criteria converter.
     *
     * @var \Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter
     */
    protected $criteriaConverter;

    public function __construct(Connection $connection, CriteriaConverter $criteriaConverter)
    {
        $this->connection = $connection;
        $this->criteriaConverter = $criteriaConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Criterion $criterion, $offset, $limit, array $sortClauses = [], $doCount = true)
    {
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

        $query = $this->createSelectDistinctQuery();
        $query
            ->where($this->criteriaConverter->convertCriteria($query, $criterion))
            ->setMaxResults($limit > 0 ? $limit : PHP_INT_MAX)
            ->setFirstResult($offset);

        foreach ($sortClauses as $sortClause) {
            $column = sprintf('url.%s', $sortClause->target);
            $query->addOrderBy($column, $this->getQuerySortingDirection($sortClause->direction));
        }

        $statement = $query->executeQuery();

        return [
            'count' => $count,
            'rows' => $statement->fetchAllAssociative(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function findUsages($id): array
    {
        $query = $this->connection->createQueryBuilder();
        $expr = $query->expr();
        $query
            ->select('DISTINCT c.id')
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin(
                'c',
                ContentGateway::CONTENT_FIELD_TABLE,
                'f_def',
                $expr->and(
                    'c.id = f_def.contentobject_id',
                    'c.current_version = f_def.version'
                )
            )
            ->innerJoin(
                'f_def',
                self::URL_LINK_TABLE,
                'u_lnk',
                $expr->and(
                    'f_def.id = u_lnk.contentobject_attribute_id',
                    'f_def.version = u_lnk.contentobject_attribute_version'
                )
            )
            ->where(
                $expr->eq(
                    'u_lnk.url_id',
                    $query->createPositionalParameter($id, ParameterType::INTEGER)
                )
            );

        return $query->executeQuery()->fetchFirstColumn();
    }

    /**
     * {@inheritdoc}
     */
    public function updateUrl(URL $url)
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->update(self::URL_TABLE)
            ->set(
                self::COLUMN_URL,
                $query->createPositionalParameter($url->url, ParameterType::STRING)
            )->set(
                self::COLUMN_ORIGINAL_URL_MD5,
                $query->createPositionalParameter($url->originalUrlMd5, ParameterType::STRING)
            )
            ->set(
                self::COLUMN_MODIFIED,
                $query->createPositionalParameter($url->modified, ParameterType::INTEGER)
            )
            ->set(
                self::COLUMN_IS_VALID,
                $query->createPositionalParameter((int)$url->isValid, ParameterType::INTEGER)
            )
            ->set(
                self::COLUMN_LAST_CHECKED,
                $query->createPositionalParameter($url->lastChecked, ParameterType::INTEGER)
            )
            ->where(
                $query->expr()->eq(
                    self::COLUMN_ID,
                    $query->createPositionalParameter($url->id, ParameterType::INTEGER)
                )
            );

        $query->executeStatement();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUrlData($id): array
    {
        $query = $this->createSelectQuery();
        $query->where(
            $query->expr()->eq(
                self::COLUMN_ID,
                $query->createPositionalParameter($id, ParameterType::INTEGER)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * {@inheritdoc}
     */
    public function loadUrlDataByUrl($url): array
    {
        $query = $this->createSelectQuery();
        $query->where(
            $query->expr()->eq(
                self::COLUMN_URL,
                $query->createPositionalParameter($url, ParameterType::STRING)
            )
        );

        return $query->executeQuery()->fetchAllAssociative();
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     */
    protected function doCount(Criterion $criterion): int
    {
        $columnName = self::COLUMN_ID;

        $query = $this->connection->createQueryBuilder();
        $query
            ->select("COUNT(DISTINCT url.{$columnName})")
            ->from(self::URL_TABLE, 'url')
            ->where($this->criteriaConverter->convertCriteria($query, $criterion));

        return (int)$query->executeQuery()->fetchOne();
    }

    /**
     * Creates a Url find query.
     */
    protected function createSelectQuery(): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select($this->getSelectColumns())
            ->from(self::URL_TABLE, 'url');
    }

    private function createSelectDistinctQuery(): QueryBuilder
    {
        return $this->connection
            ->createQueryBuilder()
            ->select(sprintf('DISTINCT %s', implode(', ', $this->getSelectColumns())))
            ->from(self::URL_TABLE, 'url');
    }

    private function getSelectColumns(): array
    {
        return [
            sprintf('url.%s', self::COLUMN_ID),
            sprintf('url.%s', self::COLUMN_URL),
            sprintf('url.%s', self::COLUMN_ORIGINAL_URL_MD5),
            sprintf('url.%s', self::COLUMN_IS_VALID),
            sprintf('url.%s', self::COLUMN_LAST_CHECKED),
            sprintf('url.%s', self::COLUMN_CREATED),
            sprintf('url.%s', self::COLUMN_MODIFIED),
        ];
    }

    /**
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentException
     */
    private function getQuerySortingDirection(string $direction): string
    {
        if (!isset(self::SORT_DIRECTION_MAP[$direction])) {
            throw new InvalidArgumentException(
                '$sortClause->direction',
                sprintf(
                    'Unsupported "%s" sorting directions, use one of the SortClause::SORT_* constants instead',
                    $direction
                )
            );
        }

        return self::SORT_DIRECTION_MAP[$direction];
    }
}
