<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\URLAliasService as URLAliasServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationList;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchHit;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Core\Persistence\Legacy\Content\Location\Gateway;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;

/**
 * Test case for operations in the LocationService using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\LocationService
 *
 * @group location
 */
class LocationServiceTest extends BaseTestCase
{
    /**
     * Test for the newLocationCreateStruct() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::newLocationCreateStruct()
     */
    public function testNewLocationCreateStruct()
    {
        $repository = $this->getRepository();

        $parentLocationId = $this->generateId('location', 1);
        /* BEGIN: Use Case */
        // $parentLocationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $locationCreate = $locationService->newLocationCreateStruct(
            $parentLocationId
        );
        /* END: Use Case */

        self::assertInstanceOf(
            LocationCreateStruct::class,
            $locationCreate
        );

        return $locationCreate;
    }

    /**
     * Test for the newLocationCreateStruct() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationCreateStruct $locationCreate
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::newLocationCreateStruct()
     *
     * @depends testNewLocationCreateStruct
     */
    public function testNewLocationCreateStructValues(LocationCreateStruct $locationCreate)
    {
        $this->assertPropertiesCorrect(
            [
                'priority' => 0,
                'hidden' => false,
                // remoteId should be initialized with a default value
                //'remoteId' => null,
                'sortField' => null,
                'sortOrder' => null,
                'parentLocationId' => $this->generateId('location', 1),
            ],
            $locationCreate
        );
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testNewLocationCreateStruct
     */
    public function testCreateLocation()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
        $locationCreate->priority = 23;
        $locationCreate->hidden = true;
        $locationCreate->remoteId = 'sindelfingen';
        $locationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate->sortOrder = Location::SORT_ORDER_DESC;

        $location = $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $location
        );

        return [
            'locationCreate' => $locationCreate,
            'createdLocation' => $location,
            'contentInfo' => $contentInfo,
            'parentLocation' => $locationService->loadLocation($this->generateId('location', 5)),
        ];
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation
     *
     * @depends testCreateLocation
     * @depends Ibexa\Tests\Integration\Core\Repository\ContentServiceTest::testHideContent
     */
    public function testCreateLocationChecksContentVisibility(): void
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);
        $contentService->hideContent($contentInfo);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
        $locationCreate->priority = 23;
        $locationCreate->hidden = false;
        $locationCreate->remoteId = 'sindelfingen';
        $locationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate->sortOrder = Location::SORT_ORDER_DESC;

        $location = $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */

        self::assertInstanceOf(Location::class, $location);

        self::assertTrue($location->invisible);
    }

    /**
     * Test for the createLocation() method with utilizing default ContentType sorting options.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation
     */
    public function testCreateLocationWithContentTypeSortingOptions(): void
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        // ContentType loading
        $contentType = $contentTypeService->loadContentType($contentInfo->contentTypeId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
        $locationCreate->priority = 23;
        $locationCreate->hidden = true;
        $locationCreate->remoteId = 'sindelfingen';

        $location = $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );

        self::assertEquals($contentType->defaultSortField, $location->sortField);
        self::assertEquals($contentType->defaultSortOrder, $location->sortOrder);
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testCreateLocation
     */
    public function testCreateLocationStructValues(array $data)
    {
        $locationCreate = $data['locationCreate'];
        $createdLocation = $data['createdLocation'];
        $contentInfo = $data['contentInfo'];

        $this->assertPropertiesCorrect(
            [
                'priority' => $locationCreate->priority,
                'hidden' => $locationCreate->hidden,
                'invisible' => $locationCreate->hidden,
                'remoteId' => $locationCreate->remoteId,
                'contentInfo' => $contentInfo,
                'parentLocationId' => $locationCreate->parentLocationId,
                'pathString' => '/1/5/' . $this->parseId('location', $createdLocation->id) . '/',
                'depth' => 2,
                'sortField' => $locationCreate->sortField,
                'sortOrder' => $locationCreate->sortOrder,
            ],
            $createdLocation
        );

        self::assertNotNull($createdLocation->id);
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testNewLocationCreateStruct
     */
    public function testCreateLocationThrowsInvalidArgumentExceptionContentAlreadyBelowParent()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 11);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location which already
        // has the content assigned to one of its descendant locations
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);

        // Throws exception, since content is already located at "/1/2/107/110/"
        $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testNewLocationCreateStruct
     */
    public function testCreateLocationThrowsInvalidArgumentExceptionParentIsSubLocationOfContent()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 4);
        $parentLocationId = $this->generateId('location', 12);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location which is below a
        // location that is assigned to the content
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);

        // Throws exception, since content is already located at "/1/2/"
        $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testNewLocationCreateStruct
     */
    public function testCreateLocationThrowsInvalidArgumentExceptionRemoteIdExists()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
        // This remote ID already exists
        $locationCreate->remoteId = 'f3e90596361e31d496d4026eb624c983';

        // Throws exception, since remote ID is already in use
        $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testNewLocationCreateStruct
     *
     * @dataProvider dataProviderForOutOfRangeLocationPriority
     */
    public function testCreateLocationThrowsInvalidArgumentExceptionPriorityIsOutOfRange($priority)
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // ContentInfo for "How to use Ibexa"
        $contentInfo = $contentService->loadContentInfo($contentId);

        $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
        $locationCreate->priority = $priority;
        $locationCreate->hidden = true;
        $locationCreate->remoteId = 'sindelfingen';
        $locationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate->sortOrder = Location::SORT_ORDER_DESC;

        // Throws exception, since priority is out of range
        $locationService->createLocation(
            $contentInfo,
            $locationCreate
        );
        /* END: Use Case */
    }

    public function dataProviderForOutOfRangeLocationPriority()
    {
        return [[-2147483649], [2147483648]];
    }

    /**
     * Test for the createLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::createLocation()
     *
     * @depends testCreateLocation
     */
    public function testCreateLocationInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 41);
        $parentLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $contentId is the ID of an existing content object
        // $parentLocationId is the ID of an existing location
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $repository->beginTransaction();

        try {
            // ContentInfo for "How to use Ibexa"
            $contentInfo = $contentService->loadContentInfo($contentId);

            $locationCreate = $locationService->newLocationCreateStruct($parentLocationId);
            $locationCreate->remoteId = 'sindelfingen';

            $createdLocationId = $locationService->createLocation(
                $contentInfo,
                $locationCreate
            )->id;
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $repository->rollback();

        try {
            // Throws exception since creation of location was rolled back
            $location = $locationService->loadLocation($createdLocationId);
        } catch (NotFoundException $e) {
            return;
        }
        /* END: Use Case */

        self::fail('Objects still exists after rollback.');
    }

    /**
     * Test for the loadLocation() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocation
     *
     * @depends testCreateLocation
     */
    public function testLoadLocation()
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($locationId);
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $location
        );
        self::assertEquals(5, $location->id);

        return $location;
    }

    /**
     * Test for the loadLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocation()
     *
     * @depends testLoadLocation
     */
    public function testLoadLocationRootStructValues()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $location = $locationService->loadLocation($this->generateId('location', 1));

        $this->assertRootLocationStructValues($location);
    }

    public function testLoadLocationRootStructValuesWithPrioritizedLanguages(): void
    {
        $repository = $this->getRepository();

        $rootLocation = $repository
            ->getLocationService()
            ->loadLocation(
                $this->generateId('location', 1),
                [
                    'eng-GB',
                    'ger-DE',
                ]
            );

        $this->assertRootLocationStructValues($rootLocation);
    }

    private function assertRootLocationStructValues(Location $location): void
    {
        $legacyDateTime = new \DateTime();
        $legacyDateTime->setTimestamp(1030968000);

        self::assertInstanceOf(Location::class, $location);
        $this->assertPropertiesCorrect(
            [
                'id' => $this->generateId('location', 1),
                'status' => 1,
                'priority' => 0,
                'hidden' => false,
                'invisible' => false,
                'remoteId' => '629709ba256fe317c3ddcee35453a96a',
                'parentLocationId' => $this->generateId('location', 1),
                'pathString' => '/1/',
                'depth' => 0,
                'sortField' => 1,
                'sortOrder' => 1,
            ],
            $location
        );

        self::assertInstanceOf(ContentInfo::class, $location->contentInfo);
        $this->assertPropertiesCorrect(
            [
                'id' => $this->generateId('content', 0),
                'name' => 'Top Level Nodes',
                'sectionId' => 1,
                'mainLocationId' => 1,
                'contentTypeId' => 1,
                'currentVersionNo' => 1,
                'published' => 1,
                'ownerId' => 14,
                'modificationDate' => $legacyDateTime,
                'publishedDate' => $legacyDateTime,
                'alwaysAvailable' => 1,
                'remoteId' => 'IBEXA_ROOT_385b2cd4737a459c999ba4b7595a0016',
                'mainLanguageCode' => 'eng-GB',
            ],
            $location->contentInfo
        );
    }

    /**
     * Test for the loadLocation() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocation()
     *
     * @depends testLoadLocation
     */
    public function testLoadLocationStructValues(Location $location)
    {
        $this->assertPropertiesCorrect(
            [
                'id' => $this->generateId('location', 5),
                'priority' => 0,
                'hidden' => false,
                'invisible' => false,
                'remoteId' => '3f6d92f8044aed134f32153517850f5a',
                'parentLocationId' => $this->generateId('location', 1),
                'pathString' => '/1/5/',
                'depth' => 1,
                'sortField' => 1,
                'sortOrder' => 1,
            ],
            $location
        );

        self::assertInstanceOf(ContentInfo::class, $location->contentInfo);
        self::assertEquals($this->generateId('object', 4), $location->contentInfo->id);

        self::assertInstanceOf(Location::class, $location->getParentLocation());
        self::assertEquals($this->generateId('location', 1), $location->getParentLocation()->id);

        // Check lazy loaded proxy on ->content
        self::assertInstanceOf(
            Content::class,
            $content = $location->getContent()
        );
        self::assertEquals(4, $content->contentInfo->id);
    }

    public function testLoadLocationPrioritizedLanguagesFallback()
    {
        $repository = $this->getRepository();

        // Add a language
        $this->createLanguage('nor-NO', 'Norsk');

        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $location = $locationService->loadLocation(5);

        // Translate "Users"
        $draft = $contentService->createContentDraft($location->contentInfo);
        $struct = $contentService->newContentUpdateStruct();
        $struct->setField('name', 'Brukere', 'nor-NO');
        $draft = $contentService->updateContent($draft->getVersionInfo(), $struct);
        $contentService->publishVersion($draft->getVersionInfo());

        // Load with priority language (fallback will be the old one)
        $location = $locationService->loadLocation(5, ['nor-NO']);

        self::assertInstanceOf(
            Location::class,
            $location
        );
        self::assertEquals(5, $location->id);
        self::assertInstanceOf(
            Content::class,
            $content = $location->getContent()
        );
        self::assertEquals(4, $content->contentInfo->id);

        self::assertEquals($content->getVersionInfo()->getName(), 'Brukere');
        self::assertEquals($content->getVersionInfo()->getName('eng-US'), 'Users');
    }

    /**
     * Test that accessing lazy-loaded Content without a translation in the specific
     * not available language throws NotFoundException.
     */
    public function testLoadLocationThrowsNotFoundExceptionForNotAvailableContent(): void
    {
        $repository = $this->getRepository();

        $locationService = $repository->getLocationService();

        $this->createLanguage('pol-PL', 'Polski');

        $this->expectException(NotFoundException::class);

        // Note: relying on existing database fixtures to make test case more readable
        $locationService->loadLocation(60, ['pol-PL']);
    }

    /**
     * Test for the loadLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocation()
     *
     * @depends testCreateLocation
     */
    public function testLoadLocationThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistentLocationId = $this->generateId('location', 2342);
        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();

        // Throws exception, if Location with $nonExistentLocationId does not
        // exist
        $location = $locationService->loadLocation($nonExistentLocationId);
        /* END: Use Case */
    }

    /**
     * Test for the loadLocationList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationList
     */
    public function testLoadLocationList(): void
    {
        $repository = $this->getRepository();

        // 5 is the ID of an existing location, 442 is a non-existing id
        $locationService = $repository->getLocationService();
        $locations = iterator_to_array($locationService->loadLocationList([5, 442]));

        self::assertIsIterable($locations);
        self::assertCount(1, $locations);
        self::assertEquals([5], array_keys($locations));
        self::assertInstanceOf(Location::class, $locations[5]);
        self::assertEquals(5, $locations[5]->id);
    }

    /**
     * Test for the loadLocationList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationList
     *
     * @depends testLoadLocationList
     */
    public function testLoadLocationListPrioritizedLanguagesFallback(): void
    {
        $repository = $this->getRepository();

        $this->createLanguage('pol-PL', 'Polski');

        // 5 is the ID of an existing location, 442 is a non-existing id
        $locationService = $repository->getLocationService();
        $locations = $locationService->loadLocationList([5, 442], ['pol-PL'], false);

        self::assertIsIterable($locations);
        self::assertCount(0, $locations);
    }

    /**
     * Test for the loadLocationList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationList
     *
     * @depends testLoadLocationListPrioritizedLanguagesFallback
     */
    public function testLoadLocationListPrioritizedLanguagesFallbackAndAlwaysAvailable(): void
    {
        $repository = $this->getRepository();

        $this->createLanguage('pol-PL', 'Polski');

        // 5 is the ID of an existing location, 442 is a non-existing id
        $locationService = $repository->getLocationService();
        $locations = iterator_to_array($locationService->loadLocationList([5, 442], ['pol-PL'], true));

        self::assertIsIterable($locations);
        self::assertCount(1, $locations);
        self::assertEquals([5], array_keys($locations));
        self::assertInstanceOf(Location::class, $locations[5]);
        self::assertEquals(5, $locations[5]->id);
    }

    /**
     * Test for the loadLocationList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationList
     */
    public function testLoadLocationListWithRootLocationId()
    {
        $repository = $this->getRepository();

        // 1 is the ID of an root location
        $locationService = $repository->getLocationService();
        $locations = iterator_to_array($locationService->loadLocationList([1]));

        self::assertIsIterable($locations);
        self::assertCount(1, $locations);
        self::assertEquals([1], array_keys($locations));
        self::assertInstanceOf(Location::class, $locations[1]);
        self::assertEquals(1, $locations[1]->id);
    }

    /**
     * Test for the loadLocationList() method.
     *
     * Ensures the list is returned in the same order as passed IDs array.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationList
     */
    public function testLoadLocationListInCorrectOrder()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $cachedLocationId = 2;
        $locationIdsToLoad = [43, $cachedLocationId, 5];

        // Call loadLocation to cache it in memory as it might possibly affect list order
        $locationService->loadLocation($cachedLocationId);

        $locations = iterator_to_array($locationService->loadLocationList($locationIdsToLoad));
        $locationIds = array_column($locations, 'id');

        self::assertEquals($locationIdsToLoad, $locationIds);
    }

    /**
     * Test for the loadLocationByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationByRemoteId()
     *
     * @depends testLoadLocation
     */
    public function testLoadLocationByRemoteId()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocationByRemoteId(
            '3f6d92f8044aed134f32153517850f5a'
        );
        /* END: Use Case */

        self::assertEquals(
            $locationService->loadLocation($this->generateId('location', 5)),
            $location
        );
    }

    /**
     * Test for the loadLocationByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationByRemoteId()
     *
     * @depends testLoadLocation
     */
    public function testLoadLocationByRemoteIdThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();

        // Throws exception, since Location with remote ID does not exist
        $location = $locationService->loadLocationByRemoteId(
            'not-exists'
        );
        /* END: Use Case */
    }

    /**
     * Test for the loadLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations()
     *
     * @depends testCreateLocation
     */
    public function testLoadLocations()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', 4);
        /* BEGIN: Use Case */
        // $contentId contains the ID of an existing content object
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $contentInfo = $contentService->loadContentInfo($contentId);

        $locations = $locationService->loadLocations($contentInfo);
        /* END: Use Case */

        self::assertIsArray($locations);
        self::assertNotEmpty($locations);

        foreach ($locations as $location) {
            self::assertInstanceOf(Location::class, $location);
            self::assertEquals($contentInfo->id, $location->getContentInfo()->id);
        }

        return $locations;
    }

    /**
     * Test for the loadLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations()
     *
     * @depends testLoadLocations
     */
    public function testLoadLocationsContent(array $locations)
    {
        self::assertCount(1, $locations);
        foreach ($locations as $loadedLocation) {
            self::assertInstanceOf(Location::class, $loadedLocation);
        }

        usort(
            $locations,
            static function ($a, $b): int {
                return strcmp($a->id, $b->id);
            }
        );

        self::assertEquals(
            [$this->generateId('location', 5)],
            array_map(
                static function (Location $location) {
                    return $location->id;
                },
                $locations
            )
        );
    }

    /**
     * Test for the loadLocations() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location[]
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations($contentInfo, $rootLocation)
     *
     * @depends testLoadLocations
     */
    public function testLoadLocationsLimitedSubtree()
    {
        $repository = $this->getRepository();

        $originalLocationId = $this->generateId('location', 54);
        $originalParentLocationId = $this->generateId('location', 48);
        $newParentLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $originalLocationId is the ID of an existing location
        // $originalParentLocationId is the ID of the parent location of
        //     $originalLocationId
        // $newParentLocationId is the ID of an existing location outside the tree
        // of $originalLocationId and $originalParentLocationId
        $locationService = $repository->getLocationService();

        // Location at "/1/48/54"
        $originalLocation = $locationService->loadLocation($originalLocationId);

        // Create location under "/1/43/"
        $locationCreate = $locationService->newLocationCreateStruct($newParentLocationId);
        $locationService->createLocation(
            $originalLocation->contentInfo,
            $locationCreate
        );

        $findRootLocation = $locationService->loadLocation($originalParentLocationId);

        // Returns an array with only $originalLocation
        $locations = $locationService->loadLocations(
            $originalLocation->contentInfo,
            $findRootLocation
        );
        /* END: Use Case */

        self::assertIsArray($locations);

        return $locations;
    }

    /**
     * Test for the loadLocations() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location[] $locations
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations()
     *
     * @depends testLoadLocationsLimitedSubtree
     */
    public function testLoadLocationsLimitedSubtreeContent(array $locations)
    {
        self::assertCount(1, $locations);

        self::assertEquals(
            $this->generateId('location', 54),
            reset($locations)->id
        );
    }

    /**
     * Test for the loadLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations()
     *
     * @depends testLoadLocations
     */
    public function testLoadLocationsThrowsBadStateException()
    {
        $this->expectException(BadStateException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // Create new content, which is not published
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreate = $contentService->newContentCreateStruct($folderType, 'eng-US');
        $contentCreate->setField('name', 'New Folder');
        $content = $contentService->createContent($contentCreate);

        // Throws Exception, since $content has no published version, yet
        $locationService->loadLocations(
            $content->contentInfo
        );
        /* END: Use Case */
    }

    /**
     * Test for the loadLocations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocations($contentInfo, $rootLocation)
     *
     * @depends testLoadLocations
     */
    public function testLoadLocationsThrowsBadStateExceptionLimitedSubtree()
    {
        $this->expectException(BadStateException::class);

        $repository = $this->getRepository();

        $someLocationId = $this->generateId('location', 2);
        /* BEGIN: Use Case */
        // $someLocationId is the ID of an existing location
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // Create new content, which is not published
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreate = $contentService->newContentCreateStruct($folderType, 'eng-US');
        $contentCreate->setField('name', 'New Folder');
        $content = $contentService->createContent($contentCreate);

        $findRootLocation = $locationService->loadLocation($someLocationId);

        // Throws Exception, since $content has no published version, yet
        $locationService->loadLocations(
            $content->contentInfo,
            $findRootLocation
        );
        /* END: Use Case */
    }

    /**
     * Test for the loadLocationChildren() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     *
     * @depends testLoadLocation
     */
    public function testLoadLocationChildren()
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($locationId);

        $childLocations = $locationService->loadLocationChildren($location);
        /* END: Use Case */

        self::assertInstanceOf(LocationList::class, $childLocations);
        self::assertIsArray($childLocations->locations);
        self::assertNotEmpty($childLocations->locations);
        self::assertIsInt($childLocations->totalCount);

        foreach ($childLocations->locations as $childLocation) {
            self::assertInstanceOf(Location::class, $childLocation);
            self::assertEquals($location->id, $childLocation->parentLocationId);
        }

        return $childLocations;
    }

    /**
     * Test loading parent Locations for draft Content.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadParentLocationsForDraftContent
     */
    public function testLoadParentLocationsForDraftContent()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();

        // prepare locations
        $locationCreateStructs = [
            $locationService->newLocationCreateStruct(2),
            $locationService->newLocationCreateStruct(5),
        ];

        // Create new content
        $folderType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreate = $contentService->newContentCreateStruct($folderType, 'eng-US');
        $contentCreate->setField('name', 'New Folder');
        $contentDraft = $contentService->createContent($contentCreate, $locationCreateStructs);

        // Test loading parent Locations
        $locations = $locationService->loadParentLocationsForDraftContent($contentDraft->versionInfo);

        self::assertCount(2, $locations);
        foreach ($locations as $location) {
            // test it is one of the given parent locations
            self::assertTrue($location->id === 2 || $location->id === 5);
        }

        return $contentDraft;
    }

    /**
     * Test that trying to load parent Locations throws Exception if Content is not a draft.
     *
     * @depends testLoadParentLocationsForDraftContent
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $contentDraft
     */
    public function testLoadParentLocationsForDraftContentThrowsBadStateException(Content $contentDraft)
    {
        $this->expectException(BadStateException::class);
        $this->expectExceptionMessageMatches('/is already published/');

        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $content = $contentService->publishVersion($contentDraft->versionInfo);

        $locationService->loadParentLocationsForDraftContent($content->versionInfo);
    }

    /**
     * Test for the getLocationChildCount() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::getLocationChildCount()
     *
     * @depends testLoadLocation
     */
    public function testGetLocationChildCount()
    {
        // $locationId is the ID of an existing location
        $locationService = $this->getRepository()->getLocationService();

        self::assertSame(
            5,
            $locationService->getLocationChildCount(
                $locationService->loadLocation($this->generateId('location', 5))
            )
        );
    }

    /**
     * Test for the loadLocationChildren() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren()
     *
     * @depends testLoadLocationChildren
     */
    public function testLoadLocationChildrenData(LocationList $locations)
    {
        self::assertCount(5, $locations->locations);
        self::assertEquals(5, $locations->getTotalCount());

        foreach ($locations->locations as $location) {
            self::assertInstanceOf(
                Location::class,
                $location
            );
        }

        self::assertEquals(
            [
                $this->generateId('location', 12),
                $this->generateId('location', 13),
                $this->generateId('location', 14),
                $this->generateId('location', 44),
                $this->generateId('location', 61),
            ],
            array_map(
                static function (Location $location) {
                    return $location->id;
                },
                $locations->locations
            )
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     */
    public function testLoadLocationChildrenWithOffset(): LocationList
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($locationId);

        $childLocations = $locationService->loadLocationChildren($location, 2);

        self::assertIsIterable($childLocations);
        self::assertIsInt($childLocations->totalCount);

        return $childLocations;
    }

    /**
     * Test for the loadLocationChildren() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\LocationList $locations
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     *
     * @depends testLoadLocationChildrenWithOffset
     */
    public function testLoadLocationChildrenDataWithOffset(LocationList $locations): void
    {
        self::assertCount(3, $locations->locations);
        self::assertEquals(5, $locations->getTotalCount());

        $actualLocationIds = [];
        foreach ($locations->locations as $location) {
            self::assertInstanceOf(Location::class, $location);
            $actualLocationIds[] = $location->id;
        }

        self::assertEquals(
            [
                $this->generateId('location', 14),
                $this->generateId('location', 44),
                $this->generateId('location', 61),
            ],
            $actualLocationIds
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     *
     * @depends testLoadLocationChildren
     */
    public function testLoadLocationChildrenWithOffsetAndLimit(): LocationList
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($locationId);

        $childLocations = $locationService->loadLocationChildren($location, 2, 2);

        self::assertIsArray($childLocations->locations);
        self::assertIsInt($childLocations->totalCount);

        return $childLocations;
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     *
     * @depends testLoadLocationChildrenWithOffsetAndLimit
     */
    public function testLoadLocationChildrenDataWithOffsetAndLimit(LocationList $locations): void
    {
        self::assertCount(2, $locations->locations);
        self::assertEquals(5, $locations->getTotalCount());

        $actualLocationIds = [];
        foreach ($locations->locations as $location) {
            self::assertInstanceOf(Location::class, $location);
            $actualLocationIds[] = $location->id;
        }

        self::assertEquals(
            [
                $this->generateId('location', 14),
                $this->generateId('location', 44),
            ],
            $actualLocationIds
        );
    }

    public function providerForLoadLocationChildrenRespectsParentSortingClauses(): iterable
    {
        yield 'Name_ASC' => [
            Location::SORT_FIELD_NAME,
            Location::SORT_ORDER_ASC,
            ['A', 'B', 'C', 'Test'],
        ];

        yield 'Name_DESC' => [
            Location::SORT_FIELD_NAME,
            Location::SORT_ORDER_DESC,
            ['Test', 'C', 'B', 'A'],
        ];

        yield 'Priority_ASC' => [
            Location::SORT_FIELD_PRIORITY,
            Location::SORT_ORDER_ASC,
            ['A', 'C', 'B', 'Test'],
        ];

        yield 'Priority_DESC' => [
            Location::SORT_FIELD_PRIORITY,
            Location::SORT_ORDER_DESC,
            ['Test', 'B', 'C', 'A'],
        ];

        yield 'Path_ASC' => [
            Location::SORT_FIELD_PATH,
            Location::SORT_ORDER_ASC,
            ['A', 'C', 'B', 'Test'],
        ];

        yield 'Path_DESC' => [
            Location::SORT_FIELD_PATH,
            Location::SORT_ORDER_DESC,
            ['Test', 'B', 'C', 'A'],
        ];
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    private function createStructureForTestLoadLocationChildrenRespectsParentSortingClauses(): Location
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();

        // Firstly, create a container folder
        $rootLocation = $locationService->loadLocation(1);
        $createStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $createStruct->setField('name', 'Parent folder');
        $content = $contentService->publishVersion(
            $contentService->createContent(
                $createStruct,
                [$locationService->newLocationCreateStruct($rootLocation->id)]
            )->versionInfo
        );

        // Secondly, create child folders that would be sorted later on
        $contentNames = ['A', 'C', 'B', 'Test'];
        $priority = 1;
        foreach ($contentNames as $contentName) {
            $rootLocation = $locationService->loadLocation($content->contentInfo->mainLocationId);
            $createStruct = $contentService->newContentCreateStruct(
                $contentTypeService->loadContentTypeByIdentifier('folder'),
                'eng-GB'
            );
            $createStruct->setField('name', $contentName);

            $locationCreateStruct = $locationService->newLocationCreateStruct($rootLocation->id);
            $locationCreateStruct->priority = $priority;
            $contentService->publishVersion(
                $contentService->createContent(
                    $createStruct,
                    [$locationCreateStruct]
                )->versionInfo
            );

            ++$priority;
        }

        $location = $locationService->loadLocation($content->contentInfo->mainLocationId);
        $childrenLocations = $locationService->loadLocationChildren($location);

        self::assertCount(count($contentNames), $childrenLocations);

        return $location;
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::loadLocationChildren
     *
     * @dataProvider providerForLoadLocationChildrenRespectsParentSortingClauses
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testLoadLocationChildrenRespectsParentSortingClauses(
        int $sortField,
        int $sortOrder,
        array $expectedChildrenNames
    ): void {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $location = $this->createStructureForTestLoadLocationChildrenRespectsParentSortingClauses();

        // Update Location in order to change sort clause
        $locationUpdateStruct = $locationService->newLocationUpdateStruct();
        $locationUpdateStruct->sortField = $sortField;
        $locationUpdateStruct->sortOrder = $sortOrder;
        $location = $locationService->updateLocation(
            $location,
            $locationUpdateStruct
        );

        $childrenNames = array_map(
            static function (Location $location) {
                return $location->getContentInfo()->name;
            },
            iterator_to_array($locationService->loadLocationChildren($location))
        );

        self::assertSame($expectedChildrenNames, $childrenNames);
    }

    /**
     * Test for the newLocationUpdateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::newLocationUpdateStruct
     */
    public function testNewLocationUpdateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();

        $updateStruct = $locationService->newLocationUpdateStruct();
        /* END: Use Case */

        self::assertInstanceOf(
            LocationUpdateStruct::class,
            $updateStruct
        );

        $this->assertPropertiesCorrect(
            [
                'priority' => null,
                'remoteId' => null,
                'sortField' => null,
                'sortOrder' => null,
            ],
            $updateStruct
        );
    }

    /**
     * Test for the updateLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testLoadLocation
     */
    public function testUpdateLocation()
    {
        $repository = $this->getRepository();

        $originalLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $originalLocationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $originalLocation = $locationService->loadLocation($originalLocationId);

        $updateStruct = $locationService->newLocationUpdateStruct();
        $updateStruct->priority = 3;
        $updateStruct->remoteId = 'c7adcbf1e96bc29bca28c2d809d0c7ef69272651';
        $updateStruct->sortField = Location::SORT_FIELD_PRIORITY;
        $updateStruct->sortOrder = Location::SORT_ORDER_DESC;

        $updatedLocation = $locationService->updateLocation($originalLocation, $updateStruct);
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $updatedLocation
        );

        return [
            'originalLocation' => $originalLocation,
            'updateStruct' => $updateStruct,
            'updatedLocation' => $updatedLocation,
        ];
    }

    /**
     * Test for the updateLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testUpdateLocation
     */
    public function testUpdateLocationStructValues(array $data)
    {
        $originalLocation = $data['originalLocation'];
        $updateStruct = $data['updateStruct'];
        $updatedLocation = $data['updatedLocation'];

        $this->assertPropertiesCorrect(
            [
                'id' => $originalLocation->id,
                'priority' => $updateStruct->priority,
                'hidden' => $originalLocation->hidden,
                'invisible' => $originalLocation->invisible,
                'remoteId' => $updateStruct->remoteId,
                'contentInfo' => $originalLocation->contentInfo,
                'parentLocationId' => $originalLocation->parentLocationId,
                'pathString' => $originalLocation->pathString,
                'depth' => $originalLocation->depth,
                'sortField' => $updateStruct->sortField,
                'sortOrder' => $updateStruct->sortOrder,
            ],
            $updatedLocation
        );
    }

    /**
     * Test for the updateLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testLoadLocation
     */
    public function testUpdateLocationWithSameRemoteId()
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId and remote ID is the IDs of the same, existing location
        $locationService = $repository->getLocationService();

        $originalLocation = $locationService->loadLocation($locationId);

        $updateStruct = $locationService->newLocationUpdateStruct();

        // Remote ID of an existing location with the same locationId
        $updateStruct->remoteId = $originalLocation->remoteId;

        // Sets one of the properties to be able to confirm location gets updated, here: priority
        $updateStruct->priority = 2;

        $location = $locationService->updateLocation($originalLocation, $updateStruct);

        // Checks that the location was updated
        self::assertEquals(2, $location->priority);

        // Checks that remoteId remains the same
        self::assertEquals($originalLocation->remoteId, $location->remoteId);
        /* END: Use Case */
    }

    /**
     * Test for the updateLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testLoadLocation
     */
    public function testUpdateLocationThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId and remoteId is the IDs of an existing, but not the same, location
        $locationService = $repository->getLocationService();

        $originalLocation = $locationService->loadLocation($locationId);

        $updateStruct = $locationService->newLocationUpdateStruct();

        // Remote ID of an existing location with a different locationId
        $updateStruct->remoteId = 'f3e90596361e31d496d4026eb624c983';

        // Throws exception, since remote ID is already taken
        $locationService->updateLocation($originalLocation, $updateStruct);
        /* END: Use Case */
    }

    /**
     * Test for the updateLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testLoadLocation
     *
     * @dataProvider dataProviderForOutOfRangeLocationPriority
     */
    public function testUpdateLocationThrowsInvalidArgumentExceptionPriorityIsOutOfRange($priority)
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId and remoteId is the IDs of an existing, but not the same, location
        $locationService = $repository->getLocationService();

        $originalLocation = $locationService->loadLocation($locationId);

        $updateStruct = $locationService->newLocationUpdateStruct();

        // Priority value is out of range
        $updateStruct->priority = $priority;

        // Throws exception, since remote ID is already taken
        $locationService->updateLocation($originalLocation, $updateStruct);
        /* END: Use Case */
    }

    /**
     * Test for the updateLocation() method.
     * Ref EZP-23302: Update Location fails if no change is performed with the update.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::updateLocation()
     *
     * @depends testLoadLocation
     */
    public function testUpdateLocationTwice()
    {
        $repository = $this->getRepository();
        $permissionResolver = $repository->getPermissionResolver();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();
        $permissionResolver->setCurrentUserReference($repository->getUserService()->loadUser(14));

        $originalLocation = $locationService->loadLocation($locationId);

        $updateStruct = $locationService->newLocationUpdateStruct();
        $updateStruct->priority = 42;

        $updatedLocation = $locationService->updateLocation($originalLocation, $updateStruct);

        // Repeated update with the same, unchanged struct
        $secondUpdatedLocation = $locationService->updateLocation($updatedLocation, $updateStruct);
        /* END: Use Case */

        self::assertEquals($updatedLocation->priority, 42);
        self::assertEquals($secondUpdatedLocation->priority, 42);
    }

    /**
     * Test for the swapLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation()
     *
     * @depends testLoadLocation
     */
    public function testSwapLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);

        $mediaContentInfo = $locationService->loadLocation($mediaLocationId)->getContentInfo();
        $demoDesignContentInfo = $locationService->loadLocation($demoDesignLocationId)->getContentInfo();

        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        $mediaLocation = $locationService->loadLocation($mediaLocationId);
        $demoDesignLocation = $locationService->loadLocation($demoDesignLocationId);

        // Swaps the content referred to by the locations
        $locationService->swapLocation($mediaLocation, $demoDesignLocation);
        /* END: Use Case */

        // Reload Locations, IDs swapped
        $demoDesignLocation = $locationService->loadLocation($mediaLocationId);
        $mediaLocation = $locationService->loadLocation($demoDesignLocationId);

        // Assert Location's Content is updated
        self::assertEquals(
            $mediaContentInfo->id,
            $mediaLocation->getContentInfo()->id
        );
        self::assertEquals(
            $demoDesignContentInfo->id,
            $demoDesignLocation->getContentInfo()->id
        );

        // Assert URL aliases are updated
        self::assertEquals(
            $mediaLocation->id,
            $repository->getURLAliasService()->lookup('/Design/Media')->destination
        );
        self::assertEquals(
            $demoDesignLocation->id,
            $repository->getURLAliasService()->lookup('/Ibexa-Demo-Design-without-demo-content')->destination
        );
    }

    /**
     * Test for the swapLocation() method with custom aliases.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testSwapLocationForContentWithCustomUrlAliases(): void
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();
        $this->createLanguage('pol-PL', 'Polski');

        $folder1 = $this->createFolder(['eng-GB' => 'Folder1', 'pol-PL' => 'Folder1'], 2);
        $folder2 = $this->createFolder(['eng-GB' => 'Folder2'], 2);
        $location1 = $locationService->loadLocation($folder1->contentInfo->mainLocationId);
        $location2 = $locationService->loadLocation($folder2->contentInfo->mainLocationId);

        $urlAlias = $urlAliasService->createUrlAlias($location1, '/custom-location1', 'eng-GB', false, true);
        $urlAliasService->createUrlAlias($location1, '/custom-location1', 'pol-PL', false, true);
        $urlAliasService->createUrlAlias($location2, '/custom-location2', 'eng-GB', false, true);
        $location1UrlAliases = iterator_to_array($urlAliasService->listLocationAliases($location1));
        $location2UrlAliases = iterator_to_array($urlAliasService->listLocationAliases($location2));

        $locationService->swapLocation($location1, $location2);
        $location1 = $locationService->loadLocation($location1->contentInfo->mainLocationId);
        $location2 = $locationService->loadLocation($location2->contentInfo->mainLocationId);

        $location1UrlAliasesAfterSwap = iterator_to_array($urlAliasService->listLocationAliases($location1));
        $location2UrlAliasesAfterSwap = iterator_to_array($urlAliasService->listLocationAliases($location2));

        $keyUrlAlias = array_search($urlAlias->id, array_column($location1UrlAliasesAfterSwap, 'id'));

        self::assertEquals($folder1->id, $location2->contentInfo->id);
        self::assertEquals($folder2->id, $location1->contentInfo->id);
        self::assertNotEquals($location1UrlAliases, $location1UrlAliasesAfterSwap);
        self::assertEquals($location2UrlAliases, $location2UrlAliasesAfterSwap);
        self::assertEquals(['eng-GB'], $location1UrlAliasesAfterSwap[$keyUrlAlias]->languageCodes);
    }

    /**
     * Test swapping secondary Location with main Location.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation
     *
     * @see https://issues.ibexa.co/browse/EZP-28663
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @return int[]
     */
    public function testSwapLocationForMainAndSecondaryLocation(): array
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $folder1 = $this->createFolder(['eng-GB' => 'Folder1'], 2);
        $folder2 = $this->createFolder(['eng-GB' => 'Folder2'], 2);
        $folder3 = $this->createFolder(['eng-GB' => 'Folder3'], 2);

        $primaryLocation = $folder1->getVersionInfo()->getContentInfo()->getMainLocation();
        $parentLocation = $folder2->getVersionInfo()->getContentInfo()->getMainLocation();
        $secondaryLocation = $locationService->createLocation(
            $folder1->getVersionInfo()->getContentInfo(),
            $locationService->newLocationCreateStruct($parentLocation->id)
        );

        $targetLocation = $folder3->getVersionInfo()->getContentInfo()->getMainLocation();

        // perform sanity checks
        $this->assertContentHasExpectedLocations([$primaryLocation, $secondaryLocation], $folder1);

        // begin use case
        $locationService->swapLocation($secondaryLocation, $targetLocation);

        // test results
        $primaryLocation = $locationService->loadLocation($primaryLocation->id);
        $secondaryLocation = $locationService->loadLocation($secondaryLocation->id);
        $targetLocation = $locationService->loadLocation($targetLocation->id);

        self::assertEquals($folder1->id, $primaryLocation->getContentInfo()->getId());
        self::assertEquals($folder1->id, $targetLocation->getContentInfo()->getId());
        self::assertEquals($folder3->id, $secondaryLocation->getContentInfo()->getId());

        $this->assertContentHasExpectedLocations([$primaryLocation, $targetLocation], $folder1);

        self::assertEquals(
            $primaryLocation->id,
            $contentService->loadContent($folder1->id)->getVersionInfo()->getContentInfo()->getMainLocationId()
        );

        self::assertEquals(
            $parentLocation->id,
            $contentService->loadContent($folder2->id)->getVersionInfo()->getContentInfo()->getMainLocationId()
        );

        // only in case of Folder 3, main location id changed due to swap
        self::assertEquals(
            $secondaryLocation->id,
            $contentService->loadContent($folder3->id)->getVersionInfo()->getContentInfo()->getMainLocation()->id
        );

        return [$folder1, $folder2, $folder3];
    }

    /**
     * Compare Ids of expected and loaded Locations for the given Content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location[] $expectedLocations
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    private function assertContentHasExpectedLocations(array $expectedLocations, Content $content): void
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();

        $expectedLocationIds = array_map(
            static function (Location $location): int {
                return (int)$location->id;
            },
            $expectedLocations
        );

        $actualLocationsIds = array_map(
            static function (Location $location) {
                return $location->id;
            },
            $locationService->loadLocations($content->contentInfo)
        );
        self::assertCount(count($expectedLocations), $actualLocationsIds);

        // perform unordered equality assertion
        self::assertEqualsCanonicalizing(
            $expectedLocationIds,
            $actualLocationsIds,
            sprintf(
                'Content %d contains Locations %s, not expected Locations: %s',
                $content->id,
                implode(', ', $actualLocationsIds),
                implode(', ', $expectedLocationIds)
            )
        );
    }

    /**
     * @depends testSwapLocationForMainAndSecondaryLocation
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $contentItems Content items created by testSwapLocationForSecondaryLocation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function testSwapLocationDoesNotCorruptSearchResults(array $contentItems)
    {
        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();

        $this->refreshSearch($repository);

        $contentIds = array_map(
            static function (Content $content) {
                return $content->id;
            },
            $contentItems
        );

        $query = new Query();
        $query->filter = new Query\Criterion\ContentId($contentIds);

        $searchResult = $searchService->findContent($query);

        self::assertEquals(count($contentItems), $searchResult->totalCount);
        self::assertEquals(
            $searchResult->totalCount,
            count($searchResult->searchHits),
            'Total count of search result hits does not match the actual number of found results'
        );
        $foundContentIds = array_map(
            static function (SearchHit $searchHit) {
                return $searchHit->valueObject->id;
            },
            $searchResult->searchHits
        );
        sort($contentIds);
        sort($foundContentIds);
        self::assertSame(
            $contentIds,
            $foundContentIds,
            'Got different than expected Content item Ids'
        );
    }

    /**
     * Test swapping two secondary (non-main) Locations.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testSwapLocationForSecondaryLocations(): void
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $folder1 = $this->createFolder(['eng-GB' => 'Folder1'], 2);
        $folder2 = $this->createFolder(['eng-GB' => 'Folder2'], 2);
        $parentFolder1 = $this->createFolder(['eng-GB' => 'Parent1'], 2);
        $parentFolder2 = $this->createFolder(['eng-GB' => 'Parent2'], 2);

        $parentLocation1 = $parentFolder1->getVersionInfo()->getContentInfo()->getMainLocation();
        $parentLocation2 = $parentFolder2->getVersionInfo()->getContentInfo()->getMainLocation();
        $secondaryLocation1 = $locationService->createLocation(
            $folder1->getVersionInfo()->getContentInfo(),
            $locationService->newLocationCreateStruct($parentLocation1->id)
        );
        $secondaryLocation2 = $locationService->createLocation(
            $folder2->getVersionInfo()->getContentInfo(),
            $locationService->newLocationCreateStruct($parentLocation2->id)
        );

        // begin use case
        $locationService->swapLocation($secondaryLocation1, $secondaryLocation2);

        // test results
        $secondaryLocation1 = $locationService->loadLocation($secondaryLocation1->id);
        $secondaryLocation2 = $locationService->loadLocation($secondaryLocation2->id);

        self::assertEquals($folder2->id, $secondaryLocation1->getContentInfo()->getId());
        self::assertEquals($folder1->id, $secondaryLocation2->getContentInfo()->getId());

        self::assertEqualsCanonicalizing(
            [$folder1->getVersionInfo()->getContentInfo()->getMainLocationId(), $secondaryLocation2->id],
            array_map(
                static fn (Location $location): int => $location->id,
                $locationService->loadLocations($folder1->getVersionInfo()->getContentInfo())
            )
        );

        self::assertEqualsCanonicalizing(
            [$folder2->getVersionInfo()->getContentInfo()->getMainLocationId(), $secondaryLocation1->id],
            array_map(
                static fn (Location $location): int => $location->id,
                $locationService->loadLocations($folder2->getVersionInfo()->getContentInfo())
            )
        );
    }

    /**
     * Test swapping Main Location of a Content with another one updates Content item Main Location.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation
     */
    public function testSwapLocationUpdatesMainLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $mainLocationParentId = 60;
        $secondaryLocationId = 43;

        $publishedContent = $this->publishContentWithParentLocation(
            'Content for Swap Location Test',
            $mainLocationParentId
        );

        // sanity check
        $mainLocation = $locationService->loadLocation($publishedContent->contentInfo->mainLocationId);
        self::assertEquals($mainLocationParentId, $mainLocation->parentLocationId);

        // load another pre-existing location
        $secondaryLocation = $locationService->loadLocation($secondaryLocationId);

        // swap the Main Location with a secondary one
        $locationService->swapLocation($mainLocation, $secondaryLocation);

        // check if Main Location has been updated
        $mainLocation = $locationService->loadLocation($secondaryLocation->id);
        self::assertEquals($publishedContent->contentInfo->id, $mainLocation->contentInfo->id);
        self::assertEquals($mainLocation->id, $mainLocation->contentInfo->mainLocationId);

        $reloadedContent = $contentService->loadContentByContentInfo($publishedContent->contentInfo);
        self::assertEquals($mainLocation->id, $reloadedContent->contentInfo->mainLocationId);
    }

    /**
     * Test if location swap affects related bookmarks.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::swapLocation
     */
    public function testBookmarksAreSwappedAfterSwapLocation()
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();
        $bookmarkService = $repository->getBookmarkService();

        $mediaLocation = $locationService->loadLocation($mediaLocationId);
        $demoDesignLocation = $locationService->loadLocation($demoDesignLocationId);

        // Bookmark locations
        $bookmarkService->createBookmark($mediaLocation);
        $bookmarkService->createBookmark($demoDesignLocation);

        $beforeSwap = $bookmarkService->loadBookmarks();

        // Swaps the content referred to by the locations
        $locationService->swapLocation($mediaLocation, $demoDesignLocation);

        $afterSwap = $bookmarkService->loadBookmarks();
        /* END: Use Case */

        self::assertEquals($beforeSwap->items[0]->id, $afterSwap->items[1]->id);
        self::assertEquals($beforeSwap->items[1]->id, $afterSwap->items[0]->id);
    }

    /**
     * Test for the hideLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::hideLocation()
     *
     * @depends testLoadLocation
     */
    public function testHideLocation()
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $visibleLocation = $locationService->loadLocation($locationId);

        $hiddenLocation = $locationService->hideLocation($visibleLocation);
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $hiddenLocation
        );

        self::assertTrue(
            $hiddenLocation->hidden,
            sprintf(
                'Location with ID "%s" is not hidden.',
                $hiddenLocation->id
            )
        );

        $this->refreshSearch($repository);

        foreach ($locationService->loadLocationChildren($hiddenLocation)->locations as $child) {
            $this->assertSubtreeProperties(
                ['invisible' => true],
                $child
            );
        }
    }

    /**
     * Assert that $expectedValues are set in the subtree starting at $location.
     *
     * @param array $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     */
    protected function assertSubtreeProperties(array $expectedValues, Location $location, $stopId = null)
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        if ($location->id === $stopId) {
            return;
        }

        foreach ($expectedValues as $propertyName => $propertyValue) {
            self::assertEquals(
                $propertyValue,
                $location->$propertyName
            );

            foreach ($locationService->loadLocationChildren($location)->locations as $child) {
                $this->assertSubtreeProperties($expectedValues, $child);
            }
        }
    }

    /**
     * Test for the unhideLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::unhideLocation()
     *
     * @depends testHideLocation
     */
    public function testUnhideLocation()
    {
        $repository = $this->getRepository();

        $locationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $locationId is the ID of an existing location
        $locationService = $repository->getLocationService();

        $visibleLocation = $locationService->loadLocation($locationId);
        $hiddenLocation = $locationService->hideLocation($visibleLocation);

        $unHiddenLocation = $locationService->unhideLocation($hiddenLocation);
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $unHiddenLocation
        );

        self::assertFalse(
            $unHiddenLocation->hidden,
            sprintf(
                'Location with ID "%s" is hidden.',
                $unHiddenLocation->id
            )
        );

        $this->refreshSearch($repository);

        foreach ($locationService->loadLocationChildren($unHiddenLocation)->locations as $child) {
            $this->assertSubtreeProperties(
                ['invisible' => false],
                $child
            );
        }
    }

    /**
     * Test for the unhideLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::unhideLocation()
     *
     * @depends testUnhideLocation
     */
    public function testUnhideLocationNotUnhidesHiddenSubtree()
    {
        $repository = $this->getRepository();

        $higherLocationId = $this->generateId('location', 5);
        $lowerLocationId = $this->generateId('location', 13);
        /* BEGIN: Use Case */
        // $higherLocationId is the ID of a location
        // $lowerLocationId is the ID of a location below $higherLocationId
        $locationService = $repository->getLocationService();

        $higherLocation = $locationService->loadLocation($higherLocationId);
        $hiddenHigherLocation = $locationService->hideLocation($higherLocation);

        $lowerLocation = $locationService->loadLocation($lowerLocationId);
        $hiddenLowerLocation = $locationService->hideLocation($lowerLocation);

        $unHiddenHigherLocation = $locationService->unhideLocation($hiddenHigherLocation);
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $unHiddenHigherLocation
        );

        self::assertFalse(
            $unHiddenHigherLocation->hidden,
            sprintf(
                'Location with ID "%s" is hidden.',
                $unHiddenHigherLocation->id
            )
        );

        $this->refreshSearch($repository);

        foreach ($locationService->loadLocationChildren($unHiddenHigherLocation)->locations as $child) {
            $this->assertSubtreeProperties(
                ['invisible' => false],
                $child,
                $this->generateId('location', 13)
            );
        }

        $stillHiddenLocation = $locationService->loadLocation($this->generateId('location', 13));
        self::assertTrue(
            $stillHiddenLocation->hidden,
            sprintf(
                'Hidden sub-location with ID %s unhidden unexpectedly.',
                $stillHiddenLocation->id
            )
        );
        foreach ($locationService->loadLocationChildren($stillHiddenLocation)->locations as $child) {
            $this->assertSubtreeProperties(
                ['invisible' => true],
                $child
            );
        }
    }

    /**
     * Test for the deleteLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::deleteLocation()
     *
     * @depends testLoadLocation
     */
    public function testDeleteLocation()
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the location of the
        // "Media" location in an Ibexa demo installation
        $locationService = $repository->getLocationService();

        $location = $locationService->loadLocation($mediaLocationId);

        $locationService->deleteLocation($location);
        /* END: Use Case */

        try {
            $locationService->loadLocation($mediaLocationId);
            self::fail("Location $mediaLocationId not deleted.");
        } catch (NotFoundException $e) {
        }

        // The following IDs are IDs of child locations of $mediaLocationId location
        // ( Media/Images, Media/Files, Media/Multimedia respectively )
        foreach ([51, 52, 53] as $childLocationId) {
            try {
                $locationService->loadLocation($this->generateId('location', $childLocationId));
                self::fail("Location $childLocationId not deleted.");
            } catch (NotFoundException $e) {
            }
        }

        // The following IDs are IDs of content below $mediaLocationId location
        // ( Media/Images, Media/Files, Media/Multimedia respectively )
        $contentService = $this->getRepository()->getContentService();
        foreach ([49, 50, 51] as $childContentId) {
            try {
                $contentService->loadContentInfo($this->generateId('object', $childContentId));
                self::fail("Content $childContentId not deleted.");
            } catch (NotFoundException $e) {
            }
        }
    }

    /**
     * Test for the deleteLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::deleteLocation()
     *
     * @depends testDeleteLocation
     */
    public function testDeleteLocationDecrementsChildCountOnParent()
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the location of the
        // "Media" location in an Ibexa demo installation

        $locationService = $repository->getLocationService();

        // Load the current the user group location
        $location = $locationService->loadLocation($mediaLocationId);

        // Load the parent location
        $parentLocation = $locationService->loadLocation(
            $location->parentLocationId
        );

        // Get child count
        $childCountBefore = $locationService->getLocationChildCount($parentLocation);

        // Delete the user group location
        $locationService->deleteLocation($location);

        $this->refreshSearch($repository);

        // Reload parent location
        $parentLocation = $locationService->loadLocation(
            $location->parentLocationId
        );

        // This will be $childCountBefore - 1
        $childCountAfter = $locationService->getLocationChildCount($parentLocation);
        /* END: Use Case */

        self::assertEquals($childCountBefore - 1, $childCountAfter);
    }

    /**
     * Test for the deleteLocation() method.
     *
     * Related issue: EZP-21904
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::deleteLocation()
     */
    public function testDeleteContentObjectLastLocation()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        /* BEGIN: Use case */
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $contentTypeService = $repository->getContentTypeService();
        $urlAliasService = $repository->getURLAliasService();

        // prepare Content object
        $createStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-GB'
        );
        $createStruct->setField('name', 'Test folder');

        // creata Content object
        $content = $contentService->publishVersion(
            $contentService->createContent(
                $createStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // delete location
        $locationService->deleteLocation(
            $locationService->loadLocation(
                $urlAliasService->lookup('/Test-folder')->destination
            )
        );

        // this should throw a not found exception
        $contentService->loadContent($content->versionInfo->contentInfo->id);
        /* END: Use case*/
    }

    /**
     * Test for the deleteLocation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::deleteLocation
     *
     * @depends testDeleteLocation
     */
    public function testDeleteLocationDeletesRelatedBookmarks()
    {
        $repository = $this->getRepository();

        $parentLocationId = $this->generateId('location', 43);
        $childLocationId = $this->generateId('location', 53);

        /* BEGIN: Use Case */
        $locationService = $repository->getLocationService();
        $bookmarkService = $repository->getBookmarkService();

        // Load location
        $childLocation = $locationService->loadLocation($childLocationId);
        // Add location to bookmarks
        $bookmarkService->createBookmark($childLocation);
        // Load parent location
        $parentLocation = $locationService->loadLocation($parentLocationId);
        // Delete parent location
        $locationService->deleteLocation($parentLocation);
        /* END: Use Case */

        // Location isn't bookmarked anymore
        foreach ($bookmarkService->loadBookmarks(0, 9999) as $bookmarkedLocation) {
            self::assertNotEquals($childLocation->id, $bookmarkedLocation->id);
        }
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::deleteLocation
     */
    public function testDeleteUnusedLocationWhichPreviousHadContentWithRelativeAlias(): void
    {
        $repository = $this->getRepository(false);

        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();

        $originalFolder = $this->createFolder(['eng-GB' => 'Original folder'], 2);
        $newFolder = $this->createFolder(['eng-GB' => 'New folder'], 2);
        $originalFolderLocationId = $originalFolder->contentInfo->mainLocationId;

        $forum = $contentService->publishVersion(
            $contentService->createContent(
                $this->createForumStruct('Some forum'),
                [
                    $locationService->newLocationCreateStruct($originalFolderLocationId),
                ]
            )->versionInfo
        );

        $forumMainLocation = $locationService->loadLocation(
            $forum->contentInfo->mainLocationId
        );

        $customRelativeAliasPath = '/Original-folder/some-forum-alias';

        $urlAliasService->createUrlAlias(
            $forumMainLocation,
            $customRelativeAliasPath,
            'eng-GB',
            true,
            true
        );

        $locationService->moveSubtree(
            $forumMainLocation,
            $locationService->loadLocation(
                $newFolder->contentInfo->mainLocationId
            )
        );

        $this->assertAliasExists(
            $customRelativeAliasPath,
            $forumMainLocation,
            $urlAliasService
        );

        $urlAliasService->lookup($customRelativeAliasPath);

        $locationService->deleteLocation(
            $locationService->loadLocation(
                $originalFolder->contentInfo->mainLocationId
            )
        );

        $this->assertAliasExists(
            $customRelativeAliasPath,
            $forumMainLocation,
            $urlAliasService
        );

        $urlAliasService->lookup($customRelativeAliasPath);
    }

    /**
     * Test for the copySubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     *
     * @depends testLoadLocation
     */
    public function testCopySubtree()
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to copy
        $locationToCopy = $locationService->loadLocation($mediaLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        // Copy location "Media" to "Demo Design"
        $copiedLocation = $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );
        /* END: Use Case */

        self::assertInstanceOf(
            Location::class,
            $copiedLocation
        );

        $this->assertPropertiesCorrect(
            [
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $copiedLocation->id) . '/',
            ],
            $copiedLocation
        );

        $this->assertDefaultContentStates($copiedLocation->contentInfo);
    }

    /**
     * Test for the copySubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     *
     * @depends testLoadLocation
     */
    public function testCopySubtreeWithAliases()
    {
        $repository = $this->getRepository();
        $urlAliasService = $repository->getURLAliasService();

        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation
        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);

        $locationService = $repository->getLocationService();
        $locationToCopy = $locationService->loadLocation($mediaLocationId);
        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        $expectedSubItemAliases = [
            '/Design/Plain-site/Media/Multimedia',
            '/Design/Plain-site/Media/Images',
            '/Design/Plain-site/Media/Files',
        ];

        $this->assertAliasesBeforeCopy($urlAliasService, $expectedSubItemAliases);

        // Copy location "Media" to "Design"
        $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );

        $this->assertGeneratedAliases($urlAliasService, $expectedSubItemAliases);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree
     */
    public function testCopySubtreeWithTranslatedContent(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $urlAliasService = $repository->getURLAliasService();

        $mediaLocationId = $this->generateId('location', 43);
        $filesLocationId = $this->generateId('location', 52);
        $demoDesignLocationId = $this->generateId('location', 56);

        $locationToCopy = $locationService->loadLocation($mediaLocationId);
        $filesLocation = $locationService->loadLocation($filesLocationId);
        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        // translating the 'middle' folder
        $translatedDraft = $contentService->createContentDraft($filesLocation->contentInfo);
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => 'ger-DE',
            'fields' => $translatedDraft->getFields(),
        ]);
        $contentUpdateStruct->setField('short_name', 'FilesGER', 'ger-DE');
        $translatedContent = $contentService->updateContent($translatedDraft->versionInfo, $contentUpdateStruct);
        $contentService->publishVersion($translatedContent->versionInfo);

        // creating additional content under translated folder
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        $contentCreate = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $contentCreate->setField('name', 'My folder');
        $content = $contentService->createContent(
            $contentCreate,
            [new LocationCreateStruct(['parentLocationId' => $filesLocationId])]
        );
        $contentService->publishVersion($content->versionInfo);

        $expectedSubItemAliases = [
            '/Design/Plain-site/Media/Multimedia',
            '/Design/Plain-site/Media/Images',
            '/Design/Plain-site/Media/Files',
            '/Design/Plain-site/Media/Files/my-folder',
        ];

        $this->assertAliasesBeforeCopy($urlAliasService, $expectedSubItemAliases);

        $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );

        $this->assertGeneratedAliases($urlAliasService, $expectedSubItemAliases);
    }

    /**
     * Asserts that given Content has default ContentStates.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    private function assertDefaultContentStates(ContentInfo $contentInfo)
    {
        $repository = $this->getRepository();
        $objectStateService = $repository->getObjectStateService();

        $objectStateGroups = $objectStateService->loadObjectStateGroups();

        foreach ($objectStateGroups as $objectStateGroup) {
            $contentState = $objectStateService->getContentState($contentInfo, $objectStateGroup);
            foreach ($objectStateService->loadObjectStates($objectStateGroup, Language::ALL) as $objectState) {
                // Only check the first object state which is the default one.
                self::assertEquals(
                    $objectState,
                    $contentState
                );
                break;
            }
        }
    }

    /**
     * Test for the copySubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     *
     * @depends testCopySubtree
     */
    public function testCopySubtreeUpdatesSubtreeProperties()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $locationToCopy = $locationService->loadLocation($this->generateId('location', 43));

        // Load Subtree properties before copy
        $expected = $this->loadSubtreeProperties($locationToCopy);

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to copy
        $locationToCopy = $locationService->loadLocation($mediaLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        // Copy location "Media" to "Demo Design"
        $copiedLocation = $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );
        /* END: Use Case */

        $beforeIds = [];
        foreach ($expected as $properties) {
            $beforeIds[] = $properties['id'];
        }

        $this->refreshSearch($repository);

        // Load Subtree properties after copy
        $actual = $this->loadSubtreeProperties($copiedLocation);

        self::assertEquals(count($expected), count($actual));

        foreach ($actual as $properties) {
            self::assertNotContains($properties['id'], $beforeIds);
            self::assertStringStartsWith(
                $newParentLocation->pathString . $this->parseId('location', $copiedLocation->id) . '/',
                $properties['pathString']
            );
            self::assertStringEndsWith(
                '/' . $this->parseId('location', $properties['id']) . '/',
                $properties['pathString']
            );
        }
    }

    /**
     * Test for the copySubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     *
     * @depends testCopySubtree
     */
    public function testCopySubtreeIncrementsChildCountOfNewParent()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $childCountBefore = $locationService->getLocationChildCount($locationService->loadLocation(56));

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to copy
        $locationToCopy = $locationService->loadLocation($mediaLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        // Copy location "Media" to "Demo Design"
        $copiedLocation = $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );
        /* END: Use Case */

        $this->refreshSearch($repository);

        $childCountAfter = $locationService->getLocationChildCount($locationService->loadLocation($demoDesignLocationId));

        self::assertEquals($childCountBefore + 1, $childCountAfter);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     */
    public function testCopySubtreeWithInvisibleChild(): void
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        // Hide child Location
        $locationService->hideLocation($locationService->loadLocation($this->generateId('location', 53)));

        $this->refreshSearch($repository);

        $locationToCopy = $locationService->loadLocation($this->generateId('location', 43));

        $expected = $this->loadSubtreeProperties($locationToCopy);

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        $locationService = $repository->getLocationService();

        $locationToCopy = $locationService->loadLocation($mediaLocationId);

        $newParentLocation = $locationService->loadLocation($demoDesignLocationId);

        $copiedLocation = $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );

        $this->refreshSearch($repository);

        // Load Subtree properties after copy
        $actual = $this->loadSubtreeProperties($copiedLocation);

        self::assertEquals(count($expected), count($actual));

        foreach ($actual as $key => $properties) {
            self::assertEquals($expected[$key]['hidden'], $properties['hidden']);
            self::assertEquals($expected[$key]['invisible'], $properties['invisible']);
        }
    }

    /**
     * Test for the copySubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::copySubtree()
     *
     * @depends testCopySubtree
     */
    public function testCopySubtreeThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $communityLocationId = $this->generateId('location', 5);
        /* BEGIN: Use Case */
        // $communityLocationId is the ID of the "Community" page location in
        // an Ibexa demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to copy
        $locationToCopy = $locationService->loadLocation($communityLocationId);

        // Use a child as new parent
        $childLocations = $locationService->loadLocationChildren($locationToCopy)->locations;
        $newParentLocation = end($childLocations);

        // This call will fail with an "InvalidArgumentException", because the
        // new parent is a child location of the subtree to copy.
        $locationService->copySubtree(
            $locationToCopy,
            $newParentLocation
        );
        /* END: Use Case */
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @depends testLoadLocation
     */
    public function testMoveSubtree(): void
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($demoDesignLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);

        // Move location from "Home" to "Media"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Load moved location
        $movedLocation = $locationService->loadLocation($demoDesignLocationId);
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'hidden' => false,
                'invisible' => false,
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $movedLocation->id) . '/',
            ],
            $movedLocation
        );
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @depends testLoadLocation
     */
    public function testMoveSubtreeToLocationWithoutContent(): void
    {
        $repository = $this->getRepository();

        $rootLocationId = $this->generateId('location', 1);
        $demoDesignLocationId = $this->generateId('location', 56);
        $locationService = $repository->getLocationService();
        $locationToMove = $locationService->loadLocation($demoDesignLocationId);
        $newParentLocation = $locationService->loadLocation($rootLocationId);

        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        $movedLocation = $locationService->loadLocation($demoDesignLocationId);

        $this->assertPropertiesCorrect(
            [
                'hidden' => false,
                'invisible' => false,
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $movedLocation->id) . '/',
            ],
            $movedLocation
        );
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @depends testLoadLocation
     */
    public function testMoveSubtreeThrowsExceptionOnMoveNotIntoContainer(): void
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        $userLocationId = $this->generateId('location', 15);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $userLocationId is the ID of the "Administrator User" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($mediaLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($userLocationId);

        // Move location from "Home" to "Demo Design" (not container)
        $this->expectException(InvalidArgumentException::class);
        $locationService->moveSubtree($locationToMove, $newParentLocation);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @depends testLoadLocation
     */
    public function testMoveSubtreeThrowsExceptionOnMoveToSame(): void
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($mediaLocationId);

        // Load parent location
        $newParentLocation = $locationService->loadLocation($locationToMove->parentLocationId);

        // Move location from "Home" to "Home"
        $this->expectException(InvalidArgumentException::class);
        $locationService->moveSubtree($locationToMove, $newParentLocation);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @depends testMoveSubtree
     */
    public function testMoveSubtreeHidden(): void
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($demoDesignLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);

        // Hide the target location before we move
        $newParentLocation = $locationService->hideLocation($newParentLocation);

        // Move location from "Demo Design" to "Home"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Load moved location
        $movedLocation = $locationService->loadLocation($demoDesignLocationId);
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'hidden' => false,
                'invisible' => true,
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $movedLocation->id) . '/',
            ],
            $movedLocation
        );
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree()
     *
     * @depends testMoveSubtree
     */
    public function testMoveSubtreeUpdatesSubtreeProperties()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $locationToMove = $locationService->loadLocation($this->generateId('location', 56));
        $newParentLocation = $locationService->loadLocation($this->generateId('location', 43));

        // Load Subtree properties before move
        $expected = $this->loadSubtreeProperties($locationToMove);
        foreach ($expected as $id => $properties) {
            $expected[$id]['depth'] = $properties['depth'] + 2;
            $expected[$id]['pathString'] = str_replace(
                $locationToMove->pathString,
                $newParentLocation->pathString . $this->parseId('location', $locationToMove->id) . '/',
                $properties['pathString']
            );
        }

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($demoDesignLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);

        // Move location from "Demo Design" to "Home"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Load moved location
        $movedLocation = $locationService->loadLocation($demoDesignLocationId);
        /* END: Use Case */

        $this->refreshSearch($repository);

        // Load Subtree properties after move
        $actual = $this->loadSubtreeProperties($movedLocation);

        self::assertEquals($expected, $actual);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree()
     *
     * @depends testMoveSubtreeUpdatesSubtreeProperties
     */
    public function testMoveSubtreeUpdatesSubtreePropertiesHidden()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $locationToMove = $locationService->loadLocation($this->generateId('location', 2));
        $newParentLocation = $locationService->loadLocation($this->generateId('location', 43));

        // Hide the target location before we move
        $newParentLocation = $locationService->hideLocation($newParentLocation);

        // Load Subtree properties before move
        $expected = $this->loadSubtreeProperties($locationToMove);
        foreach ($expected as $id => $properties) {
            $expected[$id]['invisible'] = true;
            $expected[$id]['depth'] = $properties['depth'] + 1;
            $expected[$id]['pathString'] = str_replace(
                $locationToMove->pathString,
                $newParentLocation->pathString . $this->parseId('location', $locationToMove->id) . '/',
                $properties['pathString']
            );
        }

        $homeLocationId = $this->generateId('location', 2);
        $mediaLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $homeLocationId is the ID of the "Home" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($homeLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);

        // Move location from "Home" to "Demo Design"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Load moved location
        $movedLocation = $locationService->loadLocation($homeLocationId);
        /* END: Use Case */

        $this->refreshSearch($repository);

        // Load Subtree properties after move
        $actual = $this->loadSubtreeProperties($movedLocation);

        self::assertEquals($expected, $actual);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree()
     *
     * @depends testMoveSubtree
     */
    public function testMoveSubtreeIncrementsChildCountOfNewParent()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $newParentLocation = $locationService->loadLocation($this->generateId('location', 43));

        // Load expected properties before move
        $expected = $this->loadLocationProperties($newParentLocation);
        $childCountBefore = $locationService->getLocationChildCount($newParentLocation);

        $mediaLocationId = $this->generateId('location', 43);
        $demoDesignLocationId = $this->generateId('location', 56);
        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $demoDesignLocationId is the ID of the "Demo Design" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($demoDesignLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);

        // Move location from "Demo Design" to "Home"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Load moved location
        $movedLocation = $locationService->loadLocation($demoDesignLocationId);

        // Reload new parent location
        $newParentLocation = $locationService->loadLocation($mediaLocationId);
        /* END: Use Case */

        $this->refreshSearch($repository);

        // Load Subtree properties after move
        $actual = $this->loadLocationProperties($newParentLocation);
        $childCountAfter = $locationService->getLocationChildCount($newParentLocation);

        self::assertEquals($expected, $actual);
        self::assertEquals($childCountBefore + 1, $childCountAfter);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree()
     *
     * @depends testMoveSubtree
     */
    public function testMoveSubtreeDecrementsChildCountOfOldParent()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $oldParentLocation = $locationService->loadLocation($this->generateId('location', 1));

        // Load expected properties before move
        $expected = $this->loadLocationProperties($oldParentLocation);
        $childCountBefore = $locationService->getLocationChildCount($oldParentLocation);

        $homeLocationId = $this->generateId('location', 2);
        $mediaLocationId = $this->generateId('location', 43);
        /* BEGIN: Use Case */
        // $homeLocationId is the ID of the "Home" page location in
        // an Ibexa demo installation

        // $mediaLocationId is the ID of the "Media" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($mediaLocationId);

        // Get the location id of the old parent
        $oldParentLocationId = $locationToMove->parentLocationId;

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($homeLocationId);

        // Move location from "Demo Design" to "Home"
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );

        // Reload old parent location
        $oldParentLocation = $locationService->loadLocation($oldParentLocationId);
        /* END: Use Case */

        $this->refreshSearch($repository);

        // Load Subtree properties after move
        $actual = $this->loadLocationProperties($oldParentLocation);
        $childCountAfter = $locationService->getLocationChildCount($oldParentLocation);

        self::assertEquals($expected, $actual);
        self::assertEquals($childCountBefore - 1, $childCountAfter);
    }

    /**
     * Test moving invisible (hidden by parent) subtree.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testMoveInvisibleSubtree()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $rootLocationId = 2;

        $folder = $this->createFolder(['eng-GB' => 'Folder'], $rootLocationId);
        $child = $this->createFolder(['eng-GB' => 'Child'], $folder->contentInfo->mainLocationId);
        $locationService->hideLocation(
            $locationService->loadLocation($folder->contentInfo->mainLocationId)
        );
        // sanity check
        $childLocation = $locationService->loadLocation($child->contentInfo->mainLocationId);
        self::assertFalse($childLocation->hidden);
        self::assertTrue($childLocation->invisible);
        self::assertEquals($folder->contentInfo->mainLocationId, $childLocation->parentLocationId);

        $destination = $this->createFolder(['eng-GB' => 'Destination'], $rootLocationId);
        $destinationLocation = $locationService->loadLocation(
            $destination->contentInfo->mainLocationId
        );

        $locationService->moveSubtree($childLocation, $destinationLocation);

        $childLocation = $locationService->loadLocation($child->contentInfo->mainLocationId);
        // Business logic - Location moved to visible parent becomes visible
        self::assertFalse($childLocation->hidden);
        self::assertFalse($childLocation->invisible);
        self::assertEquals($destinationLocation->id, $childLocation->parentLocationId);
    }

    /**
     * Test for the moveSubtree() method.
     *
     * @depends testMoveSubtree
     */
    public function testMoveSubtreeThrowsInvalidArgumentException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $mediaLocationId = $this->generateId('location', 43);
        $multimediaLocationId = $this->generateId('location', 53);

        /* BEGIN: Use Case */
        // $mediaLocationId is the ID of the "Media" page location in
        // an Ibexa demo installation

        // $multimediaLocationId is the ID of the "Multimedia" page location in an Ibexa
        // Publish demo installation

        // Load the location service
        $locationService = $repository->getLocationService();

        // Load location to move
        $locationToMove = $locationService->loadLocation($mediaLocationId);

        // Load new parent location
        $newParentLocation = $locationService->loadLocation($multimediaLocationId);

        // Throws an exception because new parent location is placed below location to move
        $this->expectException(InvalidArgumentException::class);
        $locationService->moveSubtree(
            $locationToMove,
            $newParentLocation
        );
        /* END: Use Case */
    }

    /**
     * Test that Legacy ibexa_content_tree.path_identification_string field is correctly updated
     * after moving subtree.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testMoveSubtreeUpdatesPathIdentificationString(): void
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $topNode = $this->createFolder(['eng-US' => 'top_node'], 2);

        $newParentLocation = $locationService->loadLocation(
            $this
                ->createFolder(['eng-US' => 'Parent'], $topNode->contentInfo->mainLocationId)
                ->contentInfo
                ->mainLocationId
        );
        $location = $locationService->loadLocation(
            $this
                ->createFolder(['eng-US' => 'Move Me'], $topNode->contentInfo->mainLocationId)
                ->contentInfo
                ->mainLocationId
        );

        $locationService->moveSubtree($location, $newParentLocation);

        // path location string is not present on API level, so we need to query database
        $serviceContainer = $this->getSetupFactory()->getServiceContainer();
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $serviceContainer->get('ibexa.persistence.connection');
        $query = $connection->createQueryBuilder();
        $query
            ->select('path_identification_string')
            ->from(Gateway::CONTENT_TREE_TABLE)
            ->where('node_id = :nodeId')
            ->setParameter('nodeId', $location->id);

        self::assertEquals(
            'top_node/parent/move_me',
            $query->executeQuery()->fetchOne()
        );
    }

    /**
     * Test that is_visible is set correct for children when moving a location where a child is hidden by content (not by location).
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     */
    public function testMoveSubtreeKeepsContentHiddenOnChildrenAndParent(): void
    {
        $repository = $this->getRepository();

        $mediaLocationId = $this->generateId('location', 43);

        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $sourceFolderContent = $this->publishContentWithParentLocation('SourceFolder', $mediaLocationId); // media/SourceFolder
        $subFolderContent1 = $this->publishContentWithParentLocation('subFolderContent1', $sourceFolderContent->contentInfo->mainLocationId);
        $subFolderContent2 = $this->publishContentWithParentLocation('subFolderContent2', $sourceFolderContent->contentInfo->mainLocationId);
        $targetFolderContent = $this->publishContentWithParentLocation('targetFolder', $mediaLocationId); // media/TargetFolder

        $contentService->hideContent($subFolderContent1->contentInfo);

        // Move media/SourceFolder to media/TargetFolder/
        $locationService->moveSubtree(
            $sourceFolderContent->contentInfo->getMainLocation(),
            $targetFolderContent->contentInfo->getMainLocation()
        );

        $movedLocation = $locationService->loadLocation($sourceFolderContent->contentInfo->mainLocationId);
        $newParentLocation = $locationService->loadLocation($targetFolderContent->contentInfo->mainLocationId);

        // Assert Moved Location remains visible ( only child is hidden )
        $this->assertPropertiesCorrect(
            [
                'hidden' => false,
                'invisible' => false,
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $movedLocation->id) . '/',
            ],
            $movedLocation
        );
        self::assertFalse($movedLocation->getContentInfo()->isHidden);

        // Assert children of Moved location
        $childrenLocations = [
            $subFolderContent1->contentInfo->getMainLocation(),
            $subFolderContent2->contentInfo->getMainLocation(),
        ];
        foreach ($childrenLocations as $childLocation) {
            $this->assertPropertiesCorrect(
                [
                    'hidden' => $childLocation === $subFolderContent1->contentInfo->getMainLocation(), // Only SubFolderContent1 should be hidden,
                    'invisible' => $childLocation === $subFolderContent1->contentInfo->getMainLocation(), // Only SubFolderContent1 should be hidden
                    'depth' => $movedLocation->depth + 1,
                    'parentLocationId' => $movedLocation->id,
                    'pathString' => $movedLocation->pathString . $this->parseId('location', $childLocation->id) . '/',
                ],
                $childLocation
            );
            self::assertEquals($childLocation === $subFolderContent1->contentInfo->getMainLocation(), $childLocation->getContentInfo()->isHidden);
        }
    }

    /**
     * Test that is_visible is set correct for children when moving a content which is hidden (location is not hidden).
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     */
    public function testMoveSubtreeKeepsContentHiddenOnChildren(): void
    {
        $repository = $this->getRepository();

        $sourceLocationId = $this->createFolder(
            [
                'eng-GB' => 'SourceParentFolder',
            ],
            2
        )->getVersionInfo()->getContentInfo()->mainLocationId;

        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $sourceFolderContent = $this->publishContentWithParentLocation('SourceFolder', $sourceLocationId); // media/SourceFolder
        $subFolderContent1 = $this->publishContentWithParentLocation('subFolderContent1', $sourceFolderContent->contentInfo->mainLocationId);
        $subFolderContent2 = $this->publishContentWithParentLocation('subFolderContent2', $sourceFolderContent->contentInfo->mainLocationId);
        $targetFolderContent = $this->publishContentWithParentLocation('targetFolder', $sourceLocationId); // media/TargetFolder

        $contentService->hideContent($sourceFolderContent->contentInfo);

        // Move media/SourceFolder to media/TargetFolder/
        $locationService->moveSubtree(
            $sourceFolderContent->contentInfo->getMainLocation(),
            $targetFolderContent->contentInfo->getMainLocation()
        );

        $movedLocation = $locationService->loadLocation($sourceFolderContent->contentInfo->mainLocationId);
        $newParentLocation = $locationService->loadLocation($targetFolderContent->contentInfo->mainLocationId);

        // Assert Moved Location
        $this->assertPropertiesCorrect(
            [
                'hidden' => true,
                'invisible' => true,
                'depth' => $newParentLocation->depth + 1,
                'parentLocationId' => $newParentLocation->id,
                'pathString' => $newParentLocation->pathString . $this->parseId('location', $movedLocation->id) . '/',
            ],
            $movedLocation
        );

        self::assertTrue($movedLocation->getContentInfo()->isHidden);

        // Assert children of Moved location
        $childLocations = [$subFolderContent1->contentInfo->getMainLocation(), $subFolderContent2->contentInfo->getMainLocation()];
        foreach ($childLocations as $childLocation) {
            $this->assertPropertiesCorrect(
                [
                    'hidden' => false,
                    'invisible' => true,
                    'depth' => $movedLocation->depth + 1,
                    'parentLocationId' => $movedLocation->id,
                    'pathString' => $movedLocation->pathString . $this->parseId('location', $childLocation->id) . '/',
                ],
                $childLocation
            );
            self::assertFalse($childLocation->getContentInfo()->isHidden);
        }
    }

    /**
     * Test validating whether content that is being moved is still allowed to be moved when one of its locations
     * is inaccessible by a current user, however, when moved location is accessible.
     *
     * @covers \Ibexa\Contracts\Core\Repository\LocationService::moveSubtree
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     */
    public function testMoveSubtreeContentWithMultipleLocationsAndOneOfThemInaccessible(): void
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $permissionResolver = $repository->getPermissionResolver();

        $folder = $this->publishContentWithParentLocation('Parent folder', 2);
        $accessibleFolder = $this->publishContentWithParentLocation('Accessible folder', 2);
        $subFolder = $this->publishContentWithParentLocation(
            'Sub folder',
            $folder->contentInfo->mainLocationId
        );
        $contentToBeMoved = $this->publishContentWithParentLocation(
            'Target folder',
            $subFolder->contentInfo->mainLocationId
        );
        $forbiddenContent = $this->publishContentWithParentLocation('Forbidden folder', 2);

        // Add second location (parent 'Forbidden folder') to 'Target content' in folder that user won't have access to
        $locationService->createLocation(
            $contentToBeMoved->contentInfo,
            $locationService->newLocationCreateStruct($forbiddenContent->contentInfo->mainLocationId)
        );

        $folderLocation = $locationService->loadLocation($folder->contentInfo->mainLocationId);
        $accessibleFolderLocation = $locationService->loadLocation($accessibleFolder->contentInfo->mainLocationId);

        // Set user that cannot access 'Forbidden folder'
        $user = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'read'],
                ['module' => 'content', 'function' => 'create'],
            ],
            new SubtreeLimitation(
                [
                    'limitationValues' => [
                        $folderLocation->getPathString(),
                        $accessibleFolderLocation->getPathString(),
                    ],
                ]
            )
        );
        $permissionResolver->setCurrentUserReference($user);

        // Move Parent folder/Sub folder/Target folder to location of ID = 2
        $locationService->moveSubtree(
            $contentToBeMoved->contentInfo->getMainLocation(),
            $accessibleFolderLocation
        );

        $targetContentMainLocation = $locationService->loadLocation($contentToBeMoved->contentInfo->mainLocationId);

        self::assertSame($targetContentMainLocation->parentLocationId, $accessibleFolderLocation->id);
    }

    public function testGetSubtreeSize(): Location
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $folder = $this->createFolder(['eng-GB' => 'Parent Folder'], 2);
        $location = $folder->getVersionInfo()->getContentInfo()->getMainLocation();
        self::assertSame(1, $locationService->getSubtreeSize($location));

        $this->createFolder(['eng-GB' => 'Child 1'], $location->id);
        $this->createFolder(['eng-GB' => 'Child 2'], $location->id);

        self::assertSame(3, $locationService->getSubtreeSize($location));

        return $location;
    }

    /**
     * Loads properties from all locations in the $location's subtree.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param array $properties
     *
     * @return array
     */
    private function loadSubtreeProperties(Location $location, array $properties = [])
    {
        $locationService = $this->getRepository()->getLocationService();

        foreach ($locationService->loadLocationChildren($location)->locations as $childLocation) {
            $properties[] = $this->loadLocationProperties($childLocation);

            $properties = $this->loadSubtreeProperties($childLocation, $properties);
        }

        return $properties;
    }

    /**
     * Loads assertable properties from the given location.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location $location
     * @param mixed[] $overwrite
     *
     * @return array
     */
    private function loadLocationProperties(Location $location, array $overwrite = [])
    {
        return array_merge(
            [
                'id' => $location->id,
                'depth' => $location->depth,
                'parentLocationId' => $location->parentLocationId,
                'pathString' => $location->pathString,
                'remoteId' => $location->remoteId,
                'hidden' => $location->hidden,
                'invisible' => $location->invisible,
                'priority' => $location->priority,
                'sortField' => $location->sortField,
                'sortOrder' => $location->sortOrder,
            ],
            $overwrite
        );
    }

    /**
     * Assert generated aliases to expected alias return.
     *
     * @param \Ibexa\Contracts\Core\Repository\URLAliasService $urlAliasService
     * @param array $expectedAliases
     */
    protected function assertGeneratedAliases($urlAliasService, array $expectedAliases)
    {
        foreach ($expectedAliases as $expectedAlias) {
            $urlAlias = $urlAliasService->lookup($expectedAlias);
            $this->assertPropertiesCorrect(['type' => 0], $urlAlias);
        }
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\URLAliasService $urlAliasService
     * @param array $expectedSubItemAliases
     */
    private function assertAliasesBeforeCopy($urlAliasService, array $expectedSubItemAliases)
    {
        foreach ($expectedSubItemAliases as $aliasUrl) {
            try {
                $urlAliasService->lookup($aliasUrl);
                self::fail('We didn\'t expect to find alias, but it was found');
            } catch (\Exception $e) {
                self::assertTrue(true); // OK - alias was not found
            }
        }
    }

    /**
     * Create and publish Content with the given parent Location.
     *
     * @param string $contentName
     * @param int $parentLocationId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content published Content
     */
    private function publishContentWithParentLocation($contentName, $parentLocationId)
    {
        $repository = $this->getRepository(false);
        $locationService = $repository->getLocationService();

        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-US'
        );
        $contentCreateStruct->setField('name', $contentName);
        $contentDraft = $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct($parentLocationId),
            ]
        );

        return $contentService->publishVersion($contentDraft->versionInfo);
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    private function createForumStruct(string $name): ContentCreateStruct
    {
        $repository = $this->getRepository(false);

        $contentTypeForum = $repository->getContentTypeService()
            ->loadContentTypeByIdentifier('forum');

        $forum = $repository->getContentService()
            ->newContentCreateStruct($contentTypeForum, 'eng-GB');

        $forum->setField('name', $name);

        return $forum;
    }

    private function assertAliasExists(
        string $expectedAliasPath,
        Location $location,
        URLAliasServiceInterface $urlAliasService
    ): void {
        $articleAliasesBeforeDelete = iterator_to_array($urlAliasService->listLocationAliases($location));

        self::assertNotEmpty(
            array_filter(
                $articleAliasesBeforeDelete,
                static function (URLAlias $alias) use ($expectedAliasPath): bool {
                    return $alias->path === $expectedAliasPath;
                }
            )
        );
    }
}
