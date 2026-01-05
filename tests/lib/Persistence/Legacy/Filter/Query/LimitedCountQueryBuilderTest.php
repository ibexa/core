<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Persistence\Legacy\Filter\Query;

use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Persistence\Legacy\Filter\Query\LimitedCountQueryBuilder;
use Ibexa\Tests\Core\Persistence\Legacy\TestCase;

/**
 * @covers \Ibexa\Core\Persistence\Legacy\Filter\Query\LimitedCountQueryBuilder
 */
final class LimitedCountQueryBuilderTest extends TestCase
{
    private LimitedCountQueryBuilder $limitedCountQueryBuilder;

    protected function setUp(): void
    {
        $this->limitedCountQueryBuilder = new LimitedCountQueryBuilder($this->getDatabaseConnection());
    }

    public function testWrapThrowsExceptionOnZeroLimit(): void
    {
        $qb = $this->getDatabaseConnection()->createQueryBuilder();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Limit must be greater than 0/');

        $this->limitedCountQueryBuilder->wrap($qb, 'someField', 0);
    }

    /**
     * @covers \Ibexa\Core\Persistence\Legacy\Filter\Query\LimitedCountQueryBuilder::wrap
     */
    public function testWrapDoesNotChangeQueryBuilderIfLimitIsNull(): void
    {
        $qb = $this->getDatabaseConnection()->createQueryBuilder();
        $qb->select('DISTINCT someField')
            ->from('someTable')
            ->where('someCondition = :condition')
            ->setParameter('condition', 'value');

        $wrappedQueryBuilder = $this->limitedCountQueryBuilder->wrap($qb, 'someField', null);

        // The original query should remain unchanged
        self::assertEquals($qb->getSQL(), $wrappedQueryBuilder->getSQL());
    }

    public function testWrapWrapsQueryBuilderCorrectly(): void
    {
        $qb = $this->getDatabaseConnection()->createQueryBuilder();
        $qb->select('DISTINCT someField')
            ->from('someTable')
            ->where('someCondition = :condition')
            ->setParameter('condition', 'value');

        $wrappedQueryBuilder = $this->limitedCountQueryBuilder->wrap($qb, 'someField', 10);

        $expectedSql = 'SELECT COUNT(1) FROM (SELECT someField FROM someTable WHERE someCondition = :condition LIMIT 10) csub';
        self::assertEquals($expectedSql, $wrappedQueryBuilder->getSQL());
        self::assertEquals($qb->getParameters(), $wrappedQueryBuilder->getParameters());
    }
}
