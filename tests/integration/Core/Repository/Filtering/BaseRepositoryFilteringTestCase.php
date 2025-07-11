<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Filtering;

use Ibexa\Contracts\Core\Repository\Collections\TotalCountAwareInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion;
use Ibexa\Contracts\Core\Repository\Values\Filter\FilteringSortClause;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use Ibexa\Tests\Core\Repository\Filtering\TestContentProvider;
use Ibexa\Tests\Integration\Core\Repository\BaseTestCase;
use IteratorAggregate;

/**
 * @internal for internal use by Ibexa Kernel test cases
 */
abstract class BaseRepositoryFilteringTestCase extends BaseTestCase
{
    private const PAGINATION_EXPECTED_TOTAL_COUNT = 5;
    private const PAGINATION_LIMIT = 3;
    private const PAGINATION_OFFSET = 2;

    /** @var \Ibexa\Tests\Core\Repository\Filtering\TestContentProvider */
    protected $contentProvider;

    abstract protected function find(Filter $filter, ?array $contextLanguages = null): iterable;

    abstract protected function assertFoundContentItemsByRemoteIds(
        iterable $list,
        array $expectedContentRemoteIds
    ): void;

    abstract protected function compareWithSearchResults(Filter $filter, IteratorAggregate $list): void;

    abstract protected function getDefaultSortClause(): FilteringSortClause;

