<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Ibexa\Core\Persistence\Doctrine\JoinedTablesTracker;
use PHPUnit\Framework\TestCase;

final class JoinedTablesTrackerTest extends TestCase
{
    private JoinedTablesTracker $tracker;

    protected function setUp(): void
    {
        $this->tracker = new JoinedTablesTracker();
    }

    public function testMarkTableAsJoinedReturnsTrueOnFirstCall(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        self::assertTrue($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_field'));
    }

    public function testMarkTableAsJoinedReturnsFalseOnRepeatedCallForSameQueryBuilder(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        self::assertTrue($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_field'));
        self::assertFalse($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_field'));
    }

    /**
     * A single shared tracker instance (as wired via DI for all Search/URL criterion handlers)
     * must not let joins made for one QueryBuilder leak into an unrelated QueryBuilder, even
     * when both use the exact same table identifier -- e.g. one query built for a Repository
     * Filtering request and another for a legacy Search API request in the same PHP process.
     */
    public function testSameTableIdentifierIsTrackedIndependentlyPerQueryBuilder(): void
    {
        $filteringLikeQueryBuilder = $this->createQueryBuilder();
        $searchLikeQueryBuilder = $this->createQueryBuilder();

        self::assertTrue($this->tracker->markTableAsJoined($filteringLikeQueryBuilder, 'ibexa_content_field'));
        self::assertFalse($this->tracker->markTableAsJoined($filteringLikeQueryBuilder, 'ibexa_content_field'));

        // the second, unrelated QueryBuilder must still need to perform its own join
        self::assertTrue($this->tracker->markTableAsJoined($searchLikeQueryBuilder, 'ibexa_content_field'));
        self::assertFalse($this->tracker->markTableAsJoined($searchLikeQueryBuilder, 'ibexa_content_field'));
    }

    public function testDifferentTableIdentifiersOnSameQueryBuilderAreTrackedSeparately(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        self::assertTrue($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_field'));
        self::assertTrue($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_version'));
        self::assertFalse($this->tracker->markTableAsJoined($queryBuilder, 'ibexa_content_field'));
    }

    private function createQueryBuilder(): QueryBuilder
    {
        return new QueryBuilder($this->createMock(Connection::class));
    }
}
