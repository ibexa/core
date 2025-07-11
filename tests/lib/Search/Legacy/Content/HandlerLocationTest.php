<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Search\Legacy\Content;

use Ibexa\Contracts\Core\Persistence\Content\Location as SPILocation;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Core\Persistence;
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
class HandlerLocationTest extends AbstractTestCase
{
    /**
     * Returns the location search handler to test.
     *
     * This method returns a fully functional search handler to perform tests on.
     *
     * @param array $fullTextSearchConfiguration
     *
     * @return \Ibexa\Core\Search\Legacy\Content\Handler
     */
    protected function getContentSearchHandler(array $fullTextSearchConfiguration = [])
    {
        $ruleFiles = glob(dirname(__DIR__, 5) . '/src/lib/Resources/slug_converter/transformations/*.tr');
        self::assertNotFalse($ruleFiles, 'Failed to find transformation files');

        $transformationProcessor = new Persistence\TransformationProcessor\DefinitionBased(
            new Persistence\TransformationProcessor\DefinitionBased\Parser(),
            new Persistence\TransformationProcessor\PcreCompiler(
                new Persistence\Utf8Converter()
            ),
            $ruleFiles
        );
        $connection = $this->getDatabaseConnection();
        $commaSeparatedCollectionValueHandler = new CommonCriterionHandler\FieldValue\Handler\Collection(
            $connection,
            $transformationProcessor,
            ','
        );
        $hyphenSeparatedCollectionValueHandler = new CommonCriterionHandler\FieldValue\Handler\Collection(
            $connection,
            $transformationProcessor,
            '-'
        );
        $simpleValueHandler = new CommonCriterionHandler\FieldValue\Handler\Simple(
            $connection,
            $transformationProcessor
        );
        $compositeValueHandler = new CommonCriterionHandler\FieldValue\Handler\Composite(
            $connection,
            $transformationProcessor
        );

        return new Content\Handler(
            $this->createMock(ContentGateway::class),
            new Content\Location\Gateway\DoctrineDatabase(
                $connection,
                new CriteriaConverter(
                    [
                        new LocationCriterionHandler\LocationId($connection),
                        new LocationCriterionHandler\ParentLocationId($connection),
                        new LocationCriterionHandler\LocationRemoteId($connection),
                        new LocationCriterionHandler\Subtree($connection),
                        new LocationCriterionHandler\Visibility($connection),
                        new LocationCriterionHandler\Location\Depth($connection),
                        new LocationCriterionHandler\Location\Priority($connection),
                        new LocationCriterionHandler\Location\IsMainLocation($connection),
                        new CommonCriterionHandler\ContentId($connection),
                        new CommonCriterionHandler\ContentTypeGroupId($connection),
                        new CommonCriterionHandler\ContentTypeId($connection),
                        new CommonCriterionHandler\ContentTypeIdentifier(
                            $connection,
                            $this->getContentTypeHandler()
                        ),
                        new CommonCriterionHandler\DateMetadata($connection),
                        new CommonCriterionHandler\Field(
                            $connection,
                            $this->getContentTypeHandler(),
                            $this->getLanguageHandler(),
                            $this->getConverterRegistry(),
                            new CommonCriterionHandler\FieldValue\Converter(
                                new CommonCriterionHandler\FieldValue\HandlerRegistry(
                                    [
                                        'ibexa_boolean' => $simpleValueHandler,
                                        'ibexa_country' => $commaSeparatedCollectionValueHandler,
                                        'ibexa_date' => $simpleValueHandler,
                                        'ibexa_datetime' => $simpleValueHandler,
                                        'ibexa_email' => $simpleValueHandler,
                                        'ibexa_integer' => $simpleValueHandler,
                                        'ibexa_object_relation' => $simpleValueHandler,
                                        'ibexa_object_relation_list' => $commaSeparatedCollectionValueHandler,
                                        'ibexa_selection' => $hyphenSeparatedCollectionValueHandler,
                                        'ibexa_time' => $simpleValueHandler,
                                    ]
                                ),
                                $compositeValueHandler
                            ),
                            $transformationProcessor
                        ),
                        new CommonCriterionHandler\FullText(
                            $connection,
                            $transformationProcessor,
                            $this->getLanguageMaskGenerator(),
                            $fullTextSearchConfiguration
                        ),
                        new CommonCriterionHandler\LanguageCode(
                            $connection,
                            $this->getLanguageMaskGenerator()
                        ),
                        new CommonCriterionHandler\LogicalAnd($connection),
                        new CommonCriterionHandler\LogicalNot($connection),
                        new CommonCriterionHandler\LogicalOr($connection),
                        new CommonCriterionHandler\MapLocationDistance(
                            $connection,
                            $this->getContentTypeHandler(),
                            $this->getLanguageHandler()
                        ),
                        new CommonCriterionHandler\MatchAll($connection),
                        new CommonCriterionHandler\ObjectStateId($connection),
                        new CommonCriterionHandler\FieldRelation(
                            $connection,
                            $this->getContentTypeHandler(),
                            $this->getLanguageHandler()
                        ),
                        new CommonCriterionHandler\RemoteId($connection),
                        new CommonCriterionHandler\SectionId($connection),
                        new CommonCriterionHandler\UserMetadata($connection),
                    ]
                ),
                new SortClauseConverter(
                    [
                        new LocationSortClauseHandler\Location\Id($connection),
                        new CommonSortClauseHandler\ContentId($connection),
                    ]
                ),
                $this->getLanguageHandler()
            ),
            new Content\WordIndexer\Gateway\DoctrineDatabase(
                $this->getDatabaseConnection(),
                $this->getContentTypeHandler(),
                $transformationProcessor,
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

    public function testFindWithoutOffsetLimit()
    {
        $handler = $this->getContentSearchHandler();

        $searchResult = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId(2),
                ]
            )
        );

        self::assertEquals(1, $searchResult->totalCount);
        self::assertCount(1, $searchResult->searchHits);
    }

