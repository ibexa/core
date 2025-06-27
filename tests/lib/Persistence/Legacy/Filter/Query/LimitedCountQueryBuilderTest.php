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
class LimitedCountQueryBuilderTest extends TestCase
{
    private LimitedCountQueryBuilder $limitedCountQueryBuilder;

    protected function setUp(): void
    {
        $this->limitedCountQueryBuilder = new LimitedCountQueryBuilder($this->getDatabaseConnection());
    }

    /**
     * @covers \Ibexa\Core\Persistence\Legacy\Filter\Query\LimitedCountQueryBuilder::wrap
     */
    public function testWrapThrowsExceptionOnZeroLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Limit must be greater than 0/');

        $qb = $this->getDatabaseConnection()->createQueryBuilder();

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
        $this->assertEquals($qb->getSQL(), $wrappedQueryBuilder->getSQL());
    }

    /**
     * @covers \Ibexa\Core\Persistence\Legacy\Filter\Query\LimitedCountQueryBuilder::wrap
     */
    public function testWrapWrapsQueryBuilderCorrectly(): void
    {
        $qb = $this->getDatabaseConnection()->createQueryBuilder();
        $qb->select('DISTINCT someField')
            ->from('someTable')
            ->where('someCondition = :condition')
            ->setParameter('condition', 'value');

        $wrappedQueryBuilder = $this->limitedCountQueryBuilder->wrap($qb, 'someField', 10);

        $expectedSql = 'SELECT COUNT(*) FROM (SELECT someField FROM someTable WHERE someCondition = :condition LIMIT 10) csub';
        $this->assertEquals($expectedSql, $wrappedQueryBuilder->getSQL());
    }
}
