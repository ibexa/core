<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use DateTime;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;

/**
 * Test case for indexing operations with a search engine.
 *
 * @group integration
 * @group search
 * @group indexing
 */
class SearchEngineIndexingTest extends BaseTestCase
{
    /**
     * Test that indexing full text data depends on the isSearchable flag on the field definition.
     */
    public function testFindContentInfoFullTextIsSearchable()
    {
        $searchTerm = 'pamplemousse';
        $content = $this->createFullTextIsSearchableContent($searchTerm, true);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new Query(
            [
                'query' => new Criterion\FullText($searchTerm),
            ]
        );

        $searchResult = $searchService->findContentInfo($query);

        self::assertEquals(1, $searchResult->totalCount);
        $contentInfo = $searchResult->searchHits[0]->valueObject;
        self::assertEquals($content->id, $contentInfo->id);

        return $contentInfo;
    }

    /**
     * Test that indexing full text data depends on the isSearchable flag on the field definition.
     *
     * @depends testFindContentInfoFullTextIsSearchable
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testFindLocationsFullTextIsSearchable(ContentInfo $contentInfo)
    {
        $searchTerm = 'pamplemousse';

        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();

        $query = new LocationQuery(
            [
                'query' => new Criterion\FullText($searchTerm),
            ]
        );

        $searchResult = $searchService->findLocations($query);

        self::assertEquals(1, $searchResult->totalCount);
        self::assertEquals(
            $contentInfo->mainLocationId,
            $searchResult->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test that indexing full text data depends on the isSearchable flag on the field definition.
     *
     * @depends testFindContentInfoFullTextIsSearchable
     */
    public function testFindContentInfoFullTextIsNotSearchable()
    {
        $searchTerm = 'pamplemousse';
        $this->createFullTextIsSearchableContent($searchTerm, false);

        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $query = new Query(
            [
                'query' => new Criterion\FullText($searchTerm),
            ]
        );

        $searchResult = $searchService->findContentInfo($query);

        self::assertEquals(0, $searchResult->totalCount);
    }

    /**
     * Test that indexing full text data depends on the isSearchable flag on the field definition.
     *
     * @depends testFindLocationsFullTextIsSearchable
     */
    public function testFindLocationsFullTextIsNotSearchable()
    {
        $searchTerm = 'pamplemousse';

        $repository = $this->getRepository(false);
        $searchService = $repository->getSearchService();

        $query = new LocationQuery(
            [
                'query' => new Criterion\FullText($searchTerm),
            ]
        );

        $searchResult = $searchService->findLocations($query);

        self::assertEquals(0, $searchResult->totalCount);
    }

    /**
     * Creates Content for testing full text search depending on the isSearchable flag.
     *
     * @see testFindContentInfoFullTextIsearchable
     * @see testFindLocationsFullTextIsSearchable
     * @see testFindContentInfoFullTextIsNotSearchable
     * @see testFindLocationsFullTextIsNotSearchable
     *
     * @param string $searchText
     * @param bool $isSearchable
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createFullTextIsSearchableContent($searchText, $isSearchable)
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        if (!$isSearchable) {
            $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
            $fieldDefinitionUpdateStruct = $contentTypeService->newFieldDefinitionUpdateStruct();
            $fieldDefinitionUpdateStruct->isSearchable = false;

            $fieldDefinition = $contentType->getFieldDefinition('name');

            $contentTypeService->updateFieldDefinition(
                $contentTypeDraft,
                $fieldDefinition,
                $fieldDefinitionUpdateStruct
            );

            $contentTypeService->publishContentTypeDraft($contentTypeDraft);
            $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');
        }

        $contentCreateStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');

        $contentCreateStruct->setField('name', $searchText);
        $contentCreateStruct->setField('short_name', 'hello world');
        $content = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreateStruct,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        $this->refreshSearch($repository);

        return $content;
    }

    /**
     * EZP-26186: Make sure index is NOT deleted on removal of version draft (affected Solr).
     */
    public function testDeleteVersion()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $membersContentId = $this->generateId('content', 11);
        $contentInfo = $contentService->loadContentInfo($membersContentId);

        $draft = $contentService->createContentDraft($contentInfo);
        $contentService->deleteVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        // Found
        $criterion = new Criterion\LocationId($contentInfo->mainLocationId);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContentInfo($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $contentInfo->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * EZP-26186: Make sure affected child locations are deleted on content deletion (affected Solr).
     */
    public function testDeleteContent()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $anonymousUsersContentId = $this->generateId('content', 42);
        $contentInfo = $contentService->loadContentInfo($anonymousUsersContentId);

        $contentService->deleteContent($contentInfo);

        $this->refreshSearch($repository);

        // Should not be found
        $criterion = new Criterion\ParentLocationId($contentInfo->mainLocationId);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(0, $result->totalCount);
    }

