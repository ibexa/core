<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Search\Legacy\Content;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriteriaConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler as CommonCriterionHandler;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseConverter;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\SortClauseHandler as CommonSortClauseHandler;
use Ibexa\Core\Search\Legacy\Content\Gateway as ContentGateway;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway\CriterionHandler as LocationCriterionHandler;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway\SortClauseHandler as LocationSortClauseHandler;

/**
 * Location Search test case for ContentSearchHandler.
 */
class HandlerLocationSortTest extends AbstractTestCase
{
    protected function getIds($searchResult)
    {
        $ids = array_map(
            static function ($hit) {
                return $hit->valueObject->id;
            },
            $searchResult->searchHits
        );

        return $ids;
    }

    /**
     * Returns the location search handler to test.
     *
     * This method returns a fully functional search handler to perform tests on.
     *
     * @return \Ibexa\Core\Search\Legacy\Content\Handler
     */
    protected function getContentSearchHandler()
    {
        $connection = $this->getDatabaseConnection();

        return new Content\Handler(
            $this->createMock(ContentGateway::class),
            new Content\Location\Gateway\DoctrineDatabase(
                $connection,
                new CriteriaConverter(
                    [
                        new LocationCriterionHandler\LocationId($connection),
                        new LocationCriterionHandler\ParentLocationId($connection),
                        new CommonCriterionHandler\LogicalAnd($connection),
                        new CommonCriterionHandler\MatchAll($connection),
                        new CommonCriterionHandler\SectionId($connection),
                        new CommonCriterionHandler\ContentTypeIdentifier(
                            $connection,
                            $this->getContentTypeHandler()
                        ),
                    ]
                ),
                new SortClauseConverter(
                    [
                        new LocationSortClauseHandler\Location\Id($connection),
                        new LocationSortClauseHandler\Location\Depth($connection),
                        new LocationSortClauseHandler\Location\Path($connection),
                        new LocationSortClauseHandler\Location\Priority($connection),
                        new LocationSortClauseHandler\Location\Visibility($connection),
                        new LocationSortClauseHandler\Location\IsMainLocation($connection),
                        new CommonSortClauseHandler\ContentId($connection),
                        new CommonSortClauseHandler\ContentName($connection),
                        new CommonSortClauseHandler\DateModified($connection),
                        new CommonSortClauseHandler\DatePublished($connection),
                        new CommonSortClauseHandler\SectionIdentifier($connection),
                        new CommonSortClauseHandler\SectionName($connection),
                        new CommonSortClauseHandler\Field(
                            $connection,
                            $this->getLanguageHandler(),
                            $this->getContentTypeHandler()
                        ),
                    ]
                ),
                $this->getLanguageHandler()
            ),
            new Content\WordIndexer\Gateway\DoctrineDatabase(
                $this->getDatabaseConnection(),
                $this->getContentTypeHandler(),
                $this->getDefinitionBasedTransformationProcessor(),
                new Content\WordIndexer\Repository\SearchIndex($this->getDatabaseConnection()),
                $this->getLanguageMaskGenerator(),
                $this->getFullTextSearchConfiguration()
            ),
            $this->createMock(ContentMapper::class),
            $this->getLocationMapperMock(),
            $this->getLanguageHandler(),
            $this->getFullTextMapper($this->getContentTypeHandler())
        );
    }

    /**
     * Returns a location mapper mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Location\Mapper
     */
    protected function getLocationMapperMock()
    {
        $mapperMock = $this->getMockBuilder(LocationMapper::class)
            ->setMethods(['createLocationsFromRows'])
            ->getMock();
        $mapperMock
            ->expects(self::any())
            ->method('createLocationsFromRows')
            ->with(self::isType('array'))
            ->will(
                self::returnCallback(
                    static function ($rows): array {
                        $locations = [];
                        foreach ($rows as $row) {
                            $locationId = (int)$row['node_id'];
                            if (!isset($locations[$locationId])) {
                                $locations[$locationId] = new SPILocation();
                                $locations[$locationId]->id = $locationId;
                            }
                        }

                        return array_values($locations);
                    }
                )
            );

        return $mapperMock;
    }

