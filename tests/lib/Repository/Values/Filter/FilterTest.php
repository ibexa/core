<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\Repository\Values\Filter;

use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\SortClause as URLQuerySortClause;
use function md5;
use PHPUnit\Framework\TestCase;
use function sprintf;

/**
 * @covers \Ibexa\Contracts\Core\Repository\Values\Filter\Filter
 */
final class FilterTest extends TestCase
{
    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testConstructor(): void
    {
        $criterion = new Criterion\LogicalAnd(
            [new Criterion\ParentLocationId(1), new Criterion\RemoteId(md5('/1/2/3/'))]
        );
        $sortClauses = [
            new SortClause\Location\Priority(),
            new SortClause\ContentName(Query::SORT_DESC),
        ];
        $filter = new Filter($criterion, $sortClauses);
        self::assertEquals($criterion, $filter->getCriterion());
        self::assertEquals($sortClauses, $filter->getSortClauses());
    }

    /**
     * @dataProvider getInvalidSortClausesData
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testConstructorThrowsBadStateException(
        array $sortClauses,
        string $expectedExceptionMessage
    ): Filter {
        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        return new Filter(new Criterion\ParentLocationId(3), $sortClauses);
    }

    public function getInvalidSortClausesData(): iterable
    {
        yield [
            [
                new SortClause\Location\Priority(),
                1,
            ],
            'Expected an instance of "Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause", ' .
            'got "int" at position 1',
        ];

        yield [
            [
                new SortClause\Location\Depth(),
                new URLQuerySortClause\URL(Query::SORT_DESC),
                Query::SORT_ASC,
            ],
            'Expected an instance of "Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause", ' .
            'got "Ibexa\Contracts\Core\Repository\Values\URL\Query\SortClause\URL" at position 1',
        ];

        yield [
            [
                new SortClause\DatePublished(),
                new SortClause\SectionIdentifier(Query::SORT_DESC),
                Query::SORT_ASC,
                new class('', Query::SORT_DESC) extends URLQuerySortClause {
                },
            ],
            'Expected an instance of "Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause", ' .
            'got "string" at position 2',
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testWithCriterion(): Filter
    {
        $filter = new Filter();
        self::assertNull($filter->getCriterion());
        $criterion = new Criterion\ContentId(1);
        $filter->withCriterion($criterion);
        self::assertEquals($criterion, $filter->getCriterion());

        return $filter;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testWithCriterionThrowsBadStateException(): void
    {
        $filter = new Filter();
        $filter->withCriterion(new Criterion\ParentLocationId(2));

        $this->expectException(BadStateException::class);
        $filter->withCriterion(new Criterion\ContentId(2));
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testAndWithCriterion(): Filter
    {
        $criterion1 = new Criterion\ContentId(1);
        $criterion2 = new Criterion\RemoteId(md5('/1/2/3/'));
        $criterion3 = new Criterion\Ancestor('/1/2/');

        $filter = new Filter();
        $filter->withCriterion($criterion1);

        $filter->andWithCriterion($criterion2);
        $expectedCriterion = new Criterion\LogicalAnd([$criterion1, $criterion2]);
        self::assertEquals($expectedCriterion, $filter->getCriterion());

        $filter->andWithCriterion($criterion3);
        $expectedCriterion = new Criterion\LogicalAnd([$criterion1, $criterion2, $criterion3]);
        self::assertEquals($expectedCriterion, $filter->getCriterion());

        return $filter;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testOrWithCriterion(): Filter
    {
        // sanity check
        $criterion1 = new Criterion\ContentId(1);
        $criterion2 = new Criterion\RemoteId('1');
        $criterion3 = new Criterion\Ancestor('/1/2/');

        $filter = new Filter();
        $filter->withCriterion($criterion1);

        $filter->orWithCriterion($criterion2);
        $expectedCriterion = new Criterion\LogicalOr([$criterion1, $criterion2]);
        self::assertEquals($expectedCriterion, $filter->getCriterion());

        $filter->orWithCriterion($criterion3);
        $expectedCriterion = new Criterion\LogicalOr([$criterion1, $criterion2, $criterion3]);
        self::assertEquals($expectedCriterion, $filter->getCriterion());

        return $filter;
    }

    public function testWithSortClause(): Filter
    {
        $filter = new Filter();
        // sanity check
        self::assertSame([], $filter->getSortClauses());

        $sortClause1 = new SortClause\Location\Priority(Query::SORT_DESC);
        $filter->withSortClause($sortClause1);
        self::assertContainsEquals($sortClause1, $filter->getSortClauses());

        $sortClause2 = new SortClause\Location\Priority(Query::SORT_DESC);
        $filter->withSortClause($sortClause2);
        self::assertContainsEquals($sortClause2, $filter->getSortClauses());
        self::assertSame([$sortClause1, $sortClause2], $filter->getSortClauses());

        return $filter;
    }

    /**
     * @dataProvider getComplexFilterTestData
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause[] $expectedSortClauses
     */
    public function testBuildingComplexFilter(
        Filter $filter,
        ?Query\CriterionInterface $expectedCriterion,
        array $expectedSortClauses,
        int $expectedLimit = 0,
        int $expectedOffset = 0
    ): void {
        self::assertEquals($expectedCriterion, $filter->getCriterion());
        self::assertEquals($expectedSortClauses, $filter->getSortClauses());
        self::assertEquals($expectedOffset, $filter->getOffset());
        self::assertEquals($expectedLimit, $filter->getLimit());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function getComplexFilterTestData(): iterable
    {
        $parent1Criterion = new Criterion\ParentLocationId(1);
        $engGBCriterion = new Criterion\LanguageCode('eng-GB');
        $parent2Criterion = new Criterion\ParentLocationId(2);
        $gerDECriterion = new Criterion\LanguageCode('ger-DE');

        $criterion = new Criterion\LogicalOr(
            [
                new Criterion\LogicalAnd(
                    [
                        $parent1Criterion,
                        $engGBCriterion,
                    ]
                ),
                new Criterion\LogicalAnd(
                    [
                        $parent2Criterion,
                        $gerDECriterion,
                    ]
                ),
            ]
        );
        $sortClauses = [
            new SortClause\Location\Priority(),
            new SortClause\ContentName(Query::SORT_DESC),
        ];
        self::assertInstanceOf(FilteringCriterion::class, $criterion->criteria[0]);
        self::assertInstanceOf(FilteringCriterion::class, $criterion->criteria[1]);

        $filter = new Filter();
        $filter
            ->withCriterion($criterion->criteria[0])
            ->orWithCriterion($criterion->criteria[1])
            ->withSortClause($sortClauses[0])
            ->withSortClause($sortClauses[1]);

        yield '(parent=1 AND language=eng-GB) OR (parent=2 AND language=ger-DE)' => [
            $filter,
            $criterion,
            $sortClauses,
        ];

        $criterion = new Criterion\LogicalAnd(
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\LogicalAnd(
                            [
                                $parent1Criterion,
                                $engGBCriterion,
                            ]
                        ),
                        $parent2Criterion,
                    ]
                ),
                $gerDECriterion,
            ]
        );

        $filter = new Filter();
        $filter
            ->withCriterion($parent1Criterion)
            ->andWithCriterion($engGBCriterion)
            ->orWithCriterion($parent2Criterion)
            ->andWithCriterion($gerDECriterion)
            ->withSortClause($sortClauses[1]);

        yield '(parent=1 AND language=eng-GB OR parent=2) AND language=ger-DE' => [
            $filter,
            $criterion,
            [$sortClauses[1]],
        ];

        // pagination / slices support:

        $filter = new Filter();
        $filter->sliceBy(10, 0);

        yield 'sliceBy(limit=10, offset=0)' => [
            $filter,
            null,
            [],
            10,
            0,
        ];

        $filter = new Filter();
        $filter->sliceBy(25, 10);

        yield 'sliceBy(limit=25, offset=10)' => [
            $filter,
            null,
            [],
            25,
            10,
        ];

        // use case for offset with no limit: skip the latest item
        $dateTimeSortClause = new SortClause\DatePublished(Query::SORT_DESC);
        $filter = new Filter();
        $filter
            ->sliceBy(0, 1)
            ->withSortClause($dateTimeSortClause);

        yield 'sliceBy(limit=0, offset=1)' => [
            $filter,
            null,
            [$dateTimeSortClause],
            0,
            1,
        ];

        yield 'withLimit(limit=10)' => [
            (new Filter())->withLimit(10),
            null,
            [],
            10,
            0,
        ];

        yield 'withOffset(offset=10)' => [
            (new Filter())->withOffset(10),
            null,
            [],
            0,
            10,
        ];
    }

    /**
     * @dataProvider getFiltersWithInvalidSliceData
     */
    public function testSliceByThrowsInvalidArgumentException(
        int $limit,
        int $offset,
        string $expectedExceptionMessage
    ): void {
        $filter = new Filter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);

        $filter->sliceBy($limit, $offset);
    }

    public function getFiltersWithInvalidSliceData(): iterable
    {
        yield [-1, 0, 'Argument \'$limit\' is invalid: Filtering slice limit needs to be >=0, got -1'];
        yield [0, -1, 'Argument \'$offset\' is invalid: Filtering slice offset needs to be >=0, got -1'];
        yield [
            PHP_INT_MIN,
            PHP_INT_MIN,
            sprintf(
                'Argument \'$limit\' is invalid: Filtering slice limit needs to be >=0, got %d',
                PHP_INT_MIN
            ),
        ];
    }

    /**
     * @dataProvider getFilters
     */
    public function testReset(Filter $filter): void
    {
        $filter->reset();
        self::assertEmpty($filter->getCriterion());
        self::assertEmpty($filter->getSortClauses());
        self::assertSame(0, $filter->getOffset());
        self::assertSame(0, $filter->getLimit());
    }

    /**
     * @dataProvider getFilters
     */
    public function testClone(Filter $filter): void
    {
        $clonedFilter = clone $filter;

        self::assertEquals($filter->getCriterion(), $clonedFilter->getCriterion());
        self::assertEquals($filter->getSortClauses(), $clonedFilter->getSortClauses());

        if (null !== ($expectedCriterion = $filter->getCriterion())) {
            self::assertNotSame($expectedCriterion, $clonedFilter->getCriterion());
        }
        if ([] !== ($expectedSortClauses = $filter->getSortClauses())) {
            self::assertNotSame($expectedSortClauses, $clonedFilter->getSortClauses());
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function getFilters(): iterable
    {
        $criterion = new Criterion\LogicalAnd(
            [
                new Criterion\ParentLocationId(1),
            ]
        );

        yield 'Filter with Criterion and Sort Clauses' => [
            new Filter(
                $criterion,
                [
                    new SortClause\Location\Priority(),
                    new SortClause\ContentName(Query::SORT_DESC),
                ]
            ),
        ];

        yield 'Filter with Criterion only' => [new Filter($criterion)];

        yield 'Filter with Sort Clause only' => [new Filter(null, [new SortClause\ContentName()])];

        yield 'Empty Filter' => [new Filter()];
    }
}
