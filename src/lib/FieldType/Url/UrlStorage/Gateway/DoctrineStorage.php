<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\FieldType\Url\UrlStorage\Gateway;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ibexa\Core\FieldType\Url\UrlStorage\Gateway;
use Ibexa\Core\Persistence\Legacy\URL\Gateway\DoctrineDatabase;
use PDO;

class DoctrineStorage extends Gateway
{
    public const URL_TABLE = DoctrineDatabase::URL_TABLE;
    public const URL_LINK_TABLE = DoctrineDatabase::URL_LINK_TABLE;

    /** @var \Doctrine\DBAL\Connection */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return a list of URLs for a list of URL ids.
     *
     * Non-existent ids are ignored.
     *
     * @param int[] $ids An array of URL ids
     *
     * @return array An array of URLs, with ids as keys
     */
    public function getIdUrlMap(array $ids)
    {
        $map = [];

        if (!empty($ids)) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->select(
                    $this->connection->quoteIdentifier('id'),
                    $this->connection->quoteIdentifier('url')
                )
                ->from(self::URL_TABLE)
                ->where('id IN (:ids)')
                ->setParameter('ids', $ids, Connection::PARAM_INT_ARRAY);

            $statement = $query->executeQuery();
            foreach ($statement->fetchAllAssociative() as $row) {
                $map[$row['id']] = $row['url'];
            }
        }

