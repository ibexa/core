<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\ContentInfo;
use Ibexa\Contracts\Core\Persistence\Content\Type;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Persistence;
use Ibexa\Core\Persistence\Legacy\Content\FieldHandler;
use Ibexa\Core\Persistence\Legacy\Content\Location\Mapper as LocationMapper;
use Ibexa\Core\Persistence\Legacy\Content\Mapper as ContentMapper;
use Ibexa\Core\Search\Legacy\Content;
use Ibexa\Core\Search\Legacy\Content\Handler;
use Ibexa\Core\Search\Legacy\Content\Location\Gateway as LocationGateway;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Content Search test case for ContentSearchHandler.
 */
class HandlerContentTest extends AbstractTestCase
{
    /**
     * Returns the content search handler to test.
     *
     * This method returns a fully functional search handler to perform tests
     * on.
     *
     * @param array $fullTextSearchConfiguration
     *
     * @return \Ibexa\Core\Search\Legacy\Content\Handler
     */
    protected function getContentSearchHandler(array $fullTextSearchConfiguration = []): Handler
    {
        $transformationProcessor = new Persistence\TransformationProcessor\DefinitionBased(
            new Persistence\TransformationProcessor\DefinitionBased\Parser(),
            new Persistence\TransformationProcessor\PcreCompiler(
                new Persistence\Utf8Converter()
            ),
            glob(__DIR__ . '/../../../../Persistence/Tests/TransformationProcessor/_fixtures/transformations/*.tr')
        );
        $connection = $this->getDatabaseConnection();
        $commaSeparatedCollectionValueHandler = new Content\Common\Gateway\CriterionHandler\FieldValue\Handler\Collection(
            $connection,
            $transformationProcessor,
            ','
        );
        $hyphenSeparatedCollectionValueHandler = new Content\Common\Gateway\CriterionHandler\FieldValue\Handler\Collection(
            $connection,
            $transformationProcessor,
            '-'
        );
        $simpleValueHandler = new Content\Common\Gateway\CriterionHandler\FieldValue\Handler\Simple(
            $connection,
            $transformationProcessor
        );
        $compositeValueHandler = new Content\Common\Gateway\CriterionHandler\FieldValue\Handler\Composite(
            $connection,
            $transformationProcessor
        );

        return new Handler(
            new Content\Gateway\DoctrineDatabase(
                $connection,
                new Content\Common\Gateway\CriteriaConverter(
                    [
                        new Content\Common\Gateway\CriterionHandler\ContentId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\LogicalNot(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\LogicalAnd(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\LogicalOr(
                            $connection
                        ),
                        new Content\Gateway\CriterionHandler\Subtree(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\ContentTypeId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\ContentTypeIdentifier(
                            $connection,
                            $this->getContentTypeHandler()
                        ),
                        new Content\Common\Gateway\CriterionHandler\ContentTypeGroupId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\DateMetadata(
                            $connection
                        ),
                        new Content\Gateway\CriterionHandler\LocationId(
                            $connection
                        ),
                        new Content\Gateway\CriterionHandler\ParentLocationId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\RemoteId(
                            $connection
                        ),
                        new Content\Gateway\CriterionHandler\LocationRemoteId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\SectionId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\FullText(
                            $connection,
                            $transformationProcessor,
                            $this->getLanguageMaskGenerator(),
                            $fullTextSearchConfiguration
                        ),
                        new Content\Common\Gateway\CriterionHandler\Field(
                            $connection,
                            $this->getContentTypeHandler(),
                            $this->getLanguageHandler(),
                            $this->getConverterRegistry(),
                            new Content\Common\Gateway\CriterionHandler\FieldValue\Converter(
                                new Content\Common\Gateway\CriterionHandler\FieldValue\HandlerRegistry(
                                    [
                                        'ezboolean' => $simpleValueHandler,
                                        'ezcountry' => $commaSeparatedCollectionValueHandler,
                                        'ezdate' => $simpleValueHandler,
                                        'ezdatetime' => $simpleValueHandler,
                                        'ezemail' => $simpleValueHandler,
                                        'ezinteger' => $simpleValueHandler,
                                        'ezobjectrelation' => $simpleValueHandler,
                                        'ezobjectrelationlist' => $commaSeparatedCollectionValueHandler,
                                        'ezselection' => $hyphenSeparatedCollectionValueHandler,
                                        'eztime' => $simpleValueHandler,
                                    ]
                                ),
                                $compositeValueHandler
                            ),
                            $transformationProcessor
                        ),
                        new Content\Common\Gateway\CriterionHandler\ObjectStateId(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\LanguageCode(
                            $connection,
                            $this->getLanguageMaskGenerator()
                        ),
                        new Content\Gateway\CriterionHandler\Visibility(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\MatchAll(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\UserMetadata(
                            $connection
                        ),
                        new Content\Common\Gateway\CriterionHandler\FieldRelation(
                            $connection,
                            $this->getContentTypeHandler(),
                            $this->getLanguageHandler()
                        ),
                    ]
                ),
                new Content\Common\Gateway\SortClauseConverter(
                    [
                        new Content\Common\Gateway\SortClauseHandler\ContentId($connection),
                    ]
                ),
                $this->getLanguageHandler()
            ),
            $this->createMock(LocationGateway::class),
            new Content\WordIndexer\Gateway\DoctrineDatabase(
                $this->getDatabaseConnection(),
                $this->getContentTypeHandler(),
                $this->getDefinitionBasedTransformationProcessor(),
                new Content\WordIndexer\Repository\SearchIndex($this->getDatabaseConnection()),
                $this->getLanguageMaskGenerator(),
                $this->getFullTextSearchConfiguration()
            ),
            $this->getContentMapperMock(),
            $this->createMock(LocationMapper::class),
            $this->getLanguageHandler(),
            $this->getFullTextMapper($this->getContentTypeHandler())
        );
    }

    /**
     * Returns a content mapper mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\Mapper
     */
    protected function getContentMapperMock(): MockObject
    {
        $mapperMock = $this->getMockBuilder(ContentMapper::class)
            ->setConstructorArgs(
                [
                    $this->getConverterRegistry(),
                    $this->getLanguageHandler(),
                    $this->getContentTypeHandler(),
                    $this->getEventDispatcher(),
                ]
            )
            ->setMethods(['extractContentInfoFromRows'])
            ->getMock();
        $mapperMock->expects(self::any())
            ->method('extractContentInfoFromRows')
            ->with(self::isType('array'))
            ->will(
                self::returnCallback(
                    static function ($rows): array {
                        $contentInfoObjs = [];
                        foreach ($rows as $row) {
                            $contentId = (int)$row['id'];
                            if (!isset($contentInfoObjs[$contentId])) {
                                $contentInfoObjs[$contentId] = new ContentInfo();
                                $contentInfoObjs[$contentId]->id = $contentId;
                            }
                        }

                        return array_values($contentInfoObjs);
                    }
                )
            );

        return $mapperMock;
    }

    /**
     * Returns a content field handler mock.
     *
     * @return \Ibexa\Core\Persistence\Legacy\Content\FieldHandler
     */
    protected function getContentFieldHandlerMock(): MockObject
    {
        return $this->getMockBuilder(FieldHandler::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadExternalFieldData'])
            ->getMock();
    }

    /**
     * Bug #80.
     */
    public function testFindWithoutOffsetLimit(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\ContentId(10),
                ]
            )
        );

        self::assertEquals(
            1,
            $result->totalCount
        );
    }

    /**
     * Bug #81, bug #82.
     */
    public function testFindWithZeroLimit(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\ContentId(10),
                    'offset' => 0,
                    'limit' => 0,
                ]
            )
        );

        self::assertEquals(
            1,
            $result->totalCount
        );
        self::assertEquals(
            [],
            $result->searchHits
        );
    }

    /**
     * Issue with PHP_MAX_INT limit overflow in databases.
     */
    public function testFindWithNullLimit(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\ContentId(10),
                    'offset' => 0,
                    'limit' => null,
                ]
            )
        );

        self::assertEquals(
            1,
            $result->totalCount
        );
        self::assertCount(
            1,
            $result->searchHits
        );
    }

    /**
     * Issue with offsetting to the nonexistent results produces \ezcQueryInvalidParameterException exception.
     */
    public function testFindWithOffsetToNonexistent(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\ContentId(10),
                    'offset' => 1000,
                    'limit' => null,
                ]
            )
        );

        self::assertEquals(
            1,
            $result->totalCount
        );
        self::assertCount(
            0,
            $result->searchHits
        );
    }

    public function testFindSingle(): void
    {
        $locator = $this->getContentSearchHandler();

        $contentInfo = $locator->findSingle(new Criterion\ContentId(10));

        self::assertEquals(10, $contentInfo->id);
    }

    public function testFindSingleWithNonSearchableField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locator = $this->getContentSearchHandler();
        $locator->findSingle(
            new Criterion\Field(
                'tag_cloud_url',
                Criterion\Operator::EQ,
                'http://nimbus.com'
            )
        );
    }

    public function testFindContentWithNonSearchableField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locator = $this->getContentSearchHandler();
        $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\Field(
                        'tag_cloud_url',
                        Criterion\Operator::EQ,
                        'http://nimbus.com'
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testFindSingleTooMany(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locator = $this->getContentSearchHandler();
        $locator->findSingle(new Criterion\ContentId([4, 10, 12, 23]));
    }

    public function testFindSingleZero(): void
    {
        $this->expectException(NotFoundException::class);

        $locator = $this->getContentSearchHandler();
        $locator->findSingle(new Criterion\ContentId(0));
    }

    public function testContentIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 10],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ContentId(
                            [1, 4, 10]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentIdFilterCount(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\ContentId(
                        [1, 4, 10]
                    ),
                    'limit' => 10,
                ]
            )
        );

        self::assertSame(2, $result->totalCount);
    }

    public function testContentAndCombinatorFilter(): void
    {
        $this->assertSearchResults(
            [4],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\ContentId(
                                    [1, 4, 10]
                                ),
                                new Criterion\ContentId(
                                    [4, 12]
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentOrCombinatorFilter(): void
    {
        $locator = $this->getContentSearchHandler();

        $result = $locator->findContent(
            new Query(
                [
                    'filter' => new Criterion\LogicalOr(
                        [
                            new Criterion\ContentId(
                                [1, 4, 10]
                            ),
                            new Criterion\ContentId(
                                [4, 12]
                            ),
                        ]
                    ),
                    'limit' => 10,
                ]
            )
        );

        $expectedContentIds = [4, 10, 12];

        self::assertEquals(
            count($expectedContentIds),
            count($result->searchHits)
        );
        foreach ($result->searchHits as $hit) {
            self::assertContains(
                $hit->valueObject->id,
                $expectedContentIds
            );
        }
    }

    public function testContentNotCombinatorFilter(): void
    {
        $this->assertSearchResults(
            [4],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\ContentId(
                                    [1, 4, 10]
                                ),
                                new Criterion\LogicalNot(
                                    new Criterion\ContentId(
                                        [10, 12]
                                    )
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentSubtreeFilterIn(): void
    {
        $this->assertSearchResults(
            [67, 68, 69, 70, 71, 72, 73, 74],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Subtree(
                            ['/1/2/69/']
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentSubtreeFilterEq(): void
    {
        $this->assertSearchResults(
            [67, 68, 69, 70, 71, 72, 73, 74],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Subtree('/1/2/69/'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentTypeIdFilter(): void
    {
        $this->assertSearchResults(
            [10, 14, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ContentTypeId(4),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentTypeIdentifierFilter(): void
    {
        $this->assertSearchResults(
            [41, 45, 49, 50, 51],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ContentTypeIdentifier('folder'),
                        'limit' => 5,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testContentTypeGroupFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 42, 225, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ContentTypeGroupId(2),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterModifiedGreater(): void
    {
        $this->assertSearchResults(
            [11, 225, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\DateMetadata(
                            Criterion\DateMetadata::MODIFIED,
                            Criterion\Operator::GT,
                            1311154214
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterModifiedGreaterOrEqual(): void
    {
        $this->assertSearchResults(
            [11, 14, 225, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\DateMetadata(
                            Criterion\DateMetadata::MODIFIED,
                            Criterion\Operator::GTE,
                            1311154214
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterModifiedIn(): void
    {
        $this->assertSearchResults(
            [11, 14, 225, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\DateMetadata(
                            Criterion\DateMetadata::MODIFIED,
                            Criterion\Operator::IN,
                            [1311154214, 1311154215]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterModifiedBetween(): void
    {
        $this->assertSearchResults(
            [11, 14, 225, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\DateMetadata(
                            Criterion\DateMetadata::MODIFIED,
                            Criterion\Operator::BETWEEN,
                            [1311154213, 1311154215]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterCreatedBetween(): void
    {
        $this->assertSearchResults(
            [66, 131, 225],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\DateMetadata(
                            Criterion\DateMetadata::CREATED,
                            Criterion\Operator::BETWEEN,
                            [1299780749, 1311154215]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testLocationIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 65],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LocationId([1, 2, 5]),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testParentLocationIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 41, 45, 56, 65],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ParentLocationId([1]),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testRemoteIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 10],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\RemoteId(
                            ['f5c88a2209584891056f987fd965b0ba', 'faaeb9be3bd98ed09f606fc16d144eca']
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testLocationRemoteIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 65],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LocationRemoteId(
                            ['3f6d92f8044aed134f32153517850f5a', 'f3e90596361e31d496d4026eb624c983']
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testSectionFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 42, 226],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\SectionId([2]),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testStatusFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $searchResult = $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        // Status criterion is gone, but this will also match all published
                        'filter' => new Criterion\LogicalNot(
                            new Criterion\ContentId(
                                [0]
                            )
                        ),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );

        self::assertEquals(
            185,
            $searchResult->totalCount
        );
    }

    public function testFieldFilter(): void
    {
        $this->assertSearchResults(
            [11],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'name',
                            Criterion\Operator::EQ,
                            'members'
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterIn(): void
    {
        $this->assertSearchResults(
            [11, 42],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'name',
                            Criterion\Operator::IN,
                            ['members', 'anonymous users']
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterContainsPartial(): void
    {
        $this->assertSearchResults(
            [42],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'name',
                            Criterion\Operator::CONTAINS,
                            'nonymous use'
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterContainsSimple(): void
    {
        $this->assertSearchResults(
            [77],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'publish_date',
                            Criterion\Operator::CONTAINS,
                            1174643880
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterContainsSimpleNoMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'publish_date',
                            Criterion\Operator::CONTAINS,
                            1174643
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterBetween(): void
    {
        $this->assertSearchResults(
            [186, 187],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Field(
                            'publication_date',
                            Criterion\Operator::BETWEEN,
                            [1190000000, 1200000000]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFieldFilterOr(): void
    {
        $this->assertSearchResults(
            [11, 186, 187],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LogicalOr(
                            [
                                new Criterion\Field(
                                    'name',
                                    Criterion\Operator::EQ,
                                    'members'
                                ),
                                new Criterion\Field(
                                    'publication_date',
                                    Criterion\Operator::BETWEEN,
                                    [1190000000, 1200000000]
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextFilter(): void
    {
        $this->assertSearchResults(
            [191],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FullText('applied webpage'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextWildcardFilter(): void
    {
        $this->assertSearchResults(
            [191],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FullText('applie*'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextDisabledWildcardFilter(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler(
                ['enableWildcards' => false]
            )->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FullText('applie*'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextFilterStopwordRemoval(): void
    {
        $handler = $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 0.1,
            ]
        );

        $this->assertSearchResults(
            [],
            $handler->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FullText('the'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextFilterNoStopwordRemoval(): void
    {
        $handler = $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 1,
            ]
        );

        $result = $handler->findContent(
            new Query(
                [
                    'filter' => new Criterion\FullText(
                        'the'
                    ),
                    'limit' => 10,
                ]
            )
        );

        self::assertCount(
            10,
            array_map(
                static function ($hit) {
                    return $hit->valueObject->id;
                },
                $result->searchHits
            )
        );
    }

    public function testFullTextFilterInvalidStopwordThreshold(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 2,
            ]
        );
    }

    public function testObjectStateIdFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ObjectStateId(1),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testObjectStateIdFilterIn(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\ObjectStateId([1, 2]),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testLanguageCodeFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LanguageCode('eng-US'),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testLanguageCodeFilterIn(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LanguageCode(['eng-US', 'eng-GB']),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testLanguageCodeFilterWithAlwaysAvailable(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49, 50, 51, 56, 57, 65, 68, 70, 74, 76, 80],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\LanguageCode('eng-GB', true),
                        'limit' => 20,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testVisibilityFilter(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\Visibility(
                            Criterion\Visibility::VISIBLE
                        ),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterOwnerWrongUserId(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::OWNER,
                            Criterion\Operator::EQ,
                            2
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterOwnerAdministrator(): void
    {
        $this->assertSearchResults(
            [4, 10, 11, 12, 13, 14, 41, 42, 45, 49],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::OWNER,
                            Criterion\Operator::EQ,
                            14
                        ),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterOwnerEqAMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::OWNER,
                            Criterion\Operator::EQ,
                            226
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterOwnerInAMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::OWNER,
                            Criterion\Operator::IN,
                            [226]
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterCreatorEqAMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::MODIFIER,
                            Criterion\Operator::EQ,
                            226
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterCreatorInAMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::MODIFIER,
                            Criterion\Operator::IN,
                            [226]
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterEqGroupMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::GROUP,
                            Criterion\Operator::EQ,
                            11
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterInGroupMember(): void
    {
        $this->assertSearchResults(
            [223],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::GROUP,
                            Criterion\Operator::IN,
                            [11]
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterEqGroupMemberNoMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::GROUP,
                            Criterion\Operator::EQ,
                            13
                        ),
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterInGroupMemberNoMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::GROUP,
                            Criterion\Operator::IN,
                            [13]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterContainsSingle(): void
    {
        $this->assertSearchResults(
            [67],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::CONTAINS,
                            [60]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterContainsSingleNoMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::CONTAINS,
                            [4]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterContainsArray(): void
    {
        $this->assertSearchResults(
            [67],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::CONTAINS,
                            [60, 75]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterContainsArrayNotMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::CONTAINS,
                            [60, 64]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterInArray(): void
    {
        $this->assertSearchResults(
            [67, 75],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::IN,
                            [60, 64]
                        ),
                    ]
                )
            )
        );
    }

    public function testFieldRelationFilterInArrayNotMatch(): void
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findContent(
                new Query(
                    [
                        'filter' => new Criterion\FieldRelation(
                            'billboard',
                            Criterion\Operator::IN,
                            [4, 10]
                        ),
                    ]
                )
            )
        );
    }

    public function testGetNonExistingFieldDefinition(): void
    {
        $this->expectException(NotFoundException::class);

        $this->getContentTypeHandler()->getFieldDefinition(0, Type::STATUS_DEFINED);
    }
}
