<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Repository\Service\Mock;

use Exception;
use Ibexa\Contracts\Core\Persistence\Content\ContentInfo as SPIContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Search\Handler;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\Permission\PermissionCriterionResolver;
use Ibexa\Core\Repository\SearchService;
use Ibexa\Core\Search\Common\BackgroundIndexer;
use Ibexa\Core\Search\Common\BackgroundIndexer\NullIndexer;
use Ibexa\Tests\Core\Repository\Service\Mock\Base as BaseServiceMockTest;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Mock test case for Search service.
 */
class SearchTest extends BaseServiceMockTest
{
    protected $repositoryMock;

    protected $contentDomainMapperMock;

    protected $permissionsCriterionResolverMock;

    /**
     * Test for the __construct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::__construct
     */
    public function testConstructor()
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $contentDomainMapperMock = $this->getContentDomainMapperMock();
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $settings = ['teh setting'];

        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $contentDomainMapperMock,
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            $settings
        );
    }

    public function providerForFindContentValidatesLocationCriteriaAndSortClauses()
    {
        return [
            [
                new Query(['filter' => new Criterion\Location\Depth(Criterion\Operator::LT, 2)]),
                "Argument '\$query' is invalid: Location Criteria cannot be used in Content search",
            ],
            [
                new Query(['query' => new Criterion\Location\Depth(Criterion\Operator::LT, 2)]),
                "Argument '\$query' is invalid: Location Criteria cannot be used in Content search",
            ],
            [
                new Query(
                    [
                        'query' => new Criterion\LogicalAnd(
                            [
                                new Criterion\Location\Depth(Criterion\Operator::LT, 2),
                            ]
                        ),
                    ]
                ),
                "Argument '\$query' is invalid: Location Criteria cannot be used in Content search",
            ],
            [
                new Query(['sortClauses' => [new SortClause\Location\Id()]]),
                "Argument '\$query' is invalid: Location Sort Clauses cannot be used in Content search",
            ],
        ];
    }

    /**
     * @dataProvider providerForFindContentValidatesLocationCriteriaAndSortClauses
     */
    public function testFindContentValidatesLocationCriteriaAndSortClauses(
        $query,
        $exceptionMessage
    ) {
        $this->expectException(InvalidArgumentException::class);

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();

        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        try {
            $service->findContent($query);
        } catch (InvalidArgumentException $e) {
            self::assertEquals($exceptionMessage, $e->getMessage());
            throw $e;
        }

        self::fail('Expected exception was not thrown');
    }

    public function providerForFindSingleValidatesLocationCriteria()
    {
        return [
            [
                new Criterion\Location\Depth(Criterion\Operator::LT, 2),
                "Argument '\$filter' is invalid: Location Criteria cannot be used in Content search",
            ],
            [
                new Criterion\LogicalAnd(
                    [
                        new Criterion\Location\Depth(Criterion\Operator::LT, 2),
                    ]
                ),
                "Argument '\$filter' is invalid: Location Criteria cannot be used in Content search",
            ],
        ];
    }

    /**
     * @dataProvider providerForFindSingleValidatesLocationCriteria
     */
    public function testFindSingleValidatesLocationCriteria(
        $criterion,
        $exceptionMessage
    ) {
        $this->expectException(InvalidArgumentException::class);

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        try {
            $service->findSingle($criterion);
        } catch (InvalidArgumentException $e) {
            self::assertEquals($exceptionMessage, $e->getMessage());
            throw $e;
        }

        self::fail('Expected exception was not thrown');
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentThrowsHandlerException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Handler threw an exception');

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();

        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        /** @var Criterion $criterionMock */
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = new Query(['filter' => $criterionMock]);

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::throwException(new Exception('Handler threw an exception')));

        $service->findContent($query, [], true);
    }

    /**
     * Test for the findContent() method when search is out of sync with persistence.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentWhenDomainMapperThrowsException()
    {
        $indexer = $this->createMock(BackgroundIndexer::class);
        $indexer->expects(self::once())
            ->method('registerContent')
            ->with(self::isInstanceOf(SPIContentInfo::class));

        $service = $this->getMockBuilder(SearchService::class)
            ->setConstructorArgs([
                $this->getRepositoryMock(),
                $this->getSPIMockHandler('Search\\Handler'),
                $mapper = $this->getContentDomainMapperMock(),
                $this->getPermissionCriterionResolverMock(),
                $indexer,
            ])->setMethods(['internalFindContentInfo'])
            ->getMock();

        $info = new SPIContentInfo(['id' => 33]);
        $result = new SearchResult(['searchHits' => [new SearchHit(['valueObject' => $info])], 'totalCount' => 2]);
        $service->expects(self::once())
            ->method('internalFindContentInfo')
            ->with(self::isInstanceOf(Query::class))
            ->willReturn($result);

        $mapper->expects(self::once())
            ->method('buildContentDomainObjectsOnSearchResult')
            ->with(self::equalTo($result), self::equalTo([]))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($info) {
                unset($spiResult->searchHits[0]);
                $spiResult->totalCount = $spiResult->totalCount > 0 ? --$spiResult->totalCount : 0;

                return [$info];
            });

        $finalResult = $service->findContent(new Query());

        self::assertEmpty($finalResult->searchHits, 'Expected search hits to be empty');
        self::assertEquals(1, $finalResult->totalCount, 'Expected total count to be 1');
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentNoPermissionsFilter()
    {
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $repositoryMock = $this->getRepositoryMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $mapper = $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock(),
            new NullIndexer(),
            []
        );

        $repositoryMock->expects(self::never())->method('getPermissionResolver');

        $serviceQuery = new Query();
        $handlerQuery = new Query(['filter' => new Criterion\MatchAll(), 'limit' => 25]);
        $languageFilter = [];
        $spiContentInfo = new SPIContentInfo();
        $contentMock = $this->getMockForAbstractClass(Content::class);

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock->expects(self::once())
            ->method('findContent')
            ->with(self::equalTo($handlerQuery), self::equalTo($languageFilter))
            ->will(
                self::returnValue(
                    new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiContentInfo])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $mapper->expects(self::once())
            ->method('buildContentDomainObjectsOnSearchResult')
            ->with(self::isInstanceOf(SearchResult::class), self::equalTo([]))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($contentMock) {
                $spiResult->searchHits[0]->valueObject = $contentMock;

                return [];
            });

        $result = $service->findContent($serviceQuery, $languageFilter, false);

        self::assertEquals(
            new SearchResult(
                [
                    'searchHits' => [new SearchHit(['valueObject' => $contentMock])],
                    'totalCount' => 1,
                ]
            ),
            $result
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentWithPermission()
    {
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $this->getRepositoryMock(),
            $searchHandlerMock,
            $domainMapperMock,
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = new Query(['filter' => $criterionMock, 'limit' => 10]);
        $languageFilter = [];
        $spiContentInfo = new SPIContentInfo();
        $contentMock = $this->getMockForAbstractClass(Content::class);

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::returnValue(true));

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock->expects(self::once())
            ->method('findContent')
            ->with(self::equalTo($query), self::equalTo($languageFilter))
            ->will(
                self::returnValue(
                    new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiContentInfo])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $domainMapperMock
            ->expects(self::once())
            ->method('buildContentDomainObjectsOnSearchResult')
            ->with(self::isInstanceOf(SearchResult::class), self::equalTo([]))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($contentMock) {
                $spiResult->searchHits[0]->valueObject = $contentMock;

                return [];
            });

        $result = $service->findContent($query, $languageFilter, true);

        self::assertEquals(
            new SearchResult(
                [
                    'searchHits' => [new SearchHit(['valueObject' => $contentMock])],
                    'totalCount' => 1,
                ]
            ),
            $result
        );
    }

    /**
     * Test for the findContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findContent
     */
    public function testFindContentWithNoPermission()
    {
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $this->getRepositoryMock(),
            $searchHandlerMock,
            $mapper = $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock->expects(self::never())->method('findContent');

        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = new Query(['filter' => $criterionMock]);

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::returnValue(false));

        $mapper->expects(self::once())
            ->method('buildContentDomainObjectsOnSearchResult')
            ->with(self::isInstanceOf(SearchResult::class), self::equalTo([]))
            ->willReturn([]);

        $result = $service->findContent($query, [], true);

        self::assertEquals(
            new SearchResult(['time' => 0, 'totalCount' => 0]),
            $result
        );
    }

    /**
     * Test for the findContent() method.
     */
    public function testFindContentWithDefaultQueryValues()
    {
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $service = new SearchService(
            $this->getRepositoryMock(),
            $searchHandlerMock,
            $domainMapperMock,
            $this->getPermissionCriterionResolverMock(),
            new NullIndexer(),
            []
        );

        $languageFilter = [];
        $spiContentInfo = new SPIContentInfo();
        $contentMock = $this->getMockForAbstractClass(Content::class);

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock
            ->expects(self::once())
            ->method('findContent')
            ->with(
                new Query(
                    [
                        'filter' => new Criterion\MatchAll(),
                        'limit' => 25,
                    ]
                ),
                []
            )
            ->will(
                self::returnValue(
                    new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiContentInfo])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $domainMapperMock
            ->expects(self::once())
            ->method('buildContentDomainObjectsOnSearchResult')
            ->with(self::isInstanceOf(SearchResult::class), self::equalTo([]))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($contentMock) {
                $spiResult->searchHits[0]->valueObject = $contentMock;

                return [];
            });

        $result = $service->findContent(new Query(), $languageFilter, false);

        self::assertEquals(
            new SearchResult(
                [
                    'searchHits' => [new SearchHit(['valueObject' => $contentMock])],
                    'totalCount' => 1,
                ]
            ),
            $result
        );
    }

    /**
     * Test for the findSingle() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findSingle
     */
    public function testFindSingleThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock(),
            new NullIndexer(),
            []
        );

        /** @var Criterion $criterionMock */
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->willReturn(false);

        $service->findSingle($criterionMock, [], true);
    }

    /**
     * Test for the findSingle() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::addPermissionsCriterion
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findSingle
     */
    public function testFindSingleThrowsHandlerException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Handler threw an exception');

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        /** @var Criterion $criterionMock */
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::throwException(new Exception('Handler threw an exception')));

        $service->findSingle($criterionMock, [], true);
    }

    public function testFindSingle(): void
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler&MockObject $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $domainMapperMock,
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        $repositoryMock
            ->expects(self::once())
            ->method('getContentService')
            ->willReturn(
                $contentServiceMock = $this
                    ->getMockBuilder(ContentService::class)
                    ->disableOriginalConstructor()
                    ->getMock()
            );

        /** @var Criterion $criterionMock */
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->willReturn(true);

        $languageFilter = [];
        $spiContentInfo = new SPIContentInfo(['id' => 123]);
        $contentMock = $this->getMockForAbstractClass(Content::class);

        $searchHandlerMock
            ->expects(self::once())
            ->method('findSingle')
            ->with(self::equalTo($criterionMock), self::equalTo($languageFilter))
            ->willReturn($spiContentInfo);

        $domainMapperMock->expects(self::never())
            ->method(self::anything());

        $contentServiceMock
            ->expects(self::once())
            ->method('internalLoadContentById')
            ->willReturn($contentMock);

        $result = $service->findSingle($criterionMock, $languageFilter, true);

        self::assertEquals($contentMock, $result);
    }

    /**
     * Test for the findLocations() method.
     */
    public function testFindLocationsWithPermission()
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $domainMapperMock,
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = new LocationQuery(['filter' => $criterionMock, 'limit' => 10]);
        $spiLocation = new SPILocation();
        $locationMock = $this->getMockForAbstractClass(Location::class);

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($query))
            ->will(
                self::returnValue(
                    $spiResult = new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiLocation])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::returnValue(true));

        $endResult = new SearchResult(
            [
                'searchHits' => [new SearchHit(['valueObject' => $locationMock])],
                'totalCount' => 1,
            ]
        );

        $domainMapperMock->expects(self::once())
            ->method('buildLocationDomainObjectsOnSearchResult')
            ->with(self::equalTo($spiResult))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($endResult) {
                $spiResult->searchHits[0] = $endResult->searchHits[0];

                return [];
            });

        $result = $service->findLocations($query, [], true);

        self::assertEquals(
            $endResult,
            $result
        );
    }

    /**
     * Test for the findLocations() method.
     */
    public function testFindLocationsWithNoPermissionsFilter()
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $domainMapperMock,
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        $repositoryMock->expects(self::never())->method('getPermissionResolver');

        $serviceQuery = new LocationQuery();
        $handlerQuery = new LocationQuery(['filter' => new Criterion\MatchAll(), 'limit' => 25]);
        $spiLocation = new SPILocation();
        $locationMock = $this->getMockForAbstractClass(Location::class);

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock->expects(self::once())
            ->method('findLocations')
            ->with(self::equalTo($handlerQuery))
            ->will(
                self::returnValue(
                    $spiResult = new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiLocation])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $permissionsCriterionResolverMock->expects(self::never())->method(self::anything());

        $endResult = new SearchResult(
            [
                'searchHits' => [new SearchHit(['valueObject' => $locationMock])],
                'totalCount' => 1,
            ]
        );

        $domainMapperMock->expects(self::once())
            ->method('buildLocationDomainObjectsOnSearchResult')
            ->with(self::equalTo($spiResult))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($endResult) {
                $spiResult->searchHits[0] = $endResult->searchHits[0];

                return [];
            });

        $result = $service->findLocations($serviceQuery, [], false);

        self::assertEquals(
            $endResult,
            $result
        );
    }

    /**
     * Test for the findLocations() method when search is out of sync with persistence.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations
     */
    public function testFindLocationsBackgroundIndexerWhenDomainMapperThrowsException()
    {
        $indexer = $this->createMock(BackgroundIndexer::class);
        $indexer->expects(self::once())
            ->method('registerLocation')
            ->with(self::isInstanceOf(SPILocation::class));

        $service = $this->getMockBuilder(SearchService::class)
            ->setConstructorArgs([
                $this->getRepositoryMock(),
                $searchHandler = $this->getSPIMockHandler('Search\\Handler'),
                $mapper = $this->getContentDomainMapperMock(),
                $this->getPermissionCriterionResolverMock(),
                $indexer,
            ])->setMethods(['addPermissionsCriterion'])
            ->getMock();

        $location = new SPILocation(['id' => 44]);
        $service->expects(self::once())
            ->method('addPermissionsCriterion')
            ->with(self::isInstanceOf(Criterion::class))
            ->willReturn(true);

        $result = new SearchResult(['searchHits' => [new SearchHit(['valueObject' => $location])], 'totalCount' => 2]);
        $searchHandler->expects(self::once())
            ->method('findLocations')
            ->with(self::isInstanceOf(LocationQuery::class), self::isType('array'))
            ->willReturn($result);

        $mapper->expects(self::once())
            ->method('buildLocationDomainObjectsOnSearchResult')
            ->with(self::equalTo($result))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($location) {
                unset($spiResult->searchHits[0]);
                $spiResult->totalCount = $spiResult->totalCount > 0 ? --$spiResult->totalCount : 0;

                return [$location];
            });

        $finalResult = $service->findLocations(new LocationQuery());

        self::assertEmpty($finalResult->searchHits, 'Expected search hits to be empty');
        self::assertEquals(1, $finalResult->totalCount, 'Expected total count to be 1');
    }

    /**
     * Test for the findLocations() method.
     */
    public function testFindLocationsThrowsHandlerException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Handler threw an exception');

        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $permissionsCriterionResolverMock = $this->getPermissionCriterionResolverMock();

        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $this->getContentDomainMapperMock(),
            $permissionsCriterionResolverMock,
            new NullIndexer(),
            []
        );

        /** @var Criterion $criterionMock */
        $criterionMock = $this
            ->getMockBuilder(Criterion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $query = new LocationQuery(['filter' => $criterionMock]);

        $permissionsCriterionResolverMock->expects(self::once())
            ->method('getPermissionsCriterion')
            ->with('content', 'read')
            ->will(self::throwException(new Exception('Handler threw an exception')));

        $service->findLocations($query, [], true);
    }

    /**
     * Test for the findLocations() method.
     */
    public function testFindLocationsWithDefaultQueryValues()
    {
        $repositoryMock = $this->getRepositoryMock();
        /** @var Handler $searchHandlerMock */
        $searchHandlerMock = $this->getSPIMockHandler('Search\\Handler');
        $domainMapperMock = $this->getContentDomainMapperMock();
        $service = new SearchService(
            $repositoryMock,
            $searchHandlerMock,
            $domainMapperMock,
            $this->getPermissionCriterionResolverMock(),
            new NullIndexer(),
            []
        );

        $spiLocation = new SPILocation();
        $locationMock = $this->getMockForAbstractClass(Location::class);

        /* @var \PHPUnit\Framework\MockObject\MockObject $searchHandlerMock */
        $searchHandlerMock
            ->expects(self::once())
            ->method('findLocations')
            ->with(
                new LocationQuery(
                    [
                        'filter' => new Criterion\MatchAll(),
                        'limit' => 25,
                    ]
                )
            )
            ->will(
                self::returnValue(
                    $spiResult = new SearchResult(
                        [
                            'searchHits' => [new SearchHit(['valueObject' => $spiLocation])],
                            'totalCount' => 1,
                        ]
                    )
                )
            );

        $endResult = new SearchResult(
            [
                'searchHits' => [new SearchHit(['valueObject' => $locationMock])],
                'totalCount' => 1,
            ]
        );

        $domainMapperMock->expects(self::once())
            ->method('buildLocationDomainObjectsOnSearchResult')
            ->with(self::equalTo($spiResult))
            ->willReturnCallback(static function (SearchResult $spiResult) use ($endResult) {
                $spiResult->searchHits[0] = $endResult->searchHits[0];

                return [];
            });

        $result = $service->findLocations(new LocationQuery(), [], false);

        self::assertEquals(
            $endResult,
            $result
        );
    }

    /**
     * @return MockObject|\Ibexa\Contracts\Core\Repository\PermissionCriterionResolver
     */
    protected function getPermissionCriterionResolverMock()
    {
        if (!isset($this->permissionsCriterionResolverMock)) {
            $this->permissionsCriterionResolverMock = $this
                ->getMockBuilder(PermissionCriterionResolver::class)
                ->disableOriginalConstructor()
                ->getMock();
        }

        return $this->permissionsCriterionResolverMock;
    }
}
