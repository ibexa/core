<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use function array_map;
use function count;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentList;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\ObjectState\ObjectStateGroupCreateStruct;
use Ibexa\Core\FieldType\Keyword;
use Ibexa\Tests\Core\Repository\Filtering\TestContentProvider;
use function iterator_to_array;
use IteratorAggregate;
use function sprintf;

/**
 * @internal
 */
final class ContentFilteringTest extends BaseRepositoryFilteringTestCase
{
    /**
     * Test that special cases of Location Sort Clauses are working correctly.
     *
     * Content can have multiple Locations, so we need to check if the list of results
     * doesn't contain duplicates.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindWithLocationSortClauses(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $this->contentProvider->createSharedContentStructure();

        // sanity check
        $locations = $locationService->loadLocations(
            $contentService->loadContentInfoByRemoteId(
                TestContentProvider::CONTENT_REMOTE_IDS['folder2']
            )
        );
        self::assertCount(2, $locations);
        [$location1, $location2] = $locations;
        self::assertNotEquals($location1->depth, $location2->depth);

        $sortClause = new SortClause\Location\Depth(Query::SORT_ASC);

        $filter = new Filter();
        $filter
            ->withCriterion(
                new Criterion\RemoteId(TestContentProvider::CONTENT_REMOTE_IDS['folder2'])
            )
            ->orWithCriterion(
                new Criterion\RemoteId(TestContentProvider::CONTENT_REMOTE_IDS['folder1'])
            )
            ->withSortClause(new SortClause\ContentName(Query::SORT_ASC))
            ->withSortClause($sortClause);

        $contentList = $contentService->find($filter);
        $this->assertFoundContentItemsByRemoteIds(
            $contentList,
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
            ]
        );
    }

    public function testLocationSortClausesUseMainLocationDuringContentFiltering(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $shallowParent = $this->createFolder(
            ['eng-GB' => 'Shallow Parent'],
            2
        );
        $referenceContent = $this->createFolder(
            ['eng-GB' => 'Reference folder'],
            $shallowParent->contentInfo->mainLocationId
        );
        $deepParent = $this->createFolder(
            ['eng-GB' => 'Deep Parent'],
            $referenceContent->contentInfo->mainLocationId
        );
        $contentWithAdditionalLocation = $this->createFolder(
            ['eng-GB' => 'Folder with extra location'],
            $deepParent->contentInfo->mainLocationId
        );
        $locationService->createLocation(
            $contentWithAdditionalLocation->contentInfo,
            $locationService->newLocationCreateStruct(2)
        );

        $mainLocation = $this->loadMainLocation($locationService, $contentWithAdditionalLocation);
        $nonMainLocations = [];
        foreach ($locationService->loadLocations($contentWithAdditionalLocation->contentInfo) as $location) {
            if ($location->id !== $contentWithAdditionalLocation->contentInfo->mainLocationId) {
                $nonMainLocations[] = $location;
            }
        }
        self::assertNotEmpty($nonMainLocations);
        $nonMainLocation = $nonMainLocations[0];
        $referenceLocation = $this->loadMainLocation($locationService, $referenceContent);

        self::assertLessThan($referenceLocation->depth, $nonMainLocation->depth);
        self::assertLessThan($mainLocation->depth, $referenceLocation->depth);

        $filter = (new Filter())
            ->withCriterion(
                new Criterion\ContentId(
                    [
                        $contentWithAdditionalLocation->id,
                        $referenceContent->id,
                    ]
                )
            )
            ->withSortClause(new SortClause\Location\Depth(Query::SORT_ASC));

        $contentList = $contentService->find($filter);

        self::assertSame(
            [$referenceContent->id, $contentWithAdditionalLocation->id],
            array_map(
                static function (Content $content): int {
                    return $content->id;
                },
                iterator_to_array($contentList)
            )
        );
    }

    public function testLocationSortClausesStayDeterministicWithComplexCriteria(): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $shallowParent = $this->createFolder(
            ['eng-GB' => 'Complex Root'],
            2
        );
        $referenceContent = $this->createFolder(
            ['eng-GB' => 'Ref folder'],
            $shallowParent->contentInfo->mainLocationId
        );
        $middleContent = $this->createFolder(
            ['eng-GB' => 'Middle folder'],
            $referenceContent->contentInfo->mainLocationId
        );
        $deepParent = $this->createFolder(
            ['eng-GB' => 'Deep intermediate'],
            $middleContent->contentInfo->mainLocationId
        );
        $contentWithAdditionalLocation = $this->createFolder(
            ['eng-GB' => 'Folder with randomizing location'],
            $deepParent->contentInfo->mainLocationId
        );
        $locationService->createLocation(
            $contentWithAdditionalLocation->contentInfo,
            $locationService->newLocationCreateStruct(2)
        );

        $referenceLocation = $this->loadMainLocation($locationService, $referenceContent);
        $middleLocation = $this->loadMainLocation($locationService, $middleContent);
        $mainLocation = $this->loadMainLocation($locationService, $contentWithAdditionalLocation);
        $nonMainLocations = [];
        foreach ($locationService->loadLocations($contentWithAdditionalLocation->contentInfo) as $location) {
            if ($location->id !== $contentWithAdditionalLocation->contentInfo->mainLocationId) {
                $nonMainLocations[] = $location;
            }
        }
        self::assertNotEmpty($nonMainLocations);
        $nonMainLocation = $nonMainLocations[0];
        self::assertNotEquals($mainLocation->depth, $nonMainLocation->depth);

        $shallowParentLocation = $this->loadMainLocation($locationService, $shallowParent);

        $filter = (new Filter())
            ->withCriterion(new Criterion\Subtree($shallowParentLocation->pathString))
            ->andWithCriterion(new Criterion\ContentTypeIdentifier('folder'))
            ->andWithCriterion(
                new Criterion\ContentId(
                    [
                        $referenceContent->id,
                        $middleContent->id,
                        $contentWithAdditionalLocation->id,
                    ]
                )
            )
            ->withSortClause(new SortClause\Location\Depth(Query::SORT_ASC))
            ->withSortClause(new SortClause\ContentId(Query::SORT_ASC))
            ->withLimit(10);

        $contentList = $contentService->find($filter);

        self::assertSame(
            [
                $referenceContent->id,
                $middleContent->id,
                $contentWithAdditionalLocation->id,
            ],
            array_map(
                static function (Content $content): int {
                    return $content->id;
                },
                iterator_to_array($contentList)
            )
        );
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Exception
     */
    protected function compareWithSearchResults(Filter $filter, IteratorAggregate $filteredContentList): void
    {
        $query = $this->buildSearchQueryFromFilter($filter);
        $contentListFromSearch = $this->findUsingContentSearch($query);
        self::assertCount($contentListFromSearch->getTotalCount(), $filteredContentList);
        $filteredContentListIterator = $filteredContentList->getIterator();
        foreach ($contentListFromSearch as $pos => $expectedContentItem) {
            $this->assertContentItemEquals(
                $expectedContentItem,
                $filteredContentListIterator->offsetGet($pos),
                "Content items at the position {$pos} are not the same"
            );
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function findUsingContentSearch(Query $query): ContentList
    {
        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();
        $searchResults = $searchService->findContent($query);

        return new ContentList(
            $searchResults->totalCount,
            array_map(
                static function (SearchHit $searchHit): Content {
                    self::assertInstanceOf(Content::class, $searchHit->valueObject);

                    return $searchHit->valueObject;
                },
                $searchResults->searchHits
            )
        );
    }

    protected function getDefaultSortClause(): FilteringSortClause
    {
        return new SortClause\ContentId();
    }

    public function getFilterFactories(): iterable
    {
        yield from parent::getFilterFactories();

        yield 'Content remote ID for an item without any Location' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\RemoteId(TestContentProvider::CONTENT_REMOTE_IDS['no-location'])
                    );
            },
            // expected total count
            1,
        ];
    }

    /**
     * Create Folder and sub-folders matching expected paginator page size (creates `$pageSize` * `$noOfPages` items).
     *
     * @param int $pageSize
     * @param int $noOfPages
     *
     * @return int parent Folder Location ID
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createMultiplePagesOfContentItems(int $pageSize, int $noOfPages): int
    {
        $parentFolder = $this->createFolder(['eng-GB' => 'Parent Folder'], 2);
        $parentFolderMainLocationId = $parentFolder->contentInfo->mainLocationId;

        $noOfItems = $pageSize * $noOfPages;
        for ($itemNo = 1; $itemNo <= $noOfItems; ++$itemNo) {
            $this->createFolder(['eng-GB' => "Child no #{$itemNo}"], $parentFolderMainLocationId);
        }

        return $parentFolderMainLocationId;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testPagination(): void
    {
        $pageSize = 10;
        $noOfPages = 4;
        $parentLocationId = $this->createMultiplePagesOfContentItems($pageSize, $noOfPages);

        $collectedContentIDs = [];
        $filter = new Filter(new Criterion\ParentLocationId($parentLocationId));
        for ($offset = 0; $offset < $noOfPages; $offset += $pageSize) {
            $filter->sliceBy($pageSize, 0);
            $contentList = $this->find($filter);

            // a total count reflects a total number of items, not a number of items on a current page
            self::assertSame($pageSize * $noOfPages, $contentList->getTotalCount());

            // an actual number of items on a current page
            self::assertCount($pageSize, $contentList);

            // check if results are not duplicated across multiple pages
            foreach ($contentList as $contentItem) {
                self::assertNotContains(
                    $contentItem->id,
                    $collectedContentIDs,
                    "Content item ID={$contentItem->id} exists on multiple pages"
                );
                $collectedContentIDs[] = $contentItem->id;
            }
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindContentWithExternalStorageFields(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();

        $blogType = $contentTypeService->loadContentTypeByIdentifier('blog');
        $contentCreate = $contentService->newContentCreateStruct($blogType, 'eng-GB');
        $contentCreate->setField('name', 'British Blog');
        $contentCreate->setField('tags', new Keyword\Value(['British', 'posts']));
        $contentDraft = $contentService->createContent($contentCreate);
        $contentService->publishVersion($contentDraft->getVersionInfo());

        $filter = new Filter(new Criterion\ContentTypeIdentifier('blog'));
        $contentList = $this->find($filter, []);

        self::assertSame(1, $contentList->getTotalCount());
        self::assertCount(1, $contentList);

        foreach ($contentList as $content) {
            $legacyLoadedContent = $contentService->loadContent($content->id, []);
            self::assertEquals($legacyLoadedContent, $content);
        }
    }

    /**
     * @dataProvider getDataForTestFindContentWithLocationCriterion
     *
     * @param string[] $expectedContentRemoteIds
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindContentUsingLocationCriterion(
        callable $filterFactory,
        array $expectedContentRemoteIds
    ): void {
        $parentFolder = $this->contentProvider->createSharedContentStructure();
        $filter = $this->buildFilter($filterFactory, $parentFolder);
        $this->assertFoundContentItemsByRemoteIds(
            $this->find($filter, []),
            $expectedContentRemoteIds
        );
    }

    public function getDataForTestFindContentWithLocationCriterion(): iterable
    {
        yield 'Content items with secondary Location, sorted by Content ID' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\Location\IsMainLocation(
                            Criterion\Location\IsMainLocation::NOT_MAIN
                        )
                    )
                    ->withSortClause(new SortClause\ContentId(Query::SORT_ASC));
            },
            [TestContentProvider::CONTENT_REMOTE_IDS['folder2']],
        ];

        yield 'Folders with Location, sorted by Content ID' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\Location\IsMainLocation(
                            Criterion\Location\IsMainLocation::MAIN
                        )
                    )
                    ->andWithCriterion(
                        new Criterion\ParentLocationId($parentFolder->contentInfo->mainLocationId)
                    )
                    ->andWithCriterion(
                        new Criterion\ContentTypeIdentifier('folder')
                    )
                    ->withSortClause(new SortClause\ContentId(Query::SORT_ASC));
            },
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
            ],
        ];
    }

    protected function assertFoundContentItemsByRemoteIds(
        iterable $list,
        array $expectedContentRemoteIds
    ): void {
        self::assertCount(count($expectedContentRemoteIds), $list);
        foreach ($list as $content) {
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
            self::assertContainsEquals(
                $content->contentInfo->remoteId,
                $expectedContentRemoteIds,
                sprintf(
                    'Content %d (%s) was not supposed to be found',
                    $content->id,
                    $content->contentInfo->remoteId
                )
            );
        }
    }

    /**
     * @dataProvider getListOfSupportedSortClauses
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testFindWithSortClauses(string $sortClauseFQCN): void
    {
        $this->performAndAssertSimpleSortClauseQuery(new $sortClauseFQCN(Query::SORT_ASC));
        $this->performAndAssertSimpleSortClauseQuery(new $sortClauseFQCN(Query::SORT_DESC));
    }

    /**
     * Simple test to check each sort clause validity on a database integration level.
     *
     * Note: It should be expanded in the future to check validity of the sorting logic itself
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function performAndAssertSimpleSortClauseQuery(FilteringSortClause $sortClause): void
    {
        $filter = new Filter(new Criterion\ContentId(57), [$sortClause]);
        $contentList = $this->find($filter, []);
        self::assertCount(1, $contentList);
        $contentItem = $contentList->getIterator()[0];
        self::assertNotNull($contentItem);
        self::assertSame(57, $contentItem->id);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testObjectStateIdCriterionOnMultipleObjectStates(): void
    {
        $contentService = $this->getRepository()->getContentService();
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $locationService = $this->getRepository()->getLocationService();
        $objectStateService = $this->getRepository()->getObjectStateService();

        // Create additional Object States
        $objectStateGroupStruct = new ObjectStateGroupCreateStruct();
        $objectStateGroupStruct->identifier = 'domain';
        $objectStateGroupStruct->names = ['eng-GB' => 'Domain'];
        $objectStateGroupStruct->defaultLanguageCode = 'eng-GB';
        $objectStateGroup = $objectStateService->createObjectStateGroup($objectStateGroupStruct);

        $objectStateCreateStruct = new ObjectStateCreateStruct();
        $objectStateCreateStruct->identifier = 'public';
        $objectStateCreateStruct->names = ['eng-GB' => 'Public'];
        $objectStateCreateStruct->defaultLanguageCode = 'eng-GB';
        $objectStateService->createObjectState($objectStateGroup, $objectStateCreateStruct);

        $objectStateCreateStruct->identifier = 'private';
        $objectStateCreateStruct->names = ['eng-GB' => 'Private'];
        $objectStatePrivate = $objectStateService->createObjectState($objectStateGroup, $objectStateCreateStruct);

        // Create a new content object and assign object state "Private" to it:
        $contentCreate = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $contentCreate->setField('name', 'Private Folder');
        $content = $contentService->createContent(
            $contentCreate,
            [$locationService->newLocationCreateStruct(2)]
        );
        $contentService->publishVersion(
            $content->getVersionInfo()
        );
        $objectStateService->setContentState(
            $content->getVersionInfo()->getContentInfo(),
            $objectStateGroup,
            $objectStatePrivate
        );

        $filter = new Filter();
        $filter
            ->withCriterion(new Criterion\LogicalAnd([
                new Criterion\ParentLocationId(2),
                new Criterion\LogicalAnd([
                    new Criterion\ObjectStateId(1),
                    new Criterion\ObjectStateId($objectStatePrivate->id),
                ]),
            ]));

        $results = $this->find($filter);

        self::assertEquals(
            1,
            $results->getTotalCount(),
            'Expected to find only one object which has state "not_locked" and "private"'
        );

        foreach ($results as $result) {
            self::assertEquals($result->id, $content->id, 'Expected to find "Private Folder"');
        }
    }

    public function getListOfSupportedSortClauses(): iterable
    {
        yield 'Content\\Id' => [SortClause\ContentId::class];
        yield 'ContentName' => [SortClause\ContentName::class];
        yield 'DateModified' => [SortClause\DateModified::class];
        yield 'DatePublished' => [SortClause\DatePublished::class];
        yield 'SectionIdentifier' => [SortClause\SectionIdentifier::class];
        yield 'SectionName' => [SortClause\SectionName::class];
        yield 'Location\\Depth' => [SortClause\Location\Depth::class];
        yield 'Location\\Id' => [SortClause\Location\Id::class];
        yield 'Location\\Path' => [SortClause\Location\Path::class];
        yield 'Location\\Priority' => [SortClause\Location\Priority::class];
        yield 'Location\\Visibility' => [SortClause\Location\Visibility::class];
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentList
     */
    protected function find(Filter $filter, ?array $contextLanguages = null): iterable
    {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();

        return $contentService->find($filter, $contextLanguages);
    }

    private function buildSearchQueryFromFilter(Filter $filter): Query
    {
        $limit = $filter->getLimit();

        return new Query(
            [
                'filter' => $filter->getCriterion(),
                'sortClauses' => $filter->getSortClauses(),
                'offset' => $filter->getOffset(),
                'limit' => $limit > 0 ? $limit : 999,
            ]
        );
    }

    private function loadMainLocation(LocationService $locationService, Content $content): Location
    {
        $mainLocationId = $content->contentInfo->mainLocationId;
        self::assertNotNull($mainLocationId);

        return $locationService->loadLocation($mainLocationId);
    }
}

class_alias(ContentFilteringTest::class, 'eZ\Publish\API\Repository\Tests\Filtering\ContentFilteringTest');
