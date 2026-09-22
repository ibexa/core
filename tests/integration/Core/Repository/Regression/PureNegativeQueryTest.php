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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * This test will try to execute search queries that might be interpreted as "pure negative"
 * by the search backend and hence produce incorrect results.
 */
#[Group('regression')]
class PureNegativeQueryTest extends BaseTestCase
{
    /**
     * @return array<mixed>
     */
    public static function providerForTestMatchAll(): array
    {
        $query = new Query(['filter' => new Criterion\MatchAll()]);
        $result = static::resolveRepository()->getSearchService()->findContent($query);
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    #[DataProvider('providerForTestMatchAll')]
    public function testMatchAllContentInfoQuery($criterion, $totalCount): void
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    #[DataProvider('providerForTestMatchAll')]
    public function testMatchAllContentInfoFilter($criterion, $totalCount): void
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    #[DataProvider('providerForTestMatchAll')]
    public function testMatchAllLocationQuery($criterion, $totalCount): void
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
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion $criterion
     * @param int $totalCount
     */
    #[DataProvider('providerForTestMatchAll')]
    public function testMatchAllLocationFilter($criterion, $totalCount): void
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
