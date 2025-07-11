<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository\Regression;

use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;

/**
 * This test will try to execute search queries that might be interpreted as "pure negative"
 * by the search backend and hence produce incorrect results.
 *
 * @group regression
 */
class PureNegativeQueryTest extends BaseTestCase
{
    public function providerForTestMatchAll()
    {
        $query = new Query(['filter' => new Criterion\MatchAll()]);
        $result = $this->getRepository()->getSearchService()->findContent($query);
        // Sanity check
        self::assertGreaterThan(0, $result->totalCount);
        $totalCount = $result->totalCount;
        $contentId = 12;

        return [
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\MatchNone(),
                    ]
                ),
                1,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\MatchNone(),
                    ]
                ),
                0,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\MatchAll()
                        ),
                    ]
                ),
                1,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\MatchAll()
                        ),
                    ]
                ),
                0,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\MatchAll(),
                    ]
                ),
                $totalCount,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\MatchAll(),
                    ]
                ),
                1,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\MatchAll(),
                        new Criterion\MatchNone(),
                    ]
                ),
                $totalCount,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\MatchAll(),
                        new Criterion\MatchNone(),
                    ]
                ),
                0,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                    ]
                ),
                $totalCount,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\LogicalNot(
                                new Criterion\ContentId($contentId)
                            )
                        ),
                    ]
                ),
                1,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                        new Criterion\LogicalNot(
                            new Criterion\LogicalNot(
                                new Criterion\ContentId($contentId)
                            )
                        ),
                    ]
                ),
                $totalCount,
            ],
            [
                new Criterion\LogicalOr(
                    [
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                    ]
                ),
                $totalCount - 1,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                    ]
                ),
                0,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentId($contentId),
                        new Criterion\LogicalNot(
                            new Criterion\LogicalNot(
                                new Criterion\ContentId($contentId)
                            )
                        ),
                    ]
                ),
                1,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                        new Criterion\LogicalNot(
                            new Criterion\LogicalNot(
                                new Criterion\ContentId($contentId)
                            )
                        ),
                    ]
                ),
                0,
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                        new Criterion\LogicalNot(
                            new Criterion\ContentId($contentId)
                        ),
                    ]
                ),
                $totalCount - 1,
            ],
        ];
    }

    /**
     * @dataProvider providerForTestMatchAll
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    public function testMatchAllContentInfoQuery($criterion, $totalCount)
    {
        $query = new Query(
            [
                'query' => $criterion,
            ]
        );

        $result = $this->getRepository()->getSearchService()->findContentInfo($query);

        self::assertEquals($totalCount, $result->totalCount);
    }

    /**
     * @dataProvider providerForTestMatchAll
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    public function testMatchAllContentInfoFilter($criterion, $totalCount)
    {
        $query = new Query(
            [
                'filter' => $criterion,
            ]
        );

        $result = $this->getRepository()->getSearchService()->findContentInfo($query);

        self::assertEquals($totalCount, $result->totalCount);
    }

    /**
     * @dataProvider providerForTestMatchAll
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    public function testMatchAllLocationQuery($criterion, $totalCount)
    {
        $query = new LocationQuery(
            [
                'query' => $criterion,
            ]
        );

        $result = $this->getRepository()->getSearchService()->findLocations($query);

        self::assertEquals($totalCount, $result->totalCount);
    }

    /**
     * @dataProvider providerForTestMatchAll
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    public function testMatchAllLocationFilter($criterion, $totalCount)
    {
        $query = new LocationQuery(
            [
                'filter' => $criterion,
            ]
        );

        $result = $this->getRepository()->getSearchService()->findLocations($query);

        self::assertEquals($totalCount, $result->totalCount);
    }
}