    public function testNoSorting()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\ParentLocationId([178]),
                    'offset' => 0,
                    'limit' => 5,
                    'sortClauses' => [],
                ]
            )
        );

        $ids = $this->getIds($locations);
        self::assertEqualsCanonicalizing(
            [179, 180, 181, 182, 183],
            $ids
        );
    }

    public function testSortLocationPath()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\ParentLocationId([178]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [new SortClause\Location\Path(LocationQuery::SORT_DESC)],
                ]
            )
        );

        $this->assertSearchResults(
            [186, 185, 184, 183, 182, 181, 180, 179],
            $locations
        );
    }

    public function testSortLocationDepth()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([148, 167, 169, 172]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [new SortClause\Location\Depth(LocationQuery::SORT_ASC)],
                ]
            )
        );

        $this->assertSearchResults(
            [167, 172, 169, 148],
            $locations
        );
    }

    public function testSortLocationDepthAndPath()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([141, 142, 143, 144, 146, 147]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\Location\Depth(LocationQuery::SORT_ASC),
                        new SortClause\Location\Path(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [147, 146, 141, 144, 143, 142],
            $locations
        );
    }

    public function testSortLocationPriority()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([149, 156, 167]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\Location\Priority(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [167, 156, 149],
            $locations
        );
    }

    public function testSortDateModified()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([148, 167, 169, 172]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\DateModified(),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [169, 172, 167, 148],
            $locations
        );
    }

    public function testSortDatePublished()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([148, 167, 169, 172]),
                    'offset' => 0,
                    'limit' => 10,
                    'sortClauses' => [
                        new SortClause\DatePublished(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [148, 172, 169, 167],
            $locations
        );
    }

    public function testSortSectionIdentifier()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId(
                        [5, 43, 45, 48, 51, 54, 156, 157]
                    ),
                    'offset' => 0,
                    'limit' => 8,
                    'sortClauses' => [
                        new SortClause\SectionIdentifier(),
                    ],
                ]
            )
        );

        // First, results of section 2 should appear, then the ones of 3, 4 and 6
        // From inside a specific section, no particular order should be defined
        // the logic is then to have a set of sorted id's to compare with
        // the comparison being done slice by slice.
        $idMapSet = [
            2 => [5, 45],
            3 => [43, 51],
            4 => [48, 54],
            6 => [156, 157],
        ];
        $locationIds = $this->getIds($locations);
        $index = 0;

        foreach ($idMapSet as $idSet) {
            $locationIdsSubset = array_slice($locationIds, $index, $count = count($idSet));
            $index += $count;
            self::assertEqualsCanonicalizing(
                $idSet,
                $locationIdsSubset
            );
        }
    }

    public function testSortContentName()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([13, 15, 44, 45, 228]),
                    'offset' => 0,
                    'limit' => 5,
                    'sortClauses' => [
                        new SortClause\ContentName(),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [228, 15, 13, 45, 44],
            $locations
        );
    }

    public function testSortContentId()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([13, 15, 44, 45, 228]),
                    'offset' => 0,
                    'limit' => 5,
                    'sortClauses' => [
                        new SortClause\ContentId(),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [45, 13, 15, 44, 228],
            $locations
        );
    }

    public function testSortLocationId()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([13, 15, 44, 45, 228]),
                    'offset' => 0,
                    'limit' => 5,
                    'sortClauses' => [
                        new SortClause\Location\Id(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [228, 45, 44, 15, 13],
            $locations
        );
    }

    public function testSortLocationVisibilityAscending()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([45, 228]),
                    'offset' => 0,
                    'limit' => 2,
                    'sortClauses' => [
                        new SortClause\Location\Visibility(LocationQuery::SORT_ASC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [45, 228],
            $locations
        );
    }

    public function testSortLocationVisibilityDescending()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId([45, 228]),
                    'offset' => 0,
                    'limit' => 2,
                    'sortClauses' => [
                        new SortClause\Location\Visibility(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [228, 45],
            $locations
        );
    }

    public function testSortSectionName()
    {
        $handler = $this->getContentSearchHandler();

        $result = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\SectionId([4, 2, 6, 3]),
                    'offset' => 0,
                    'limit' => 999,
                    'sortClauses' => [
                        new SortClause\SectionName(),
                    ],
                ]
            )
        );

        // First, results of section "Media" should appear, then the ones of "Protected",
        // "Setup" and "Users"
        // From inside a specific section, no particular order should be defined
        // the logic is then to have a set of sorted id's to compare with
        // the comparison being done slice by slice.
        $idMapSet = [
            'media' => [43, 51, 52, 53, 59, 60, 61, 62, 63, 64, 65, 66, 68, 202, 203],
            'protected' => [156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166],
            'setup' => [48, 54],
            'users' => [5, 12, 13, 14, 15, 44, 45, 228],
        ];
        $locationIds = array_map(
            static function ($hit) {
                return $hit->valueObject->id;
            },
            $result->searchHits
        );

        $expectedCount = 0;
        foreach ($idMapSet as $set) {
            $expectedCount += count($set);
        }

        self::assertEquals($expectedCount, $result->totalCount);

        $index = 0;
        foreach ($idMapSet as $idSet) {
            $locationIdsSubset = array_slice($locationIds, $index, $count = count($idSet));
            $index += $count;
            self::assertEqualsCanonicalizing(
                $idSet,
                $locationIdsSubset
            );
        }
    }

    public function testSortFieldText()
    {
        $handler = $this->getContentSearchHandler();

        $result = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LogicalAnd(
                        [
                            new Criterion\SectionId([1]),
                            new Criterion\ContentTypeIdentifier(['article']),
                        ]
                    ),
                    'offset' => 0,
                    'limit' => 999,
                    'sortClauses' => [
                        new SortClause\Field('article', 'title', LocationQuery::SORT_ASC, 'eng-US'),
                    ],
                ]
            )
        );

        // There are several identical titles, need to take care about this
        $idMapSet = [
            'aenean malesuada ligula' => [85],
            'aliquam pulvinar suscipit tellus' => [104],
            'asynchronous publishing' => [150, 217],
            'canonical links' => [149, 218],
            'class aptent taciti' => [90],
            'class aptent taciti sociosqu' => [84],
            'duis auctor vehicula erat' => [91],
            'etiam posuere sodales arcu' => [80],
            'etiam sodales mauris' => [89],
            'ibexa dxp' => [146],
            'fastcgi' => [79, 220],
            'fusce sagittis sagittis' => [83],
            'fusce sagittis sagittis urna' => [109],
            'get involved' => [129],
            'how to develop with ibexa' => [120, 213],
            'how to manage ibexa' => [110, 204],
            'how to use ibexa' => [153, 195],
            'improved block editing' => [138],
            'improved front-end editing' => [141],
            'improved user registration workflow' => [134],
            'in hac habitasse platea' => [81],
            'lots of websites, one ibexa installation' => [132],
            'rest api interface' => [152, 216],
            'separate content & design in ibexa' => [193],
            'support for red hat enterprise' => [147, 219],
            'tutorials for' => [108],
        ];
        $locationIds = array_map(
            static function ($hit) {
                return $hit->valueObject->id;
            },
            $result->searchHits
        );
        $index = 0;

        foreach ($idMapSet as $idSet) {
            $locationIdsSubset = array_slice($locationIds, $index, $count = count($idSet));
            $index += $count;
            self::assertEqualsCanonicalizing(
                $idSet,
                $locationIdsSubset
            );
        }
    }

    public function testSortFieldNumeric()
    {
        self::markTestSkipped();
        $handler = $this->getContentSearchHandler();

        $result = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LogicalAnd(
                        [
                            new Criterion\SectionId([1]),
                            new Criterion\ContentTypeIdentifier('product'),
                        ]
                    ),
                    'offset' => 0,
                    'limit' => 4,
                    'sortClauses' => [
                        new SortClause\Field('product', 'price', LocationQuery::SORT_ASC, 'eng-US'),
                    ],
                ]
            )
        );

        self::assertEquals(
            [75, 73, 74, 71],
            array_map(
                static function ($hit) {
                    return $hit->valueObject->id;
                },
                $result->searchHits
            )
        );
    }

    public function testSortIsMainLocationAscending()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\ParentLocationId(224),
                    'offset' => 0,
                    'limit' => 2,
                    'sortClauses' => [
                        new SortClause\Location\IsMainLocation(LocationQuery::SORT_ASC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [510, 225],
            $locations
        );
    }

    public function testSortIsMainLocationDescending()
    {
        $handler = $this->getContentSearchHandler();

        $locations = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\ParentLocationId(224),
                    'offset' => 0,
                    'limit' => 2,
                    'sortClauses' => [
                        new SortClause\Location\IsMainLocation(LocationQuery::SORT_DESC),
                    ],
                ]
            )
        );

        $this->assertSearchResults(
            [225, 510],
            $locations
        );
    }
}