    /**
     * @dataProvider getFilterFactories
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::find
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::find
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFind(callable $filterFactory): void
    {
        $filter = $this->buildFilter(
            $filterFactory,
            $this->contentProvider->createSharedContentStructure()
        );
        if ([] === $filter->getSortClauses()) {
            // there has to be a sort clause to compare results with search engine
            $filter->withSortClause($this->getDefaultSortClause());
        }

        // validate the result using search service
        $list = $this->find($filter);
        /** @var \IteratorAggregate $list */
        $this->compareWithSearchResults($filter, $list);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->contentProvider = new TestContentProvider($this->getRepository(false), $this);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindDoesNotFindDrafts(): void
    {
        $contentDraft = $this->contentProvider->createContentDraft(
            'folder',
            ['name' => [TestContentProvider::ENG_US => 'Draft Folder']],
        );

        $filter = new Filter();
        $filter
            ->withCriterion(new Criterion\ContentId($contentDraft->id));

        $list = $this->find($filter, []);

        self::assertCount(0, $list);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testFindPaginated(): void
    {
        $parentFolder = $this->createFolder([TestContentProvider::ENG_US => 'Parent Folder'], 2);
        $parentLocationId = $parentFolder->contentInfo->mainLocationId;
        for ($i = 0; $i < self::PAGINATION_EXPECTED_TOTAL_COUNT; ++$i) {
            $this->createFolder(
                [
                    TestContentProvider::ENG_US => 'Folder ' . ($i + 1),
                ],
                $parentLocationId
            );
        }

        $filter = new Filter();
        $filter
            ->withCriterion(
                new Criterion\ParentLocationId($parentLocationId)
            )
            ->withLimit(self::PAGINATION_LIMIT)
            ->withOffset(self::PAGINATION_OFFSET);

        $list = $this->find($filter, []);
        self::assertInstanceOf(TotalCountAwareInterface::class, $list);
        self::assertSame(self::PAGINATION_EXPECTED_TOTAL_COUNT, $list->getTotalCount());
        self::assertCount(self::PAGINATION_LIMIT, $list);
    }

    /**
     * @covers  \Ibexa\Contracts\Core\Repository\ContentService::find
     *
     * @dataProvider getUserLimitationData
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation[] $limitations
     * @param string[] $expectedContentRemoteIds
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testFindByUserWithLimitations(
        array $limitations,
        array $expectedContentRemoteIds
    ): void {
        $repository = $this->getRepository();
        $parentFolder = $this->contentProvider->createSharedContentStructure();
        $login = uniqid('editor', true);
        $user = $this->createUserWithPolicies(
            $login,
            [
                ['module' => 'content', 'function' => 'read', 'limitations' => $limitations],
            ],
            new Limitation\SubtreeLimitation(
                ['limitationValues' => [$parentFolder->contentInfo->getMainLocation()->pathString]]
            )
        );
        $repository->getPermissionResolver()->setCurrentUserReference($user);

        $filter = new Filter();
        $filter->withCriterion(new ParentLocationId($parentFolder->contentInfo->mainLocationId));

        $this->assertFoundContentItemsByRemoteIds($this->find($filter), $expectedContentRemoteIds);
    }

    /**
     * Data provider consumed by children implementations.
     */
    public function getFilterFactories(): iterable
    {
        // Note: Filter relying on database data cannot be instantiated here
        // because database data is not available yet
        yield 'ParentLocationID' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\ParentLocationId($parentFolder->contentInfo->mainLocationId)
                    );
            },
            5,
        ];

        yield 'ParentLocationID for a single Translation' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\ParentLocationId($parentFolder->contentInfo->mainLocationId)
                    )
                    ->andWithCriterion(new Criterion\LanguageCode(['eng-GB']));
            },
            5,
        ];

        yield 'ParentLocationID with Sort Clauses' => [
            static function (Content $parentFolder): Filter {
                return (new Filter())
                    ->withCriterion(
                        new Criterion\ParentLocationId($parentFolder->contentInfo->mainLocationId)
                    )
                    ->withSortClause(new SortClause\DatePublished(Query::SORT_ASC))
                    ->withSortClause(new SortClause\ContentId(Query::SORT_ASC));
            },
            // expected total count
            5,
        ];

        foreach ($this->getCriteriaForInitialData() as $dataSetName => $filter) {
            yield $dataSetName => [
                static function (Content $parentFolder) use ($filter): Filter {
                    return new Filter($filter);
                },
                // for those rely on search result count
                null,
            ];
        }
    }

    /**
     * A list of Criteria which arguments rely on initial test data to work.
     *
     * Note: this is a quick attempt to cover all supported Filtering Criteria. In the future it
     * should be refactored to rely on shared data structure created at runtime.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Filter\FilteringCriterion[]
     *
     * @see getFilterFactories
     */
    public function getCriteriaForInitialData(): iterable
    {
        yield 'Ancestor=/1/5/44/45/' => new Criterion\Ancestor('/1/5/44/45/');
        yield 'ContentId=57' => new Criterion\ContentId(57);
        yield 'ContentTypeGroupId=1' => new Criterion\ContentTypeGroupId(1);
        yield 'ContentTypeId=1' => new Criterion\ContentTypeId(1);
        yield 'ContentTypeIdentifier=folder' => new Criterion\ContentTypeIdentifier('folder');
        yield 'DateMetadata=BETWEEN 1080220197 AND 1448889046' => new Criterion\DateMetadata(
            Criterion\DateMetadata::CREATED,
            Criterion\Operator::BETWEEN,
            [1080220197, 1448889046]
        );
        yield 'IsContainer=true' => new Criterion\IsContainer(true);
        yield 'IsContainer=false' => new Criterion\IsContainer(false);
        yield 'IsUserBased=true' => new Criterion\IsUserBased(true);
        yield 'IsUserBased=false' => new Criterion\IsUserBased(false);
        yield 'IsUserEnabled=true' => new Criterion\IsUserEnabled();
        yield 'LanguageCode=eng-GB' => new Criterion\LanguageCode(TestContentProvider::ENG_GB);
        yield 'LocationId=2' => new Criterion\LocationId(2);
        yield 'LocationRemoteId=f3e90596361e31d496d4026eb624c983' => new Criterion\LocationRemoteId(
            'f3e90596361e31d496d4026eb624c983'
        );
        yield 'MatchAll' => new Criterion\MatchAll();
        yield 'MatchNone' => new Criterion\MatchNone();
        yield 'ObjectStateId=1' => new Criterion\ObjectStateId(1);
        yield 'ObjectStateIdentifier=not_locked' => new Criterion\ObjectStateIdentifier(
            'not_locked'
        );
        yield 'ObjectStateIdentifier=ibexa_lock(not_locked)' => new Criterion\ObjectStateIdentifier(
            ['not_locked'],
            'ibexa_lock'
        );
        yield 'ParentLocationId=1' => new Criterion\ParentLocationId(1);
        yield 'RemoteId=8a9c9c761004866fb458d89910f52bee' => new Criterion\RemoteId(
            '8a9c9c761004866fb458d89910f52bee'
        );
        yield 'SectionId=1' => new Criterion\SectionId(1);
        yield 'SectionIdentifier=standard' => new Criterion\SectionIdentifier('standard');
        yield 'Sibling IN 2, 1]' => new Criterion\Sibling(2, 1);
        yield 'Subtree=/1/2/' => new Criterion\Subtree('/1/2/');
        yield 'UserEmail=admin@link.invalid' => new Criterion\UserEmail('admin@link.invalid');
        yield 'UserEmail=admin@*' => new Criterion\UserEmail('*@link.invalid', Criterion\Operator::LIKE);
        yield 'UserId=14' => new Criterion\UserId(14);
        yield 'UserLogin=admin' => new Criterion\UserLogin('admin');
        yield 'UserLogin=a*' => new Criterion\UserLogin('a*', Criterion\Operator::LIKE);
        yield 'UserMetadata=OWNER IN (10, 14)' => new Criterion\UserMetadata(
            Criterion\UserMetadata::OWNER,
            Criterion\Operator::IN,
            [10, 14]
        );
        yield 'UserMetadata=GROUP IN (12)' => new Criterion\UserMetadata(
            Criterion\UserMetadata::GROUP,
            Criterion\Operator::EQ,
            12
        );
        yield 'UserMetadata=MODIFIER IN (14)' => new Criterion\UserMetadata(
            Criterion\UserMetadata::MODIFIER,
            Criterion\Operator::EQ,
            14
        );
        yield 'Visibility=VISIBLE' => new Criterion\Visibility(Criterion\Visibility::VISIBLE);
    }

    protected function assertTotalCount(FilteringCriterion $criterion, int $searchTotalCount): void
    {
        if (!$criterion instanceof Criterion\MatchNone) {
            self::assertGreaterThan(
                0,
                $searchTotalCount,
                sprintf(
                    'There is no corresponding data to test the "%s" Criterion',
                    get_class($criterion)
                )
            );
        } else {
            // special case for a single criterion (not worth to make test impl. cleaner)
            self::assertSame(
                0,
                $searchTotalCount,
                sprintf('MatchNone is expected to return 0 rows, %d returned', $searchTotalCount)
            );
        }
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function getUserLimitationData(): iterable
    {
        $repository = $this->getRepository(false);

        // Content type Limitations
        $contentTypeService = $repository->getContentTypeService();
        $articleType = $contentTypeService->loadContentTypeByIdentifier('article');
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        yield 'ContentTypeLimitation IN (article)' => [
            [new Limitation\ContentTypeLimitation(['limitationValues' => [$articleType->id]])],
            ['remote-id-article-1', 'remote-id-article-2', 'remote-id-article-3'],
        ];
        yield 'ContentTypeLimitation IN (folder)' => [
            [new Limitation\ContentTypeLimitation(['limitationValues' => [$folderType->id]])],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
            ],
        ];
        yield 'ContentTypeLimitation IN (article, folder)' => [
            [
                new Limitation\ContentTypeLimitation(
                    ['limitationValues' => [$articleType->id, $folderType->id]]
                ),
            ],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article1'],
                TestContentProvider::CONTENT_REMOTE_IDS['article2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article3'],
            ],
        ];

        // Section Limitation
        $sectionService = $repository->getSectionService();
        $standardSection = $sectionService->loadSectionByIdentifier('standard');
        yield 'SectionLimitation IN (standard)' => [
            [new Limitation\SectionLimitation(['limitationValues' => [$standardSection->id]])],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article1'],
                TestContentProvider::CONTENT_REMOTE_IDS['article2'],
            ],
        ];

        // User-related Limitations
        yield 'OwnerLimitation = self' => [
            [new Limitation\OwnerLimitation(['limitationValues' => [1]])],
            [],
        ];

        yield 'UserGroupLimitation = self' => [
            [new Limitation\UserGroupLimitation(['limitationValues' => [1]])],
            [],
        ];

        // Location-related Limitations
        yield 'LocationLimitation IN (administrator users)' => [
            [new Limitation\LocationLimitation(['limitationValues' => [2]])],
            [],
        ];

        yield 'SubtreeLimitation IN (/1/2/)' => [
            [new Limitation\SubtreeLimitation(['limitationValues' => ['/1/2/']])],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article1'],
                TestContentProvider::CONTENT_REMOTE_IDS['article2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article3'],
            ],
        ];

        // Object State Limitation
        yield 'ObjectStateLimitation IN (locked, not_locked)' => [
            [new Limitation\ObjectStateLimitation(['limitationValues' => [1, 2]])],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['folder1'],
                TestContentProvider::CONTENT_REMOTE_IDS['folder2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article1'],
                TestContentProvider::CONTENT_REMOTE_IDS['article2'],
                TestContentProvider::CONTENT_REMOTE_IDS['article3'],
            ],
        ];

        yield 'ContentTypeLimitation AND SectionLimitation' => [
            [
                new Limitation\ContentTypeLimitation(['limitationValues' => [$articleType->id]]),
                new Limitation\SectionLimitation(['limitationValues' => [$standardSection->id]]),
            ],
            [
                TestContentProvider::CONTENT_REMOTE_IDS['article1'],
                TestContentProvider::CONTENT_REMOTE_IDS['article2'],
            ],
        ];
    }

    /**
     * Build Repository Filter from a callable factory accepting Content item as a container for
     * all items under test.
     */
    protected function buildFilter(callable $filterFactory, Content $parentFolder): Filter
    {
        return $filterFactory($parentFolder);
    }
}