        return $map;
    }

    /**
     * Return a list of URL ids for a list of URLs.
     *
     * Non-existent URLs are ignored.
     *
     * @param string[] $urls An array of URLs
     *
     * @return array An array of URL ids, with URLs as keys
     */
    public function getUrlIdMap(array $urls)
    {
        $map = [];

        if (!empty($urls)) {
            $query = $this->connection->createQueryBuilder();
            $query
                ->select(
                    $this->connection->quoteIdentifier('id'),
                    $this->connection->quoteIdentifier('url')
                )
                ->from(self::URL_TABLE)
                ->where(
                    $query->expr()->in('url', ':urls')
                )
                ->setParameter('urls', $urls, Connection::PARAM_STR_ARRAY);

            $statement = $query->executeQuery();
            foreach ($statement->fetchAllAssociative() as $row) {
                $map[$row['url']] = $row['id'];
            }
        }

        return $map;
    }

    /**
     * Insert a new $url and returns its id.
     *
     * @param string $url The URL to insert in the database
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public function insertUrl($url): int
    {
        $time = time();

        $query = $this->connection->createQueryBuilder();

        $query
            ->insert($this->connection->quoteIdentifier(self::URL_TABLE))
            ->values(
                [
                    'created' => ':created',
                    'modified' => ':modified',
                    'original_url_md5' => ':original_url_md5',
                    'url' => ':url',
                ]
            )
            ->setParameter('created', $time, PDO::PARAM_INT)
            ->setParameter('modified', $time, PDO::PARAM_INT)
            ->setParameter('original_url_md5', md5($url))
            ->setParameter('url', $url)
        ;

        $query->executeStatement();

        return (int)$this->connection->lastInsertId(
            $this->getSequenceName(self::URL_TABLE, 'id')
        );
    }

    /**
     * Create link to URL with $urlId for field with $fieldId in $versionNo.
     *
     * @param int $urlId
     * @param int $fieldId
     * @param int $versionNo
     */
    public function linkUrl($urlId, $fieldId, $versionNo)
    {
        $query = $this->connection->createQueryBuilder();

        $query
            ->insert($this->connection->quoteIdentifier(self::URL_LINK_TABLE))
            ->values(
                [
                    'contentobject_attribute_id' => ':contentobject_attribute_id',
                    'contentobject_attribute_version' => ':contentobject_attribute_version',
                    'url_id' => ':url_id',
                ]
            )
            ->setParameter('contentobject_attribute_id', $fieldId, PDO::PARAM_INT)
            ->setParameter('contentobject_attribute_version', $versionNo, PDO::PARAM_INT)
            ->setParameter('url_id', $urlId, PDO::PARAM_INT)
        ;

        $query->executeStatement();
    }

    /**
     * Remove link to URL for $fieldId in $versionNo and cleans up possibly orphaned URLs.
     *
     * @param int $fieldId
     * @param int $versionNo
     * @param int[] $excludeUrlIds
     */
    public function unlinkUrl($fieldId, $versionNo, array $excludeUrlIds = []): void
    {
        $selectQuery = $this->connection->createQueryBuilder();
        $selectQuery
            ->select('link.url_id')
            ->from($this->connection->quoteIdentifier(self::URL_LINK_TABLE), 'link')
            ->where(
                $selectQuery->expr()->and(
                    $selectQuery->expr()->in(
                        'link.contentobject_attribute_id',
                        ':contentobject_attribute_id'
                    ),
                    $selectQuery->expr()->in(
                        'link.contentobject_attribute_version',
                        ':contentobject_attribute_version'
                    )
                )
            )
            ->setParameter('contentobject_attribute_id', $fieldId, ParameterType::INTEGER)
            ->setParameter('contentobject_attribute_version', $versionNo, ParameterType::INTEGER);

        $statement = $selectQuery->executeQuery();
        $potentiallyOrphanedUrls = $statement->fetchFirstColumn();

        if (empty($potentiallyOrphanedUrls)) {
            return;
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::URL_LINK_TABLE))
            ->where(
                $deleteQuery->expr()->and(
                    $deleteQuery->expr()->in(
                        'contentobject_attribute_id',
                        ':contentobject_attribute_id'
                    ),
                    $deleteQuery->expr()->in(
                        'contentobject_attribute_version',
                        ':contentobject_attribute_version'
                    )
                )
            )
            ->setParameter('contentobject_attribute_id', $fieldId, ParameterType::INTEGER)
            ->setParameter('contentobject_attribute_version', $versionNo, ParameterType::INTEGER);

        if (empty($excludeUrlIds) === false) {
            $deleteQuery
                ->andWhere(
                    $deleteQuery->expr()->notIn(
                        'url_id',
                        ':url_ids'
                    )
                )
                ->setParameter('url_ids', $excludeUrlIds, Connection::PARAM_INT_ARRAY);
        }

        $deleteQuery->executeStatement();

        $this->deleteOrphanedUrls($potentiallyOrphanedUrls);
    }

    /**
     * Delete potentially orphaned URLs.
     *
     * That could be avoided if the feature is implemented there.
     *
     * URL is orphaned if it is not linked to a content attribute through ibexa_url_content_link table.
     *
     * @param int[] $potentiallyOrphanedUrls
     */
    private function deleteOrphanedUrls(array $potentiallyOrphanedUrls): void
    {
        $query = $this->connection->createQueryBuilder();
        $query
            ->select($this->connection->quoteIdentifier('url.id'))
            ->from($this->connection->quoteIdentifier(self::URL_TABLE), 'url')
            ->leftJoin(
                'url',
                $this->connection->quoteIdentifier(self::URL_LINK_TABLE),
                'link',
                'url.id = link.url_id'
            )
            ->where(
                $query->expr()->in(
                    'url.id',
                    ':url_ids'
                )
            )
            ->andWhere($query->expr()->isNull('link.url_id'))
            ->setParameter('url_ids', $potentiallyOrphanedUrls, Connection::PARAM_INT_ARRAY)
        ;

        $statement = $query->executeQuery();

        $ids = $statement->fetchFirstColumn();
        if (empty($ids)) {
            return;
        }

        $deleteQuery = $this->connection->createQueryBuilder();
        $deleteQuery
            ->delete($this->connection->quoteIdentifier(self::URL_TABLE))
            ->where($deleteQuery->expr()->in('id', ':ids'))
            ->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY)
        ;

        $deleteQuery->executeStatement();
    }
}