    /**
     * EZP-26186: Make sure index is deleted on removal of Users  (affected Solr).
     */
    public function testDeleteUser()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $searchService = $repository->getSearchService();

        $anonymousContentId = $this->generateId('user', 10);
        $user = $userService->loadUser($anonymousContentId);

        $userService->deleteUser($user);

        $this->refreshSearch($repository);

        // Should not be found
        $criterion = new Criterion\ContentId($user->id);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContentInfo($query);
        self::assertEquals(0, $result->totalCount);
    }

    /**
     * EZP-26186: Make sure index is deleted on removal of UserGroups  (affected Solr).
     */
    public function testDeleteUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $searchService = $repository->getSearchService();

        $membersContentId = $this->generateId('user_group', 11);
        $userGroup = $userService->loadUserGroup($membersContentId);

        $userService->deleteUserGroup($userGroup);

        $this->refreshSearch($repository);

        // Should not be found
        $criterion = new Criterion\ContentId($userGroup->id);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContentInfo($query);
        self::assertEquals(0, $result->totalCount);
    }

    /**
     * Test that a newly created user is available for search.
     */
    public function testCreateUser()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $searchService = $repository->getSearchService();

        // ID of the "Editors" user group
        $editorsGroupId = 13;
        $userCreate = $userService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US'
        );
        $userCreate->enabled = true;
        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', 'User');

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        // Create a new user instance.
        $user = $userService->createUser($userCreate, [$group]);

        $this->refreshSearch($repository);

        // Should be found
        $criterion = new Criterion\ContentId($user->id);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContentInfo($query);
        self::assertEquals(1, $result->totalCount);
    }

    /**
     * Test that a newly updated user is available for search.
     */
    public function testUpdateUser()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $user = $this->createUserVersion1();

        $newName = 'Drizzt Do\'Urden';
        $userUpdate = $userService->newUserUpdateStruct();
        $userUpdate->contentUpdateStruct = $contentService->newContentUpdateStruct();
        $userUpdate->contentUpdateStruct->setField('first_name', $newName);

        $userService->updateUser($user, $userUpdate);

        $this->refreshSearch($repository);

        // Should be found
        $query = new Query(
            [
                'query' => new Criterion\FullText($newName),
            ]
        );
        $result = $searchService->findContentInfo($query);

        self::assertEquals(1, $result->totalCount);
    }

    /**
     * Test that a newly created user group is available for search.
     */
    public function testCreateUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $searchService = $repository->getSearchService();

        $mainGroupId = $this->generateId('group', 4);

        $parentUserGroup = $userService->loadUserGroup($mainGroupId);
        $userGroupCreateStruct = $userService->newUserGroupCreateStruct('eng-GB');
        $userGroupCreateStruct->setField('name', 'Example Group');

        // Create a new user group
        $userGroup = $userService->createUserGroup(
            $userGroupCreateStruct,
            $parentUserGroup
        );

        $this->refreshSearch($repository);

        // Should be found
        $criterion = new Criterion\ContentId($userGroup->id);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContentInfo($query);
        self::assertEquals(1, $result->totalCount);
    }

    /**
     * Test that a newly created Location is available for search.
     */
    public function testCreateLocation()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $membersLocation = $this->createNewTestLocation();

        $this->refreshSearch($repository);

        // Found
        $criterion = new Criterion\LocationId($membersLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $membersLocation->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test that hiding a Location makes it unavailable for search.
     */
    public function testHideSubtree()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        // 5 is the ID of an existing location
        $locationId = $this->generateId('location', 5);
        $locationService = $repository->getLocationService();
        $location = $locationService->loadLocation($locationId);
        $locationService->hideLocation($location);
        $this->refreshSearch($repository);

        // Check if parent location is hidden
        $criterion = new Criterion\LocationId($locationId);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertTrue($result->searchHits[0]->valueObject->hidden);

        // Check if children locations are invisible
        $this->assertSubtreeInvisibleProperty($searchService, $locationId, true);
    }

    /**
     * Test that hiding and revealing a Location makes it available for search.
     */
    public function testRevealSubtree()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        // 5 is the ID of an existing location
        $locationId = $this->generateId('location', 5);
        $locationService = $repository->getLocationService();
        $location = $locationService->loadLocation($locationId);
        $locationService->hideLocation($location);
        $this->refreshSearch($repository);
        $locationService->unhideLocation($location);
        $this->refreshSearch($repository);

        // Check if parent location is not hidden
        $criterion = new Criterion\LocationId($locationId);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertFalse($result->searchHits[0]->valueObject->hidden);

        // Check if children locations are not invisible
        $this->assertSubtreeInvisibleProperty($searchService, $locationId, false);
    }

    /**
     * Test that a copied subtree is available for search.
     */
    public function testCopySubtree()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $rootLocationId = 2;
        $membersContentId = 11;
        $adminsContentId = 12;
        $editorsContentId = 13;
        $membersContentInfo = $contentService->loadContentInfo($membersContentId);
        $adminsContentInfo = $contentService->loadContentInfo($adminsContentId);
        $editorsContentInfo = $contentService->loadContentInfo($editorsContentId);

        $locationCreateStruct = $locationService->newLocationCreateStruct($rootLocationId);
        $membersLocation = $locationService->createLocation($membersContentInfo, $locationCreateStruct);
        $editorsLocation = $locationService->createLocation($editorsContentInfo, $locationCreateStruct);
        $adminsLocation = $locationService->createLocation(
            $adminsContentInfo,
            $locationService->newLocationCreateStruct($membersLocation->id)
        );

        $copiedLocation = $locationService->copySubtree($adminsLocation, $editorsLocation);
        $this->refreshSearch($repository);

        // Found under Members
        $criterion = new Criterion\ParentLocationId($membersLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $adminsLocation->id,
            $result->searchHits[0]->valueObject->id
        );

        // Found under Editors
        $criterion = new Criterion\ParentLocationId($editorsLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $copiedLocation->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test that moved subtree is available for search and found only under a specific parent Location.
     */
    public function testMoveSubtree()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();

        $rootLocationId = 2;
        $membersContentId = 11;
        $adminsContentId = 12;
        $editorsContentId = 13;
        $membersContentInfo = $contentService->loadContentInfo($membersContentId);
        $adminsContentInfo = $contentService->loadContentInfo($adminsContentId);
        $editorsContentInfo = $contentService->loadContentInfo($editorsContentId);

        $locationCreateStruct = $locationService->newLocationCreateStruct($rootLocationId);
        $membersLocation = $locationService->createLocation($membersContentInfo, $locationCreateStruct);
        $editorsLocation = $locationService->createLocation($editorsContentInfo, $locationCreateStruct);
        $adminsLocation = $locationService->createLocation(
            $adminsContentInfo,
            $locationService->newLocationCreateStruct($membersLocation->id)
        );

        $this->refreshSearch($repository);

        // Not found under Editors
        $criterion = new Criterion\ParentLocationId($editorsLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(0, $result->totalCount);

        // Found under Members
        $criterion = new Criterion\ParentLocationId($membersLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $adminsLocation->id,
            $result->searchHits[0]->valueObject->id
        );

        $locationService->moveSubtree($adminsLocation, $editorsLocation);
        $this->refreshSearch($repository);

        // Found under Editors
        $criterion = new Criterion\ParentLocationId($editorsLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $adminsLocation->id,
            $result->searchHits[0]->valueObject->id
        );

        // Not found under Members
        $criterion = new Criterion\ParentLocationId($membersLocation->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        self::assertEquals(0, $result->totalCount);
    }

    /**
     * Testing that content is indexed even when containing only fields with values
     * considered to be empty by the search engine.
     */
    public function testIndexContentWithNullField()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $searchService = $repository->getSearchService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct('test-type');
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Test type'];
        $createStruct->creatorId = 14;
        $createStruct->creationDate = new DateTime();

        $translatableFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct(
            'integer',
            'ibexa_integer'
        );
        $translatableFieldCreate->names = ['eng-GB' => 'Simple translatable integer field'];
        $translatableFieldCreate->fieldGroup = 'main';
        $translatableFieldCreate->position = 1;
        $translatableFieldCreate->isTranslatable = true;
        $translatableFieldCreate->isSearchable = true;

        $createStruct->addFieldDefinition($translatableFieldCreate);

        $contentGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');
        $contentTypeDraft = $contentTypeService->createContentType(
            $createStruct,
            [$contentGroup]
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentType = $contentTypeService->loadContentType($contentTypeDraft->id);

        $createStruct = $contentService->newContentCreateStruct($contentType, 'eng-GB');
        $createStruct->alwaysAvailable = false;
        $createStruct->mainLanguageCode = 'eng-GB';

        $draft = $contentService->createContent($createStruct);
        $content = $contentService->publishVersion($draft->getVersionInfo());

        $this->refreshSearch($repository);

        // Found
        $criterion = new Criterion\ContentId($content->id);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);
        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $content->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Test that updated Location is available for search.
     */
    public function testUpdateLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $rootLocationId = 2;
        $locationToUpdate = $locationService->loadLocation($rootLocationId);

        $criterion = new Criterion\LogicalAnd([
            new Criterion\LocationId($rootLocationId),
            new Criterion\Location\Priority(Criterion\Operator::GT, 0),
        ]);

        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);

        self::assertEquals(0, $result->totalCount);

        $locationUpdateStruct = $locationService->newLocationUpdateStruct();
        $locationUpdateStruct->priority = 4;
        $locationService->updateLocation($locationToUpdate, $locationUpdateStruct);

        $this->refreshSearch($repository);

        $result = $searchService->findLocations($query);

        self::assertEquals(1, $result->totalCount);
        self::assertEquals(
            $locationToUpdate->id,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Testing that content will be deleted with all of its subitems but subitems with additional location will stay as
     * they are.
     */
    public function testDeleteLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();

        $treeContainerContent = $this->createContentWithName('Tree Container', [2]);
        $supposeBeDeletedSubItem = $this->createContentWithName(
            'Suppose to be deleted sub-item',
            [$treeContainerContent->contentInfo->mainLocationId]
        );
        $supposeSurviveSubItem = $this->createContentWithName(
            'Suppose to Survive Item',
            [2, $treeContainerContent->contentInfo->mainLocationId]
        );

        $treeContainerLocation = $locationService->loadLocation($treeContainerContent->contentInfo->mainLocationId);

        $this->refreshSearch($repository);

        $this->assertContentIdSearch($treeContainerContent->id, 1);
        $this->assertContentIdSearch($supposeSurviveSubItem->id, 1);
        $this->assertContentIdSearch($supposeBeDeletedSubItem->id, 1);

        $locationService->deleteLocation($treeContainerLocation);

        $this->refreshSearch($repository);

        $this->assertContentIdSearch($supposeSurviveSubItem->id, 1);
        $this->assertContentIdSearch($treeContainerContent->id, 0);
        $this->assertContentIdSearch($supposeBeDeletedSubItem->id, 0);
    }

    /**
     * Test content is available for search after being published.
     */
    public function testPublishVersion()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $publishedContent = $this->createContentWithName('publishedContent', [2]);
        $this->refreshSearch($repository);

        $criterion = new Criterion\FullText('publishedContent');
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);

        self::assertCount(1, $result->searchHits);
        self::assertEquals($publishedContent->contentInfo->id, $result->searchHits[0]->valueObject->contentInfo->id);

        // Searching for children of locationId=2 should also hit this content
        $criterion = new Criterion\ParentLocationId(2);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);

        foreach ($result->searchHits as $searchHit) {
            if ($searchHit->valueObject->contentInfo->id === $publishedContent->contentInfo->id) {
                return;
            }
        }
        self::fail('Parent location sub-items do not contain published content');
    }

    /**
     * Test recovered content is available for search.
     */
    public function testRecoverLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $trashService = $repository->getTrashService();
        $searchService = $repository->getSearchService();

        $publishedContent = $this->createContentWithName('recovery-test', [2]);
        $location = $locationService->loadLocation($publishedContent->contentInfo->mainLocationId);

        $trashService->trash($location);
        $this->refreshSearch($repository);

        $criterion = new Criterion\LocationId($location->id);
        $query = new LocationQuery(['filter' => $criterion]);
        $locations = $searchService->findLocations($query);
        self::assertEquals(0, $locations->totalCount);

        $trashItem = $trashService->loadTrashItem($location->id);
        $trashService->recover($trashItem);
        $this->refreshSearch($repository);

        $locations = $searchService->findLocations($query);
        self::assertEquals(0, $locations->totalCount);
        $this->assertContentIdSearch($publishedContent->contentInfo->id, 1);
    }

    /**
     * Test copied content is available for search.
     */
    public function testCopyContent()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $publishedContent = $this->createContentWithName('copyTest', [2]);
        $this->refreshSearch($repository);
        $criterion = new Criterion\FullText('copyTest');
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);
        self::assertCount(1, $result->searchHits);

        $copiedContent = $contentService->copyContent($publishedContent->contentInfo, $locationService->newLocationCreateStruct(2));
        $this->refreshSearch($repository);
        $result = $searchService->findContent($query);
        self::assertCount(2, $result->searchHits);

        $this->assertContentIdSearch($publishedContent->contentInfo->id, 1);
        $this->assertContentIdSearch($copiedContent->contentInfo->id, 1);
    }

    /**
     * Test that setting object content state to locked and then unlocked does not affect search index.
     */
    public function testSetContentState()
    {
        $repository = $this->getRepository();
        $objectStateService = $repository->getObjectStateService();

        // get Object States
        $stateNotLocked = $objectStateService->loadObjectState(1);
        $stateLocked = $objectStateService->loadObjectState(2);

        $publishedContent = $this->createContentWithName('setContentStateTest', [2]);
        $objectStateService->setContentState($publishedContent->contentInfo, $stateLocked->getObjectStateGroup(), $stateLocked);
        $this->refreshSearch($repository);

        // Setting Content State to "locked" should not affect search index
        $this->assertContentIdSearch($publishedContent->contentInfo->id, 1);

        $objectStateService->setContentState($publishedContent->contentInfo, $stateNotLocked->getObjectStateGroup(), $stateNotLocked);
        $this->refreshSearch($repository);

        // Setting Content State back to "not locked" should not affect search index
        $this->assertContentIdSearch($publishedContent->contentInfo->id, 1);
    }

    /**
     * Check if FullText indexing works for special cases of text.
     *
     * @param string $text Content Item field value text (to be indexed)
     * @param string $searchForText text based on which Content Item should be found
     * @param array $ignoreForSetupFactories list of SetupFactories to be ignored
     *
     * @dataProvider getSpecialFullTextCases
     */
    public function testIndexingSpecialFullTextCases($text, $searchForText)
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $content = $this->createContentWithName($text, [2]);
        $this->refreshSearch($repository);

        $criterion = new Criterion\FullText($searchForText);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);

        $found = false;
        // for some cases there might be more than one hit, so check if proper one was found
        foreach ($result->searchHits as $searchHit) {
            if ($content->contentInfo->id === $searchHit->valueObject->versionInfo->contentInfo->id) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'Failed to find required Content in search results');
    }

    /**
     * Check if FullText indexing works for email addresses.
     *
     * @dataProvider getEmailAddressesCases
     */
    public function testIndexingEmailFieldCases(string $email, string $searchForText): void
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();

        $content = $this->createContentEmailWithAddress($email, [2]);
        $this->refreshSearch($repository);

        $criterion = new Criterion\FullText($searchForText);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);

        $found = false;
        // for some cases there might be more than one hit, so check if proper one was found
        foreach ($result->searchHits as $searchHit) {
            if ($content->contentInfo->id === $searchHit->valueObject->versionInfo->contentInfo->id) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'Failed to find required Content in search results');
    }

    /**
     * Data Provider for {@see testIndexingSpecialFullTextCases()} method.
     */
    public function getEmailAddressesCases(): array
    {
        return [
            ['test@TEST.com', 'test@test.com'],
            ['TEST3@TEST.com', 'test3@test.com'],
            ['TeSt1@TEST.com', 'test1@test.com'],
            ['TeSt2@TesT.com', 'test2@test.com'],
            ['test4@test.com', 'test4@test.com'],
        ];
    }

    /**
     * Data Provider for {@see testIndexingSpecialFullTextCases()} method.
     *
     * @return array
     */
    public function getSpecialFullTextCases()
    {
        return [
            ['UPPERCASE TEXT', 'uppercase text'],
            ['lowercase text', 'LOWERCASE TEXT'],
            ['text-with-hyphens', 'text-with-hyphens'],
            ['text containing spaces', 'text containing spaces'],
            ['"quoted text"', 'quoted text'],
            ['ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝ', 'àáâãäåçèéêëìíîïðñòóôõöøùúûüý'],
            ['with boundary.', 'with boundary'],
            ['Folder1.', 'Folder1.'],
            ['whitespaces', "     whitespaces  \n \t "],
            ["it's", "it's"],
            ['with_underscore', 'with_underscore'],
            ['MAKİNEİÇ', 'makİneİç'],
            ['DIŞ', 'diş'],
            ['TİC', 'tİc'],
            ['ŞTİ.', 'ştİ'],
            ['ʻ', 'ʻ'],
        ];
    }

    /**
     * Test FullText search on user first name and last name.
     *
     * @see https://issues.ibexa.co/browse/EZP-27250
     */
    public function testUserFullTextSearch()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $user = $this->createUser('TestUser', 'Jon', 'Snow');

        $criterion = new Criterion\LogicalAnd(
            [
                new Criterion\FullText('Jon Snow'),
                new Criterion\ContentTypeIdentifier('user'),
            ]
        );
        $query = new Query(['filter' => $criterion]);
        $this->refreshSearch($repository);
        $results = $searchService->findContent($query);
        self::assertEquals(1, $results->totalCount);
        self::assertEquals($user->id, $results->searchHits[0]->valueObject->id);
    }

    /**
     * Test updating Content field value with empty value removes it from search index.
     */
    public function testRemovedContentFieldValueIsNotFound()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $searchService = $repository->getSearchService();
        $publishedContent = $this->createContentWithNameAndDescription('testRemovedContentFieldValueIsNotFound', 'descriptionToBeRemoved', [2]);
        $this->refreshSearch($repository);

        $contentDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('description', null);
        $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
        $contentService->publishVersion($contentDraft->versionInfo);
        $this->refreshSearch($repository);

        // Removed field value should not be found
        $criterion = new Criterion\FullText('descriptionToBeRemoved');
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContent($query);
        self::assertEquals(0, $results->totalCount);

        // Should be found
        $criterion = new Criterion\FullText('testRemovedContentFieldValueIsNotFound');
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContent($query);
        self::assertEquals(1, $results->totalCount);
    }

    /**
     * Check if children locations are/are not ivisible.
     *
     * @param \Ibexa\Contracts\Core\Repository\SearchService $searchService
     * @param int $parentLocationId parent location Id
     * @param bool $expected expected value of {invisible} property in subtree
     */
    private function assertSubtreeInvisibleProperty(SearchService $searchService, $parentLocationId, $expected)
    {
        $criterion = new Criterion\ParentLocationId($parentLocationId);
        $query = new LocationQuery(['filter' => $criterion]);
        $result = $searchService->findLocations($query);
        foreach ($result->searchHits as $searchHit) {
            self::assertEquals($expected, $searchHit->valueObject->invisible, sprintf('Location %s is not hidden', $searchHit->valueObject->id));
            // Perform recursive check for children locations
            $this->assertSubtreeInvisibleProperty($searchService, $searchHit->valueObject->id, $expected);
        }
    }

    /**
     * Test that swapping locations affects properly Search Engine Index.
     */
    public function testSwapLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $content01 = $this->createContentWithName('content01', [2]);
        $location01 = $locationService->loadLocation($content01->contentInfo->mainLocationId);

        $content02 = $this->createContentWithName('content02', [2]);
        $location02 = $locationService->loadLocation($content02->contentInfo->mainLocationId);

        $locationService->swapLocation($location01, $location02);
        $this->refreshSearch($repository);

        // content02 should be at location01
        $criterion = new Criterion\LocationId($location01->id);
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContent($query);
        self::assertEquals(1, $results->totalCount);
        self::assertEquals($content02->id, $results->searchHits[0]->valueObject->id);

        // content01 should be at location02
        $criterion = new Criterion\LocationId($location02->id);
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContent($query);
        self::assertEquals(1, $results->totalCount);
        self::assertEquals($content01->id, $results->searchHits[0]->valueObject->id);
    }

    /**
     * Test that updating Content metadata affects properly Search Engine Index.
     */
    public function testUpdateContentMetadata()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $searchService = $repository->getSearchService();

        $publishedContent = $this->createContentWithName('updateMetadataTest', [2]);
        $originalMainLocationId = $publishedContent->contentInfo->mainLocationId;
        $newLocationCreateStruct = $locationService->newLocationCreateStruct(60);
        $newLocation = $locationService->createLocation($publishedContent->contentInfo, $newLocationCreateStruct);

        $newContentMetadataUpdateStruct = $contentService->newContentMetadataUpdateStruct();
        $newContentMetadataUpdateStruct->remoteId = md5('Test');
        $newContentMetadataUpdateStruct->publishedDate = new \DateTime();
        $newContentMetadataUpdateStruct->publishedDate->add(new \DateInterval('P1D'));
        $newContentMetadataUpdateStruct->mainLocationId = $newLocation->id;

        $contentService->updateContentMetadata($publishedContent->contentInfo, $newContentMetadataUpdateStruct);
        $this->refreshSearch($repository);

        // find Content by Id, calling findContentInfo which is using the Search Index
        $criterion = new Criterion\ContentId($publishedContent->id);
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContentInfo($query);
        self::assertEquals(1, $results->totalCount);
        self::assertEquals($publishedContent->contentInfo->id, $results->searchHits[0]->valueObject->id);

        // find Content using updated RemoteId
        $criterion = new Criterion\RemoteId($newContentMetadataUpdateStruct->remoteId);
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContent($query);
        self::assertEquals(1, $results->totalCount);
        $foundContentInfo = $results->searchHits[0]->valueObject->contentInfo;
        /** @var \Ibexa\Core\Repository\Values\Content\Content $foundContentInfo */
        self::assertEquals($publishedContent->id, $foundContentInfo->id);
        self::assertEquals($newContentMetadataUpdateStruct->publishedDate->getTimestamp(), $foundContentInfo->publishedDate->getTimestamp());
        self::assertEquals($newLocation->id, $foundContentInfo->mainLocationId);
        self::assertEquals($newContentMetadataUpdateStruct->remoteId, $foundContentInfo->remoteId);

        // find Content using old main location
        $criterion = new Criterion\LocationId($originalMainLocationId);
        $query = new LocationQuery(['filter' => $criterion]);
        $results = $searchService->findLocations($query);
        self::assertEquals(1, $results->totalCount);
        self::assertEquals($newContentMetadataUpdateStruct->remoteId, $results->searchHits[0]->valueObject->contentInfo->remoteId);
    }

    /**
     * Test that updating Content Draft metadata does not affect Search Engine Index.
     */
    public function testUpdateContentDraftMetadataIsNotIndexed()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $testableContentType = $this->createTestContentType();
        $rootContentStruct = $contentService->newContentCreateStruct($testableContentType, 'eng-GB');
        $rootContentStruct->setField('name', 'TestUpdatingContentDraftMetadata');

        $contentDraft = $contentService->createContent($rootContentStruct, [$locationService->newLocationCreateStruct(2)]);

        $newContentMetadataUpdateStruct = $contentService->newContentMetadataUpdateStruct();
        $newContentMetadataUpdateStruct->ownerId = 10;
        $newContentMetadataUpdateStruct->remoteId = md5('Test');

        $contentService->updateContentMetadata($contentDraft->contentInfo, $newContentMetadataUpdateStruct);

        $this->refreshSearch($repository);
        $this->assertContentIdSearch($contentDraft->contentInfo->id, 0);
    }

    /**
     * Test that assigning section to content object properly affects Search Engine Index.
     */
    public function testAssignSection()
    {
        $repository = $this->getRepository();
        $sectionService = $repository->getSectionService();
        $searchService = $repository->getSearchService();

        $section = $sectionService->loadSection(2);
        $content = $this->createContentWithName('testAssignSection', [2]);

        $sectionService->assignSection($content->contentInfo, $section);
        $this->refreshSearch($repository);

        $criterion = new Criterion\ContentId($content->id);
        $query = new Query(['filter' => $criterion]);
        $results = $searchService->findContentInfo($query);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo */
        $contentInfo = $results->searchHits[0]->valueObject;
        self::assertEquals($section->id, $contentInfo->getSectionId());
    }

    /**
     * Test search engine is updated after removal of the translation from all the Versions.
     */
    public function testDeleteTranslation()
    {
        $repository = $this->getRepository();
        $searchService = $repository->getSearchService();
        $contentService = $repository->getContentService();

        $content = $this->createMultiLanguageContent(
            [
                'eng-US' => 'AmE Name',
                'eng-GB' => 'BrE Name',
            ],
            2,
            false
        );

        $contentService->deleteTranslation($content->contentInfo, 'eng-GB');

        $this->refreshSearch($repository);

        // Test ContentId search returns Content without removed Translation
        $query = new Query([
            'query' => new Criterion\ContentId($content->contentInfo->id),
            'filter' => new Criterion\LanguageCode('eng-GB', false),
        ]);
        $result = $searchService->findContent($query);
        self::assertEquals(0, $result->totalCount);

        // Test FullText search for removed unique name part returns no results
        $query = new Query([
            'query' => new Criterion\FullText('BrE'),
        ]);
        $result = $searchService->findContent($query);
        self::assertEquals(0, $result->totalCount);

        // Test Location Search returns Content without removed Translation
        $query = new LocationQuery(
            [
                'query' => new Criterion\FullText('BrE'),
            ]
        );
        $result = $searchService->findLocations($query);
        self::assertEquals(0, $result->totalCount);
    }

    /**
     * Will create if not exists a simple content type for test purposes with just one required field.
     */
    protected function createTestContentType(
        string $identifier = 'name',
        string $fieldTypeIdentifier = 'ibexa_string',
        string $contentTypeIdentifier = 'test-type'
    ): ContentType {
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        try {
            return $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
        } catch (NotFoundException $e) {
            // continue creation process
        }

        $nameField = $contentTypeService->newFieldDefinitionCreateStruct($identifier, $fieldTypeIdentifier);
        $nameField->fieldGroup = 'main';
        $nameField->position = 1;
        $nameField->isTranslatable = true;
        $nameField->isSearchable = true;
        $nameField->isRequired = true;

        $contentTypeStruct = $contentTypeService->newContentTypeCreateStruct($contentTypeIdentifier);
        $contentTypeStruct->mainLanguageCode = 'eng-GB';
        $contentTypeStruct->creatorId = 14;
        $contentTypeStruct->creationDate = new DateTime();
        $contentTypeStruct->names = ['eng-GB' => 'Test content type'];
        $contentTypeStruct->addFieldDefinition($nameField);

        $contentTypeGroup = $contentTypeService->loadContentTypeGroupByIdentifier('Content');

        $contentTypeDraft = $contentTypeService->createContentType($contentTypeStruct, [$contentTypeGroup]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier($contentTypeIdentifier);
    }

    /**
     * Will create and publish an content with a filed with a given content name in location provided into
     * $parentLocationIdList.
     *
     * @param int[] $parentLocationIdList
     */
    protected function createContentWithName(string $contentName, array $parentLocationIdList = []): Content
    {
        $testableContentType = $this->createTestContentType();

        return $this->createContent($testableContentType, $contentName, 'name', $parentLocationIdList);
    }

    /**
     * Will create and publish an content with an email filed with a given content name in location provided into
     * $parentLocationIdList.
     *
     * @param string $address
     * @param int[] $parentLocationIdList
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createContentEmailWithAddress(string $address, array $parentLocationIdList = []): Content
    {
        $testableContentType = $this->createTestContentType('email', 'ibexa_email', 'test-email-type');

        return $this->createContent($testableContentType, $address, 'email', $parentLocationIdList);
    }

    protected function createContent(
        ContentType $testableContentType,
        string $contentName,
        string $fieldDefIdentifier,
        array $parentLocationIdList
    ): Content {
        $contentService = $this->getRepository()->getContentService();
        $locationService = $this->getRepository()->getLocationService();

        $rootContentStruct = $contentService->newContentCreateStruct($testableContentType, 'eng-GB');
        $rootContentStruct->setField($fieldDefIdentifier, $contentName);

        $parentLocationList = [];
        foreach ($parentLocationIdList as $locationID) {
            $parentLocationList[] = $locationService->newLocationCreateStruct($locationID);
        }

        $contentDraft = $contentService->createContent($rootContentStruct, $parentLocationList);

        return $contentService->publishVersion($contentDraft->getVersionInfo());
    }

    /**
     * Create and publish a content with filled name and description fields in location provided into
     * $parentLocationIdList.
     *
     * @param string $contentName
     * @param $contentDescription
     * @param array $parentLocationIdList
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createContentWithNameAndDescription($contentName, $contentDescription, array $parentLocationIdList = [])
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $publishedContent = $this->createContentWithName($contentName, $parentLocationIdList);
        $descriptionField = $contentTypeService->newFieldDefinitionCreateStruct('description', 'ibexa_string');
        $descriptionField->fieldGroup = 'main';
        $descriptionField->position = 2;
        $descriptionField->isTranslatable = true;
        $descriptionField->isSearchable = true;
        $descriptionField->isRequired = false;
        $contentType = $contentTypeService->loadContentType($publishedContent->contentInfo->contentTypeId);
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $contentTypeService->addFieldDefinition($contentTypeDraft, $descriptionField);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);
        $contentDraft = $contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = $contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('description', $contentDescription);
        $contentDraft = $contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);

        return $contentService->publishVersion($contentDraft->versionInfo);
    }

    /**
     * Create and publish a content with specified, in multiple languages, fields.
     *
     * @param string[] $names multi-language name field in the form of: <code>['lang-code' => 'name']</code>
     * @param int $parentLocationId
     * @param bool $alwaysAvailable
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     */
    protected function createMultiLanguageContent(array $names, $parentLocationId, $alwaysAvailable)
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        $testableContentType = $this->createTestContentType();

        $contentCreateStruct = $contentService->newContentCreateStruct(
            $testableContentType,
            array_keys($names)[0]
        );

        foreach ($names as $languageCode => $value) {
            $contentCreateStruct->setField('name', $value, $languageCode);
        }

        $contentCreateStruct->alwaysAvailable = $alwaysAvailable;

        $contentDraft = $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct($parentLocationId),
            ]
        );
        $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

        return $publishedContent;
    }

    /**
     * Asserts an content id if it exists still in the solr core.
     *
     * @param int $contentId
     * @param int $expectedCount
     */
    protected function assertContentIdSearch($contentId, $expectedCount)
    {
        $searchService = $this->getRepository()->getSearchService();

        $criterion = new Criterion\ContentId($contentId);
        $query = new Query(['filter' => $criterion]);
        $result = $searchService->findContent($query);

        self::assertEquals($expectedCount, $result->totalCount);
        if ($expectedCount == 0) {
            return;
        }

        self::assertEquals(
            $contentId,
            $result->searchHits[0]->valueObject->id
        );
    }

    /**
     * Create & get new Location for tests.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location
     */
    protected function createNewTestLocation()
    {
        $repository = $this->getRepository();
        $locationService = $repository->getLocationService();
        $contentService = $repository->getContentService();

        $rootLocationId = 2;
        $membersContentId = 11;
        $membersContentInfo = $contentService->loadContentInfo($membersContentId);

        $locationCreateStruct = $locationService->newLocationCreateStruct($rootLocationId);

        return $locationService->createLocation($membersContentInfo, $locationCreateStruct);
    }
}
