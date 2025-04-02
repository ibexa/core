<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Search\Legacy\Content;

use DateTimeInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Generator;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Search\Content\IndexerGateway as SPIIndexerGateway;

/**
 * @internal
 */
final class IndexerGateway implements SPIIndexerGateway
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getContentSince(DateTimeInterface $since, int $iterationCount): Generator
    {
        $query = $this->buildQueryForContentSince($since);
        $query->orderBy('c.modified');

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countContentSince(DateTimeInterface $since): int
    {
        $query = $this->buildQueryForContentSince($since, true);

        return (int)$query->executeQuery()->fetchOne();
    }

    public function getContentInSubtree(string $locationPath, int $iterationCount): Generator
    {
        $query = $this->buildQueryForContentInSubtree($locationPath);

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countContentInSubtree(string $locationPath): int
    {
        $query = $this->buildQueryForContentInSubtree($locationPath, true);

        return (int)$query->executeQuery()->fetchOne();
    }

    public function getAllContent(int $iterationCount): Generator
    {
        $query = $this->buildQueryForAllContent();

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countAllContent(): int
    {
        $query = $this->buildQueryForAllContent(true);

        return (int)$query->executeQuery()->fetchOne();
    }

    private function buildQueryForContentSince(DateTimeInterface $since, bool $count = false): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select($count ? 'COUNT(c.id)' : 'c.id')
            ->from('ezcontentobject', 'c')
            ->where('c.status = :status')->andWhere('c.modified >= :since')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter('since', $since->getTimestamp(), ParameterType::INTEGER);
    }

    private function buildQueryForContentInSubtree(string $locationPath, bool $count = false): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select($count ? 'COUNT(DISTINCT c.id)' : 'DISTINCT c.id')
            ->from('ezcontentobject', 'c')
            ->innerJoin('c', 'ezcontentobject_tree', 't', 't.contentobject_id = c.id')
            ->where('c.status = :status')
            ->andWhere('t.path_string LIKE :path')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter('path', $locationPath . '%', ParameterType::STRING);
    }

    private function buildQueryForAllContent(bool $count = false): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select($count ? 'COUNT(c.id)' : 'c.id')
            ->from('ezcontentobject', 'c')
            ->where('c.status = :status')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function fetchIteration(Result $statement, int $iterationCount): Generator
    {
        do {
            $contentIds = [];
            for ($i = 0; $i < $iterationCount; ++$i) {
                if (false !== ($contentId = $statement->fetchOne())) {
                    $contentIds[] = $contentId;
                } elseif (empty($contentIds)) {
                    return;
                } else {
                    break;
                }
            }

            yield $contentIds;
        } while (!empty($contentId));
    }
}
