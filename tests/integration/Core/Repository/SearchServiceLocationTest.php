<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\SortClause;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy;
use Ibexa\Core\Repository\Values\Content\Location;
use Ibexa\Tests\Solr\SetupFactory\LegacySetupFactory as LegacySolrSetupFactory;

/**
 * Test case for Location operations in the SearchService.
 *
 * @covers \Ibexa\Contracts\Core\Repository\SearchService
 *
 * @group integration
 * @group search
 */
class SearchServiceLocationTest extends BaseTestCase
{
    public const QUERY_CLASS = LocationQuery::class;

    /**
     * Create movie Content with subtitle field set to null.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content[]
     */
    protected function createMovieContent(): array
    {
        $movies = [];

        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('movie');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = 'movie-123';
        $createStruct->names = ['eng-GB' => 'Movie'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $fieldTitle = $contentTypeService->newFieldDefinitionCreateStruct('title', 'ibexa_string');
        $fieldTitle->names = ['eng-GB' => 'Title'];
        $fieldTitle->fieldGroup = 'main';
        $fieldTitle->position = 1;
        $fieldTitle->isTranslatable = false;
        $fieldTitle->isSearchable = true;
        $fieldTitle->isRequired = true;
        $createStruct->addFieldDefinition($fieldTitle);

        $fieldSubtitle = $contentTypeService->newFieldDefinitionCreateStruct('subtitle', 'ibexa_string');
        $fieldSubtitle->names = ['eng-GB' => 'Subtitle'];
        $fieldSubtitle->fieldGroup = 'main';
        $fieldSubtitle->position = 2;
        $fieldSubtitle->isTranslatable = false;
        $fieldSubtitle->isSearchable = true;
        $fieldSubtitle->isRequired = false;
        $createStruct->addFieldDefinition($fieldSubtitle);

        $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentTypeGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStructRambo = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructRambo->remoteId = 'movie-456';
        $createStructRambo->alwaysAvailable = false;
        $createStructRambo->setField('title', 'Rambo');
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $ramboDraft = $contentService->createContent($createStructRambo, [$locationCreateStruct]);
        $movies[] = $contentService->publishVersion($ramboDraft->getVersionInfo());
        $this->refreshSearch($repository);

        $createStructRobocop = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructRobocop->remoteId = 'movie-789';
        $createStructRobocop->alwaysAvailable = false;
        $createStructRobocop->setField('title', 'Robocop');
        $createStructRobocop->setField('subtitle', '');
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $robocopDraft = $contentService->createContent($createStructRobocop, [$locationCreateStruct]);
        $movies[] = $contentService->publishVersion($robocopDraft->getVersionInfo());
        $this->refreshSearch($repository);

        $createStructLastHope = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStructLastHope->remoteId = 'movie-101112';
        $createStructLastHope->alwaysAvailable = false;
        $createStructLastHope->setField('title', 'Star Wars');
        $createStructLastHope->setField('subtitle', 'Last Hope');
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $lastHopeDraft = $contentService->createContent($createStructLastHope, [$locationCreateStruct]);
        $movies[] = $contentService->publishVersion($lastHopeDraft->getVersionInfo());
        $this->refreshSearch($repository);

        return $movies;
    }

    /**
     * Create test Content with ibexa_country field having multiple countries selected.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createMultipleCountriesContent()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('countries-multiple');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->remoteId = 'countries-multiple-123';
        $createStruct->names = ['eng-GB' => 'Multiple countries'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $fieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('countries', 'ibexa_country');
        $fieldCreate->names = ['eng-GB' => 'Countries'];
        $fieldCreate->fieldGroup = 'main';
        $fieldCreate->position = 1;
        $fieldCreate->isTranslatable = false;
        $fieldCreate->isSearchable = true;
        $fieldCreate->fieldSettings = ['isMultiple' => true];

        $createStruct->addFieldDefinition($fieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->remoteId = 'countries-multiple-456';
        $createStruct->alwaysAvailable = false;
        $createStruct->setField(
            'countries',
            ['BE', 'DE', 'FR', 'HR', 'NO', 'PT', 'RU']
        );

        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createFolderWithNonPrintableUtf8Characters(): Content
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->remoteId = 'non-printable-char-folder-123';
        $createStruct->alwaysAvailable = false;
        $createStruct->setField(
            'name',
            mb_convert_encoding("Non\x09Printable\x0EFolder", 'ISO-8859-1', 'UTF-8')
        );

        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);
        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFieldIsEmptyInLocation()
    {
        $testContents = $this->createMovieContent();

        $query = new LocationQuery(
            [
                'query' => new Criterion\IsFieldEmpty('subtitle'),
                'sortClauses' => [new SortClause\Location\Id()],
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(2, $result->totalCount);

        self::assertEquals(
            $testContents[0]->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );

        self::assertEquals(
            $testContents[1]->contentInfo->mainLocationId,
            $result->searchHits[1]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFieldIsNotEmptyInLocation()
    {
        $testContents = $this->createMovieContent();

        $query = new LocationQuery(
            [
                'query' => new Criterion\IsFieldEmpty(
                    'subtitle',
                    false
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);

        self::assertEquals(
            $testContents[2]->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testFieldCollectionContains()
    {
        $testContent = $this->createMultipleCountriesContent();

        $query = new LocationQuery(
            [
                'query' => new Criterion\Field(
                    'countries',
                    Criterion\Operator::CONTAINS,
                    'Belgium'
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $testContent->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @depends Ibexa\Tests\Integration\Core\Repository\SearchServiceTest::testFieldCollectionContains
     */
    public function testFieldCollectionContainsNoMatch()
    {
        $this->createMultipleCountriesContent();
        $query = new LocationQuery(
            [
                'query' => new Criterion\Field(
                    'countries',
                    Criterion\Operator::CONTAINS,
                    'Netherlands Antilles'
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(0, $result->totalCount);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testNonPrintableUtf8Characters(): void
    {
        $folder = $this->createFolderWithNonPrintableUtf8Characters();
        $query = new LocationQuery(
            [
                'query' => new Criterion\Field(
                    'name',
                    Criterion\Operator::EQ,
                    mb_convert_encoding("Non\x09Printable\x0EFolder", 'ISO-8859-1', 'UTF-8')
                ),
            ]
        );

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $folder->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations
     *
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testEscapedNonPrintableUtf8Characters(): void
    {
        $setupFactory = $this->getSetupFactory();

        if (!$setupFactory instanceof LegacySolrSetupFactory) {
            self::markTestIncomplete(
                'Field Value mappers are used only with Solr and Elastic search engines'
            );
        }

        $query = new LocationQuery(
            [
                'query' => new Criterion\Field(
                    'name',
                    Criterion\Operator::EQ,
                    'Non PrintableFolder'
                ),
            ]
        );

        $repository = $this->getRepository();
        $this->createFolderWithNonPrintableUtf8Characters();
        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
    }

    public function testInvalidFieldIdentifierRange()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\Field(
                        'some_hopefully_unknown_field',
                        Criterion\Operator::BETWEEN,
                        [10, 1000]
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testInvalidFieldIdentifierIn()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findLocations(
            new LocationQuery(
                [
                    'filter' => new Criterion\Field(
                        'some_hopefully_unknown_field',
                        Criterion\Operator::EQ,
                        1000
                    ),
                    'sortClauses' => [new SortClause\ContentId()],
                ]
            )
        );
    }

    public function testFindLocationsWithNonSearchableField()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $searchService->findLocations(
            new LocationQuery(
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

    /**
     * @return array
     */
    protected function mapResultLocationIds(SearchResult $result): array
    {
        return array_map(
            static function (SearchHit $searchHit) {
                return $searchHit->valueObject->id;
            },
            $result->searchHits
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testQueryCustomField()
    {
        $query = new LocationQuery(
            [
                'query' => new Criterion\CustomField(
                    'custom_field',
                    Criterion\Operator::EQ,
                    'AdMiNiStRaToR'
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [new SortClause\ContentId()],
            ]
        );
        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . '/QueryCustomField.php',
            null,
            true
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * This tests explicitly queries the first_name while user is contained in
     * the last_name of admin and anonymous. This is done to show the custom
     * copy field working.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testQueryModifiedField()
    {
        // Check using get_class since the others extend SetupFactory\Legacy
        if ($this->getSetupFactory() instanceof Legacy) {
            self::markTestIncomplete(
                'Custom fields not supported by LegacySE ' .
                '(@todo: Legacy should fallback to just querying normal field so this should be tested here)'
            );
        }

        $query = new LocationQuery(
            [
                'query' => new Criterion\Field(
                    'first_name',
                    Criterion\Operator::EQ,
                    'User'
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [new SortClause\ContentId()],
            ]
        );
        self::assertInstanceOf(Criterion::class, $query->query);
        $query->query->setCustomField('user', 'first_name', 'custom_field');

        $this->assertQueryFixture(
            $query,
            $this->getFixtureDir() . '/QueryModifiedField.php',
            null,
            true
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     */
    protected function createTestPlaceContentType()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('testtype');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Test type'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new \DateTime();

        $translatableFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('maplocation', 'ibexa_gmap_location');
        $translatableFieldCreate->names = ['eng-GB' => 'Map location field'];
        $translatableFieldCreate->fieldGroup = 'main';
        $translatableFieldCreate->position = 1;
        $translatableFieldCreate->isTranslatable = false;
        $translatableFieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($translatableFieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType($createStruct, [$contentGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        return $contentType;
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceLessThanOrEqual()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::LTE,
                            240,
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $wildBoars->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceGreaterThanOrEqual()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            240,
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $tree->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceBetween()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::BETWEEN,
                            [239, 241],
                            43.756825,
                            15.775074
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $mushrooms->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceSortAscending()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $wellInVodice = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $wellInVodice['latitude'],
                            $wellInVodice['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\MapLocationDistance(
                        'testtype',
                        'maplocation',
                        $wellInVodice['latitude'],
                        $wellInVodice['longitude'],
                        LocationQuery::SORT_ASC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->contentInfo->mainLocationId,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->contentInfo->mainLocationId,
            $result->searchHits[2]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceSortDescending()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $well = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $well['latitude'],
                            $well['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    new SortClause\MapLocationDistance(
                        'testtype',
                        'maplocation',
                        $well['latitude'],
                        $well['longitude'],
                        LocationQuery::SORT_DESC
                    ),
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->contentInfo->mainLocationId,
            $result->searchHits[2]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->contentInfo->mainLocationId,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceWithCustomField()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $distanceCriterion = new Criterion\MapLocationDistance(
            'maplocation',
            Criterion\Operator::LTE,
            240,
            43.756825,
            15.775074
        );
        $distanceCriterion->setCustomField('testtype', 'maplocation', 'custom_geolocation_field');

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        $distanceCriterion,
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $wildBoars->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     *
     * @group maplocation
     */
    public function testMapLocationDistanceWithCustomFieldSort()
    {
        $contentType = $this->createTestPlaceContentType();

        // Create a draft to account for behaviour with ContentType in different states
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $contentTypeService->createContentTypeDraft($contentType);
        $locationCreateStruct = $repository->getLocationService()->newLocationCreateStruct(2);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.894877,
                'longitude' => 15.972699,
                'address' => 'Here be wild boars',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $wildBoars = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.927334,
                'longitude' => 15.934847,
                'address' => 'A lone tree',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $tree = $contentService->publishVersion($draft->getVersionInfo());

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->setField(
            'maplocation',
            [
                'latitude' => 45.903777,
                'longitude' => 15.958788,
                'address' => 'Meadow with mushrooms',
            ],
            'eng-GB'
        );

        $draft = $contentService->createContent($createStruct, [$locationCreateStruct]);
        $mushrooms = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        $well = [
            'latitude' => 43.756825,
            'longitude' => 15.775074,
        ];

        $sortClause = new SortClause\MapLocationDistance(
            'testtype',
            'maplocation',
            $well['latitude'],
            $well['longitude'],
            LocationQuery::SORT_DESC
        );
        $sortClause->setCustomField('testtype', 'maplocation', 'custom_geolocation_field');

        $query = new LocationQuery(
            [
                'filter' => new Criterion\LogicalAnd(
                    [
                        new Criterion\ContentTypeId($contentType->id),
                        new Criterion\MapLocationDistance(
                            'maplocation',
                            Criterion\Operator::GTE,
                            235,
                            $well['latitude'],
                            $well['longitude']
                        ),
                    ]
                ),
                'offset' => 0,
                'limit' => 10,
                'sortClauses' => [
                    $sortClause,
                ],
            ]
        );

        $searchService = $repository->getSearchService();
        $result = $searchService->findLocations($query);

        self::assertEquals(3, $result->totalCount);
        self::assertEquals(
            $wildBoars->contentInfo->mainLocationId,
            $result->searchHits[2]->valueObject->id
        );
        self::assertEquals(
            $mushrooms->contentInfo->mainLocationId,
            $result->searchHits[1]->valueObject->id
        );
        self::assertEquals(
            $tree->contentInfo->mainLocationId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test for the findLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\SearchService::findLocations()
     */
    public function testVisibilityCriterionWithHiddenContent()
    {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $testRootContentCreate = $contentService->newContentCreateStruct($contentType, 'eng-US');
        $testRootContentCreate->setField('name', 'Root for test');

        $rootContent = $contentService->createContent(
            $testRootContentCreate,
            [
                $locationService->newLocationCreateStruct(
                    $this->generateId('location', 2)
                ),
            ]
        );

        $publishedRootContent = $contentService->publishVersion($rootContent->versionInfo);

        $contentCreate = $contentService->newContentCreateStruct($contentType, 'eng-US');
        $contentCreate->setField('name', 'To Hide');

        $content = $contentService->createContent(
            $contentCreate,
            [
                $locationService->newLocationCreateStruct(
                    $publishedRootContent->contentInfo->mainLocationId
                ),
            ]
        );
        $publishedContent = $contentService->publishVersion($content->versionInfo);

        $childContentCreate = $contentService->newContentCreateStruct($contentType, 'eng-US');
        $childContentCreate->setField('name', 'Invisible Child');

        $childContent = $contentService->createContent(
            $childContentCreate,
            [
                $locationService->newLocationCreateStruct(
                    $publishedContent->contentInfo->mainLocationId
                ),
            ]
        );
        $rootLocation = $locationService->loadLocation($publishedRootContent->contentInfo->mainLocationId);

        $contentService->publishVersion($childContent->versionInfo);
        $this->refreshSearch($repository);

        $query = new LocationQuery([
            'query' => new Criterion\LogicalAnd([
                new Criterion\Visibility(
                    Criterion\Visibility::VISIBLE
                ),
                new Criterion\Subtree(
                    $rootLocation->pathString
                ),
            ]),
        ]);

        //Sanity check for visible locations
        $result = $searchService->findLocations($query);
        self::assertEquals(3, $result->totalCount);

        //Hide main content
        $contentService->hideContent($publishedContent->contentInfo);
        $this->refreshSearch($repository);

        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);

        //Query for invisible content
        $hiddenQuery = new LocationQuery([
            'query' => new Criterion\LogicalAnd([
                new Criterion\Visibility(
                    Criterion\Visibility::HIDDEN
                ),
                new Criterion\Subtree(
                    $rootLocation->pathString
                ),
            ]),
        ]);

        $result = $searchService->findLocations($hiddenQuery);
        self::assertEquals(2, $result->totalCount);
    }

    /**
     * Assert that query result matches the given fixture.
     *
     * @param string $fixture
     * @param callable|null $closure
     */
    protected function assertQueryFixture(LocationQuery $query, $fixture, $closure = null, $ignoreScore = true)
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        try {
            $result = $searchService->findLocations($query);
            $this->simplifySearchResult($result);
        } catch (NotImplementedException $e) {
            self::markTestSkipped(
                'This feature is not supported by the current search backend: ' . $e->getMessage()
            );
        }

        if (!is_file($fixture)) {
            if (isset($_ENV['ibexa_tests_record'])) {
                file_put_contents(
                    $record = $fixture . '.recording',
                    "<?php\n\nreturn " . var_export($result, true) . ";\n\n"
                );
                self::markTestIncomplete("No fixture available. Result recorded at $record. Result: \n" . $this->printResult($result));
            } else {
                self::markTestIncomplete("No fixture available. Set \$_ENV['ibexa_tests_record'] to generate:\n " . $fixture);
            }
        }

        $fixture = include $fixture;

        if ($closure !== null) {
            $closure($result);
        }

        if ($ignoreScore) {
            foreach ([$fixture, $result] as $result) {
                $property = new \ReflectionProperty(get_class($result), 'maxScore');
                $property->setAccessible(true);
                $property->setValue($result, 0.0);

                foreach ($result->searchHits as $hit) {
                    $property = new \ReflectionProperty(get_class($hit), 'score');
                    $property->setAccessible(true);
                    $property->setValue($hit, 0.0);
                }
            }
        }

        foreach ([$fixture, $result] as $set) {
            foreach ($set->searchHits as $hit) {
                $property = new \ReflectionProperty(get_class($hit), 'index');
                $property->setAccessible(true);
                $property->setValue($hit, null);

                $property = new \ReflectionProperty(get_class($hit), 'matchedTranslation');
                $property->setAccessible(true);
                $property->setValue($hit, null);
            }
        }

        self::assertEqualsWithDelta(
            $fixture,
            $result,
            .2, // Be quite generous regarding delay -- most important for scores
            'Search results do not match.',
        );
    }

    /**
     * Show a simplified view of the search result for manual introspection.
     *
     * @return string
     */
    protected function printResult(SearchResult $result): string
    {
        $printed = '';
        foreach ($result->searchHits as $hit) {
            $printed .= sprintf(" - %s (%s)\n", $hit->valueObject['title'], $hit->valueObject['id']);
        }

        return $printed;
    }

    /**
     * Simplify search result.
     *
     * This leads to saner comparisons of results, since we do not get the full
     * content objects every time.
     */
    protected function simplifySearchResult(SearchResult $result)
    {
        $result->time = 1;

        foreach ($result->searchHits as $hit) {
            switch (true) {
                case $hit->valueObject instanceof Location:
                    $hit->valueObject = [
                        'id' => $hit->valueObject->contentInfo->id,
                        'title' => $hit->valueObject->contentInfo->name,
                    ];
                    break;

                default:
                    throw new \RuntimeException('Unknown search result hit type: ' . get_class($hit->valueObject));
            }
        }
    }

    /**
     * Get fixture directory.
     *
     * @return string
     */
    protected function getFixtureDir(): string
    {
        return __DIR__ . '/_fixtures/' . getenv('fixtureDir') . '/';
    }
}
