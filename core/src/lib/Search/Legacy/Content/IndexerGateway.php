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
use Ibexa\Core\Persistence\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway as LocationGateway;

/**
 * @internal
 */
final readonly class IndexerGateway implements SPIIndexerGateway
{
    public function __construct(private Connection $connection)
    {
    }

    public function getContentSince(DateTimeInterface $since, int $iterationCount): Generator
    {
        $query = $this->buildQueryForContentSince($since);
        $query->orderBy('c.modified');

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countContentSince(DateTimeInterface $since): int
    {
        $query = $this->buildCountingQuery(
            $this->buildQueryForContentSince($since)
        );

        return (int)$query->executeQuery()->fetchOne();
    }

    public function getContentInSubtree(string $locationPath, int $iterationCount): Generator
    {
        $query = $this->buildQueryForContentInSubtree($locationPath);

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countContentInSubtree(string $locationPath): int
    {
        $query = $this->buildCountingQuery(
            $this->buildQueryForContentInSubtree($locationPath)
        );

        return (int)$query->executeQuery()->fetchOne();
    }

    public function getAllContent(int $iterationCount): Generator
    {
        $query = $this->buildQueryForAllContent();

        yield from $this->fetchIteration($query->executeQuery(), $iterationCount);
    }

    public function countAllContent(): int
    {
        $query = $this->buildCountingQuery(
            $this->buildQueryForAllContent()
        );

        return (int)$query->executeQuery()->fetchOne();
    }

    private function buildQueryForContentSince(DateTimeInterface $since): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('c.id')
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->where('c.status = :status')->andWhere('c.modified >= :since')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter('since', $since->getTimestamp(), ParameterType::INTEGER);
    }

    private function buildQueryForContentInSubtree(string $locationPath): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('DISTINCT c.id')
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->innerJoin('c', LocationGateway::CONTENT_TREE_TABLE, 't', 't.contentobject_id = c.id')
            ->where('c.status = :status')
            ->andWhere('t.path_string LIKE :path')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER)
            ->setParameter('path', $locationPath . '%', ParameterType::STRING);
    }

    private function buildQueryForAllContent(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select('c.id')
            ->from(ContentGateway::CONTENT_ITEM_TABLE, 'c')
            ->where('c.status = :status')
            ->setParameter('status', ContentInfo::STATUS_PUBLISHED, ParameterType::INTEGER);
    }

    private function buildCountingQuery(QueryBuilder $query): QueryBuilder
    {
        $countQuery = clone $query;
        $countQuery->select('COUNT(*)');

        return $countQuery;
    }

    private function fetchIteration(Result $result, int $iterationCount): Generator
    {
        do {
            $contentIds = [];
            for ($i = 0; $i < $iterationCount; ++$i) {
                if ($contentId = $result->fetchOne()) {
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