    public function testFindWithZeroLimit()
    {
        $handler = $this->getContentSearchHandler();

        $searchResult = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId(2),
                    'offset' => 0,
                    'limit' => 0,
                ]
            )
        );

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals([], $searchResult->searchHits);
    }

    /**
     * Issue with offsetting to the nonexistent results produces \ezcQueryInvalidParameterException exception.
     */
    public function testFindWithOffsetToNonexistent()
    {
        $handler = $this->getContentSearchHandler();

        $searchResult = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\LocationId(2),
                    'offset' => 1000,
                    'limit' => 1,
                ]
            )
        );

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals([], $searchResult->searchHits);
    }

    public function testLocationIdFilter()
    {
        $this->assertSearchResults(
            [12, 13],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LocationId(
                            [4, 12, 13]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testParentLocationIdFilter()
    {
        $this->assertSearchResults(
            [12, 13, 14, 44, 227],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ParentLocationId(5),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testLocationIdAndCombinatorFilter()
    {
        $this->assertSearchResults(
            [13],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\LocationId(
                                    [4, 12, 13]
                                ),
                                new Criterion\LocationId(
                                    [13, 44]
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testLocationIdParentLocationIdAndCombinatorFilter()
    {
        $this->assertSearchResults(
            [44, 160],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\LocationId(
                                    [2, 44, 160, 166]
                                ),
                                new Criterion\ParentLocationId(
                                    [5, 156]
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterEq()
    {
        $this->assertSearchResults(
            [2, 5, 43, 48, 58],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::EQ, 1),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterIn()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 43, 44, 48, 51, 52, 53, 54, 56, 58, 59, 69, 77, 86, 96, 107, 153, 156, 167, 190, 227],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::IN, [1, 2]),
                        'limit' => 50,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterBetween()
    {
        $this->assertSearchResults(
            [2, 5, 43, 48, 58],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::BETWEEN, [0, 1]),
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterGreaterThan()
    {
        $this->assertSearchResults(
            [99, 102, 135, 136, 137, 139, 140, 142, 143, 144, 145, 148, 151, 174, 175, 177, 194, 196, 197, 198, 199, 200, 201, 202, 203, 205, 206, 207, 208, 209, 210, 211, 212, 214, 215],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::GT, 4),
                        'limit' => 50,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterGreaterThanOrEqual()
    {
        $this->assertSearchResults(
            [99, 102, 135, 136, 137, 139, 140, 142, 143, 144, 145, 148, 151, 174, 175, 177, 194, 196, 197, 198, 199, 200, 201, 202, 203, 205, 206, 207, 208, 209, 210, 211, 212, 214, 215],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::GTE, 5),
                        'limit' => 50,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterLessThan()
    {
        $this->assertSearchResults(
            [2, 5, 43, 48, 58],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::LT, 2),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentDepthFilterLessThanOrEqual()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 43, 44, 48, 51, 52, 53, 54, 56, 58, 59, 69, 77, 86, 96, 107, 153, 156, 167, 190, 227],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Depth(Criterion\Operator::LTE, 2),
                        'limit' => 50,
                    ]
                )
            )
        );
    }

    public function testLocationPriorityFilter()
    {
        $this->assertSearchResults(
            [156, 167, 190],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Location\Priority(
                            Criterion\Operator::BETWEEN,
                            [1, 10]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testLocationRemoteIdFilter()
    {
        $this->assertSearchResults(
            [2, 5],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testVisibilityFilterVisible()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Visibility(
                            Criterion\Visibility::VISIBLE
                        ),
                        'limit' => 5,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testVisibilityFilterHidden()
    {
        $this->assertSearchResults(
            [228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\Visibility(
                            Criterion\Visibility::HIDDEN
                        ),
                    ]
                )
            )
        );
    }

    public function testLocationNotCombinatorFilter()
    {
        $this->assertSearchResults(
            [2, 5],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\LocationId(
                                    [2, 5, 12, 356]
                                ),
                                new Criterion\LogicalNot(
                                    new Criterion\LocationId(
                                        [12, 13, 14]
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

    public function testLocationOrCombinatorFilter()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalOr(
                            [
                                new Criterion\LocationId(
                                    [2, 5, 12]
                                ),
                                new Criterion\LocationId(
                                    [12, 13, 14]
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentIdFilterEquals()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ContentId(223),
                    ]
                )
            )
        );
    }

    public function testContentIdFilterIn()
    {
        $this->assertSearchResults(
            [225, 226, 227],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ContentId(
                            [223, 224, 225]
                        ),
                    ]
                )
            )
        );
    }

    public function testContentTypeGroupFilter()
    {
        $this->assertSearchResults(
            [5, 12, 13, 14, 15, 44, 45, 227, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ContentTypeGroupId(2),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentTypeIdFilter()
    {
        $this->assertSearchResults(
            [15, 45, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ContentTypeId(4),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testContentTypeIdentifierFilter()
    {
        $this->assertSearchResults(
            [43, 48, 51, 52, 53],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ContentTypeIdentifier('folder'),
                        'limit' => 5,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testObjectStateIdFilter()
    {
        $this->assertSearchResults(
            [5, 12, 13, 14, 15, 43, 44, 45, 48, 51],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ObjectStateId(1),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testObjectStateIdFilterIn()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\ObjectStateId([1, 2]),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testRemoteIdFilter()
    {
        $this->assertSearchResults(
            [5, 45],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testSectionFilter()
    {
        $this->assertSearchResults(
            [5, 12, 13, 14, 15, 44, 45, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\SectionId([2]),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testDateMetadataFilterModifiedGreater()
    {
        $this->assertSearchResults(
            [12, 227, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testDateMetadataFilterModifiedGreaterOrEqual()
    {
        $this->assertSearchResults(
            [12, 15, 227, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testDateMetadataFilterModifiedIn()
    {
        $this->assertSearchResults(
            [12, 15, 227, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testDateMetadataFilterModifiedBetween()
    {
        $this->assertSearchResults(
            [12, 15, 227, 228],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testDateMetadataFilterCreatedBetween()
    {
        $this->assertSearchResults(
            [68, 133, 227],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterOwnerWrongUserId()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterOwnerAdministrator()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\UserMetadata(
                            Criterion\UserMetadata::OWNER,
                            Criterion\Operator::EQ,
                            14
                        ),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testUserMetadataFilterOwnerEqAMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterOwnerInAMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterCreatorEqAMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterCreatorInAMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterEqGroupMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterInGroupMember()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterEqGroupMemberNoMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testUserMetadataFilterInGroupMemberNoMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testLanguageCodeFilter()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LanguageCode('eng-US'),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testLanguageCodeFilterIn()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LanguageCode(['eng-US', 'eng-GB']),
                        'limit' => 10,
                        'sortClauses' => [new SortClause\Location\Id()],
                    ]
                )
            )
        );
    }

    public function testLanguageCodeFilterWithAlwaysAvailable()
    {
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48, 51, 52, 53, 58, 59, 70, 72, 76, 78, 82],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LanguageCode('eng-GB', true),
                        'limit' => 20,
                        'sortClauses' => [new SortClause\ContentId()],
                    ]
                )
            )
        );
    }

    public function testMatchAllFilter()
    {
        $result = $this->getContentSearchHandler()->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\MatchAll(),
                    'limit' => 10,
                    'sortClauses' => [new SortClause\Location\Id()],
                ]
            )
        );

        self::assertCount(10, $result->searchHits);
        self::assertEquals(186, $result->totalCount);
        $this->assertSearchResults(
            [2, 5, 12, 13, 14, 15, 43, 44, 45, 48],
            $result
        );
    }

    public function testFullTextFilter()
    {
        $this->assertSearchResults(
            [193],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\FullText('applied webpage'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextWildcardFilter()
    {
        $this->assertSearchResults(
            [193],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\FullText('applie*'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextDisabledWildcardFilter()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler(['enableWildcards' => false])->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\FullText('applie*'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextFilterStopwordRemoval()
    {
        $handler = $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 0.1,
            ]
        );
        $this->assertSearchResults(
            [],
            $handler->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\FullText('the'),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testFullTextFilterNoStopwordRemoval()
    {
        $handler = $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 1,
            ]
        );

        $result = $handler->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\FullText(
                        'the'
                    ),
                    'limit' => 10,
                ]
            )
        );

        self::assertEquals(26, $result->totalCount);
        self::assertCount(10, $result->searchHits);
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

    public function testFullTextFilterInvalidStopwordThreshold()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->getContentSearchHandler(
            [
                'stopWordThresholdFactor' => 2,
            ]
        );
    }

    public function testFieldRelationFilterContainsSingle()
    {
        $this->assertSearchResults(
            [69],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldRelationFilterContainsSingleNoMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldRelationFilterContainsArray()
    {
        $this->assertSearchResults(
            [69],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldRelationFilterContainsArrayNotMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldRelationFilterInArray()
    {
        $this->assertSearchResults(
            [69, 77],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldRelationFilterInArrayNotMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilter()
    {
        $this->assertSearchResults(
            [12],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterIn()
    {
        $this->assertSearchResults(
            [12, 44],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterContainsPartial()
    {
        $this->assertSearchResults(
            [44],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterContainsSimple()
    {
        $this->assertSearchResults(
            [79],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterContainsSimpleNoMatch()
    {
        $this->assertSearchResults(
            [],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterBetween()
    {
        $this->assertSearchResults(
            [188, 189],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testFieldFilterOr()
    {
        $this->assertSearchResults(
            [12, 188, 189],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
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

    public function testIsMainLocationFilter()
    {
        $this->assertSearchResults(
            [225],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\ParentLocationId(224),
                                new Criterion\Location\IsMainLocation(
                                    Criterion\Location\IsMainLocation::MAIN
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }

    public function testIsNotMainLocationFilter()
    {
        $this->assertSearchResults(
            [510],
            $this->getContentSearchHandler()->findLocations(
                new LocationQuery(
                    [
                        'filter' => new Criterion\LogicalAnd(
                            [
                                new Criterion\ParentLocationId(224),
                                new Criterion\Location\IsMainLocation(
                                    Criterion\Location\IsMainLocation::NOT_MAIN
                                ),
                            ]
                        ),
                        'limit' => 10,
                    ]
                )
            )
        );
    }
}
