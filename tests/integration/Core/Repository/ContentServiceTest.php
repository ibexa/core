<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Exception;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException as APIInvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentMetadataUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\Content\DraftList\Item\UnauthorizedContentDraftListItem;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\RelationType;
use Ibexa\Contracts\Core\Repository\Values\Content\Section;
use Ibexa\Contracts\Core\Repository\Values\Content\URLAlias;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\ContentTypeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SectionLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Core\Base\Exceptions\UnauthorizedException as CoreUnauthorizedException;
use Ibexa\Core\FieldType\Relation\Value as RelationValue;
use Ibexa\Core\Repository\Values\Content\ContentUpdateStruct;
use InvalidArgumentException;
use ReflectionClass;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * Test case for operations in the ContentService using in memory storage.
 *
 * @see \Ibexa\Contracts\Core\Repository\ContentService
 *
 * @group content
 */
class ContentServiceTest extends BaseContentServiceTestCase
{
    private const ADMINISTRATORS_USER_GROUP_NAME = 'Administrators';
    private const ADMINISTRATORS_USER_GROUP_ID = 12;
    private const ADMINISTRATORS_USER_GROUP_LOCATION_ID = 13;

    private const WRITERS_USER_GROUP_NAME = 'Writers';

    private const MEMBERS_USER_GROUP_ID = 11;

    private const MEDIA_CONTENT_ID = 41;

    private const MEDIA_REMOTE_ID = 'a6e35cbcb7cd6ae4b691f3eee30cd262';
    private const DEMO_DESIGN_REMOTE_ID = '8b8b22fe3c6061ed500fbd2b377b885f';

    private const FORUM_IDENTIFIER = 'forum';

    private const ENG_US = 'eng-US';
    private const GER_DE = 'ger-DE';
    private const ENG_GB = 'eng-GB';

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    /** @var \Ibexa\Contracts\Core\Repository\LocationService */
    private $locationService;

    public function setUp(): void
    {
        parent::setUp();

        $repository = $this->getRepository();
        $this->permissionResolver = $repository->getPermissionResolver();
        $this->contentService = $repository->getContentService();
        $this->locationService = $repository->getLocationService();
    }

    /**
     * Test for the newContentCreateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::newContentCreateStruct()
     *
     * @depends Ibexa\Tests\Integration\Core\Repository\ContentTypeServiceTest::testLoadContentTypeByIdentifier
     *
     * @group user
     * @group field-type
     */
    public function testNewContentCreateStruct()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);

        self::assertInstanceOf(ContentCreateStruct::class, $contentCreate);
    }

    /**
     * Test for the createContent() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testNewContentCreateStruct
     *
     * @group user
     * @group field-type
     */
    public function testCreateContent()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'My awesome forum');

        $contentCreate->remoteId = 'abcdef0123456789abcdef0123456789';
        $contentCreate->alwaysAvailable = true;

        $content = $this->contentService->createContent($contentCreate);

        self::assertInstanceOf(Content::class, $content);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     * Tests made for issue #EZP-20955 where Anonymous user is granted access to create content
     * and should have access to do that.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @group user
     * @group field-type
     */
    public function testCreateContentAndPublishWithPrivilegedAnonymousUser(): void
    {
        $anonymousUserId = $this->generateId('user', 10);

        $repository = $this->getRepository();
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $roleService = $repository->getRoleService();

        // Give Anonymous user role additional rights
        $role = $roleService->loadRoleByIdentifier('Anonymous');
        $roleDraft = $roleService->createRoleDraft($role);
        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'create');
        $policyCreateStruct->addLimitation(new SectionLimitation(['limitationValues' => [1]]));
        $policyCreateStruct->addLimitation(new LocationLimitation(['limitationValues' => [2]]));
        $policyCreateStruct->addLimitation(new ContentTypeLimitation(['limitationValues' => [1]]));
        $roleDraft = $roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);

        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'publish');
        $policyCreateStruct->addLimitation(new SectionLimitation(['limitationValues' => [1]]));
        $policyCreateStruct->addLimitation(new LocationLimitation(['limitationValues' => [2]]));
        $policyCreateStruct->addLimitation(new ContentTypeLimitation(['limitationValues' => [1]]));
        $roleDraft = $roleService->addPolicyByRoleDraft($roleDraft, $policyCreateStruct);
        $roleService->publishRoleDraft($roleDraft);

        // Set Anonymous user as current
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($anonymousUserId));

        // Create a new content object:
        $contentCreate = $this->contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            self::ENG_GB
        );

        $contentCreate->setField('name', 'Folder 1');

        $content = $this->contentService->createContent(
            $contentCreate,
            [$this->locationService->newLocationCreateStruct(2)]
        );

        $this->contentService->publishVersion(
            $content->getVersionInfo()
        );
    }

    /**
     * Test for the createContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentSetsContentInfo($content)
    {
        self::assertInstanceOf(ContentInfo::class, $content->contentInfo);

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContentSetsContentInfo
     */
    public function testCreateContentSetsExpectedContentInfo($content)
    {
        $permissionResolver = $this->getRepository()->getPermissionResolver();

        self::assertEquals(
            [
                $content->id,
                28, // id of content type "forum"
                true,
                1,
                'abcdef0123456789abcdef0123456789',
                self::ENG_US,
                $permissionResolver->getCurrentUserReference()->getUserId(),
                false,
                null,
                // Main Location id for unpublished Content should be null
                null,
            ],
            [
                $content->contentInfo->id,
                $content->contentInfo->contentTypeId,
                $content->contentInfo->alwaysAvailable,
                $content->contentInfo->currentVersionNo,
                $content->contentInfo->remoteId,
                $content->contentInfo->mainLanguageCode,
                $content->contentInfo->ownerId,
                $content->contentInfo->published,
                $content->contentInfo->publishedDate,
                $content->contentInfo->mainLocationId,
            ]
        );
    }

    /**
     * Test for the createContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentSetsVersionInfo($content)
    {
        self::assertInstanceOf(VersionInfo::class, $content->getVersionInfo());

        return $content;
    }

    /**
     * Test for the createContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContentSetsVersionInfo
     */
    public function testCreateContentSetsExpectedVersionInfo($content)
    {
        $currentUserReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                'status' => VersionInfo::STATUS_DRAFT,
                'versionNo' => 1,
                'creatorId' => $currentUserReference->getUserId(),
                'initialLanguageCode' => self::ENG_US,
            ],
            [
                'status' => $content->getVersionInfo()->status,
                'versionNo' => $content->getVersionInfo()->versionNo,
                'creatorId' => $content->getVersionInfo()->creatorId,
                'initialLanguageCode' => $content->getVersionInfo()->initialLanguageCode,
            ]
        );
        self::assertTrue($content->getVersionInfo()->isDraft());
        self::assertFalse($content->getVersionInfo()->isPublished());
        self::assertFalse($content->getVersionInfo()->isArchived());
    }

    /**
     * Test for the createContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentSetsExpectedContentType($content)
    {
        $contentType = $content->getContentType();

        self::assertEquals(
            [
                $contentType->id,
                // Won't match as it's set to true in createContentDraftVersion1()
                //$contentType->defaultAlwaysAvailable,
                //$contentType->defaultSortField,
                //$contentType->defaultSortOrder,
            ],
            [
                $content->contentInfo->contentTypeId,
                //$content->contentInfo->alwaysAvailable,
                //$location->sortField,
                //$location->sortOrder,
            ]
        );
    }

    /**
     * Test for the createContent() method with utilizing ContentType default options.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     */
    public function testCreateContentWithContentTypeDefaultOptions(): void
    {
        $contentType = $this->getRepository()->getContentTypeService()
            ->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_GB);
        $contentCreate->setField('name', 'Sorting Test');

        $content = $this->contentService->createContent(
            $contentCreate,
            [$this->locationService->newLocationCreateStruct(2)]
        );
        $publishedContent = $this->contentService->publishVersion($content->getVersionInfo());

        $location = $publishedContent->contentInfo->getMainLocation();

        self::assertEquals($contentType->defaultSortField, $location->sortField);
        self::assertEquals($contentType->defaultSortOrder, $location->sortOrder);
        self::assertEquals($contentType->defaultAlwaysAvailable, $publishedContent->contentInfo->alwaysAvailable);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentThrowsInvalidArgumentException()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate1 = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate1->setField('name', 'An awesome Sidelfingen forum');

        $contentCreate1->remoteId = 'abcdef0123456789abcdef0123456789';
        $contentCreate1->alwaysAvailable = true;

        $draft = $this->contentService->createContent($contentCreate1);
        $this->contentService->publishVersion($draft->versionInfo);

        $contentCreate2 = $this->contentService->newContentCreateStruct($contentType, self::ENG_GB);
        $contentCreate2->setField('name', 'An awesome Bielefeld forum');

        $contentCreate2->remoteId = 'abcdef0123456789abcdef0123456789';
        $contentCreate2->alwaysAvailable = false;

        $this->expectException(APIInvalidArgumentException::class);
        $this->contentService->createContent($contentCreate2);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentThrowsInvalidArgumentExceptionOnFieldTypeNotAccept()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        // The name field does only accept strings and null as its values
        $contentCreate->setField('name', new \stdClass());

        $this->expectException(APIInvalidArgumentException::class);
        $this->contentService->createContent($contentCreate);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentThrowsContentFieldValidationException()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate1 = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate1->setField('name', 'An awesome Sidelfingen folder');
        // Violates string length constraint
        $contentCreate1->setField('short_name', str_repeat('a', 200));

        $this->expectException(ContentFieldValidationException::class);

        // Throws ContentFieldValidationException, since short_name does not pass validation of the string length validator
        $this->contentService->createContent($contentCreate1);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     */
    public function testCreateContentRequiredFieldMissing()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreate1 = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        // Required field "name" is not set

        $this->expectException(ContentFieldValidationException::class);

        // Throws a ContentFieldValidationException, since a required field is missing
        $this->contentService->createContent($contentCreate1);
    }

    /**
     * Test for the createContent() method.
     *
     * NOTE: We have bidirectional dependencies between the ContentService and
     * the LocationService, so that we cannot use PHPUnit's test dependencies
     * here.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent
     *
     * @depends testCreateContent
     *
     * @group user
     */
    public function testCreateContentWithLocationCreateParameterDoesNotCreateLocationImmediately()
    {
        $this->createContentDraftVersion1();

        $this->expectException(NotFoundException::class);

        // The location will not have been created, yet, so this throws an exception
        $this->locationService->loadLocationByRemoteId('0123456789abcdef0123456789abcdef');
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent($contentCreateStruct, $locationCreateStructs)
     *
     * @depends testCreateContentWithLocationCreateParameterDoesNotCreateLocationImmediately
     */
    public function testCreateContentThrowsInvalidArgumentExceptionWithLocationCreateParameter()
    {
        $parentLocationId = $this->generateId('location', 56);
        // $parentLocationId is a valid location ID

        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        // Configure new locations
        $locationCreate1 = $this->locationService->newLocationCreateStruct($parentLocationId);

        $locationCreate1->priority = 23;
        $locationCreate1->hidden = true;
        $locationCreate1->remoteId = '0123456789abcdef0123456789aaaaaa';
        $locationCreate1->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate1->sortOrder = Location::SORT_ORDER_DESC;

        $locationCreate2 = $this->locationService->newLocationCreateStruct($parentLocationId);

        $locationCreate2->priority = 42;
        $locationCreate2->hidden = true;
        $locationCreate2->remoteId = '0123456789abcdef0123456789bbbbbb';
        $locationCreate2->sortField = Location::SORT_FIELD_NODE_ID;
        $locationCreate2->sortOrder = Location::SORT_ORDER_DESC;

        // Configure new content object
        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);

        $contentCreate->setField('name', 'A awesome Sindelfingen forum');
        $contentCreate->remoteId = 'abcdef0123456789abcdef0123456789';
        $contentCreate->alwaysAvailable = true;

        // Create new content object under the specified location
        $draft = $this->contentService->createContent(
            $contentCreate,
            [$locationCreate1]
        );
        $this->contentService->publishVersion($draft->versionInfo);

        $this->expectException(APIInvalidArgumentException::class);
        // Content remoteId already exists,
        $this->contentService->createContent(
            $contentCreate,
            [$locationCreate2]
        );
    }

    /**
     * Test for the loadContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo()
     *
     * @group user
     */
    public function testLoadContentInfo()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);

        // Load the ContentInfo for "Media" folder
        $contentInfo = $this->contentService->loadContentInfo($mediaFolderId);

        self::assertInstanceOf(ContentInfo::class, $contentInfo);

        return $contentInfo;
    }

    /**
     * Test for the returned value of the loadContentInfo() method.
     *
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoSetsExpectedContentInfo(ContentInfo $contentInfo)
    {
        $this->assertPropertiesCorrectUnsorted(
            $this->getExpectedMediaContentInfoProperties(),
            $contentInfo
        );
    }

    /**
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoGetContentType(ContentInfo $contentInfo): void
    {
        $contentType = $contentInfo->getContentType();

        self::assertInstanceOf(ContentType::class, $contentType);
        self::assertEquals('folder', $contentType->identifier);
    }

    /**
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoGetSection(ContentInfo $contentInfo): void
    {
        $section = $contentInfo->getSection();

        self::assertInstanceOf(Section::class, $section);
        self::assertEquals('media', $section->identifier);
    }

    /**
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoGetMainLanguage(ContentInfo $contentInfo): void
    {
        $language = $contentInfo->getMainLanguage();

        self::assertInstanceOf(Language::class, $language);
        self::assertEquals('eng-US', $language->languageCode);
    }

    /**
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoGetMainLocation(ContentInfo $contentInfo): void
    {
        $mainLocation = $contentInfo->getMainLocation();

        self::assertInstanceOf(Location::class, $mainLocation);
        self::assertEquals('75c715a51699d2d309a924eca6a95145', $mainLocation->remoteId);
    }

    /**
     * @depends testLoadContentInfo
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoSetsExpectedOwnerProxy(ContentInfo $contentInfo): void
    {
        $owner = $contentInfo->getOwner();

        self::assertInstanceOf(User::class, $owner);
        self::assertEquals('Administrator User', $owner->getName());
    }

    /**
     * Test for the loadContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfo()
     *
     * @depends testLoadContentInfo
     */
    public function testLoadContentInfoThrowsNotFoundException()
    {
        $nonExistentContentId = $this->generateId('object', self::DB_INT_MAX);

        $this->expectException(NotFoundException::class);

        $this->contentService->loadContentInfo($nonExistentContentId);
    }

    /**
     * Test for the loadContentInfoList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfoList()
     */
    public function testLoadContentInfoList()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        $list = iterator_to_array($this->contentService->loadContentInfoList([$mediaFolderId]));

        self::assertCount(1, $list);
        self::assertEquals([$mediaFolderId], array_keys($list), 'Array key was not content id');
        self::assertInstanceOf(
            ContentInfo::class,
            $list[$mediaFolderId]
        );
    }

    /**
     * Test for the loadContentInfoList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfoList()
     *
     * @depends testLoadContentInfoList
     */
    public function testLoadContentInfoListSkipsNotFoundItems()
    {
        $nonExistentContentId = $this->generateId('object', self::DB_INT_MAX);
        $list = $this->contentService->loadContentInfoList([$nonExistentContentId]);

        self::assertCount(0, $list);
    }

    /**
     * Test for the loadContentInfoByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfoByRemoteId()
     */
    public function testLoadContentInfoByRemoteId()
    {
        // Load the ContentInfo for "Media" folder
        $contentInfo = $this->contentService->loadContentInfoByRemoteId('faaeb9be3bd98ed09f606fc16d144eca');

        self::assertInstanceOf(ContentInfo::class, $contentInfo);

        return $contentInfo;
    }

    /**
     * Test for the returned value of the loadContentInfoByRemoteId() method.
     *
     * @depends testLoadContentInfoByRemoteId
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfoByRemoteId
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    public function testLoadContentInfoByRemoteIdSetsExpectedContentInfo(ContentInfo $contentInfo)
    {
        $this->assertPropertiesCorrectUnsorted(
            [
                'id' => 10,
                'contentTypeId' => 4,
                'name' => 'Anonymous User',
                'sectionId' => 2,
                'currentVersionNo' => 2,
                'published' => true,
                'ownerId' => 14,
                'modificationDate' => $this->createDateTime(1072180405),
                'publishedDate' => $this->createDateTime(1033920665),
                'alwaysAvailable' => 1,
                'remoteId' => 'faaeb9be3bd98ed09f606fc16d144eca',
                'mainLanguageCode' => self::ENG_US,
                'mainLocationId' => 45,
            ],
            $contentInfo
        );
    }

    /**
     * Test for the loadContentInfoByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentInfoByRemoteId()
     *
     * @depends testLoadContentInfoByRemoteId
     */
    public function testLoadContentInfoByRemoteIdThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $this->contentService->loadContentInfoByRemoteId('abcdefghijklmnopqrstuvwxyz0123456789');
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfo()
     *
     * @depends testLoadContentInfo
     *
     * @group user
     */
    public function testLoadVersionInfo()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        // $mediaFolderId contains the ID of the "Media" folder

        // Load the ContentInfo for "Media" folder
        $contentInfo = $this->contentService->loadContentInfo($mediaFolderId);

        // Now load the current version info of the "Media" folder
        $versionInfo = $this->contentService->loadVersionInfo($contentInfo);

        self::assertInstanceOf(
            VersionInfo::class,
            $versionInfo
        );
    }

    /**
     * Test for the loadVersionInfoById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById()
     */
    public function testLoadVersionInfoById()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        // $mediaFolderId contains the ID of the "Media" folder

        // Load the VersionInfo for "Media" folder
        $versionInfo = $this->contentService->loadVersionInfoById($mediaFolderId);

        self::assertInstanceOf(
            VersionInfo::class,
            $versionInfo
        );

        return $versionInfo;
    }

    /**
     * Test for the returned value of the loadVersionInfoById() method.
     *
     * @depends testLoadVersionInfoById
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     */
    public function testLoadVersionInfoByIdSetsExpectedVersionInfo(VersionInfo $versionInfo)
    {
        $this->assertPropertiesCorrect(
            [
                'names' => [
                    self::ENG_US => 'Media',
                ],
                'contentInfo' => new ContentInfo($this->getExpectedMediaContentInfoProperties()),
                'id' => 472,
                'versionNo' => 1,
                'modificationDate' => $this->createDateTime(1060695457),
                'creatorId' => 14,
                'creationDate' => $this->createDateTime(1060695450),
                'status' => VersionInfo::STATUS_PUBLISHED,
                'initialLanguageCode' => self::ENG_US,
                'languageCodes' => [
                    self::ENG_US,
                ],
            ],
            $versionInfo
        );
        self::assertTrue($versionInfo->isPublished());
        self::assertFalse($versionInfo->isDraft());
        self::assertFalse($versionInfo->isArchived());
    }

    /**
     * @depends testLoadVersionInfoById
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     */
    public function testLoadVersionInfoByIdGetCreator(VersionInfo $versionInfo): void
    {
        $creator = $versionInfo->getCreator();

        self::assertInstanceOf(User::class, $creator);
        self::assertEquals('Administrator User', $creator->getName());
    }

    /**
     * @depends testLoadVersionInfoById
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     */
    public function testLoadVersionInfoByIdGetInitialLanguage(VersionInfo $versionInfo): void
    {
        $initialLanguage = $versionInfo->getInitialLanguage();

        self::assertInstanceOf(Language::class, $initialLanguage);
        self::assertEquals('eng-US', $initialLanguage->languageCode);
    }

    /**
     * @depends testLoadVersionInfoById
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo
     */
    public function testLoadVersionInfoByIdGetLanguages(VersionInfo $versionInfo): void
    {
        $actualLanguages = iterator_to_array($versionInfo->getLanguages());

        $expectedLanguages = ['eng-US'];
        foreach ($expectedLanguages as $i => $expectedLanguage) {
            self::assertEquals($expectedLanguage, $actualLanguages[$i]->languageCode);
        }
    }

    /**
     * Test for the loadVersionInfoById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById()
     *
     * @depends testLoadVersionInfoById
     */
    public function testLoadVersionInfoByIdThrowsNotFoundException()
    {
        $nonExistentContentId = $this->generateId('object', self::DB_INT_MAX);

        $this->expectException(NotFoundException::class);

        $this->contentService->loadVersionInfoById($nonExistentContentId);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByContentInfo()
     *
     * @depends testLoadContentInfo
     */
    public function testLoadContentByContentInfo()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        // $mediaFolderId contains the ID of the "Media" folder

        // Load the ContentInfo for "Media" folder
        $contentInfo = $this->contentService->loadContentInfo($mediaFolderId);

        // Now load the current content version for the info instance
        $content = $this->contentService->loadContentByContentInfo($contentInfo);

        self::assertInstanceOf(
            Content::class,
            $content
        );
    }

    /**
     * Test for the loadContentByVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByVersionInfo()
     *
     * @depends testLoadVersionInfo
     */
    public function testLoadContentByVersionInfo()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        // $mediaFolderId contains the ID of the "Media" folder

        // Load the ContentInfo for "Media" folder
        $contentInfo = $this->contentService->loadContentInfo($mediaFolderId);

        // Load the current VersionInfo
        $versionInfo = $this->contentService->loadVersionInfo($contentInfo);

        // Now load the current content version for the info instance
        $content = $this->contentService->loadContentByVersionInfo($versionInfo);

        self::assertInstanceOf(
            Content::class,
            $content
        );
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent()
     *
     * @group user
     * @group field-type
     */
    public function testLoadContent()
    {
        $mediaFolderId = $this->generateId('object', self::MEDIA_CONTENT_ID);
        // $mediaFolderId contains the ID of the "Media" folder

        // Load the Content for "Media" folder, any language and current version
        $content = $this->contentService->loadContent($mediaFolderId);

        self::assertInstanceOf(
            Content::class,
            $content
        );
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent()
     *
     * @depends testLoadContent
     */
    public function testLoadContentThrowsNotFoundException()
    {
        $nonExistentContentId = $this->generateId('object', self::DB_INT_MAX);

        $this->expectException(NotFoundException::class);

        $this->contentService->loadContent($nonExistentContentId);
    }

    /**
     * Data provider for testLoadContentByRemoteId().
     *
     * @return array
     */
    public function contentRemoteIdVersionLanguageProvider()
    {
        return [
            ['f5c88a2209584891056f987fd965b0ba', null, null],
            ['f5c88a2209584891056f987fd965b0ba', [self::ENG_US], null],
            ['f5c88a2209584891056f987fd965b0ba', null, 1],
            ['f5c88a2209584891056f987fd965b0ba', [self::ENG_US], 1],
            [self::MEDIA_REMOTE_ID, null, null],
            [self::MEDIA_REMOTE_ID, [self::ENG_US], null],
            [self::MEDIA_REMOTE_ID, null, 1],
            [self::MEDIA_REMOTE_ID, [self::ENG_US], 1],
        ];
    }

    /**
     * Test for the loadContentByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByRemoteId
     *
     * @dataProvider contentRemoteIdVersionLanguageProvider
     *
     * @param string $remoteId
     * @param array|null $languages
     * @param int $versionNo
     */
    public function testLoadContentByRemoteId($remoteId, $languages, $versionNo)
    {
        $content = $this->contentService->loadContentByRemoteId($remoteId, $languages, $versionNo);

        self::assertInstanceOf(
            Content::class,
            $content
        );

        self::assertEquals($remoteId, $content->contentInfo->remoteId);
        if ($languages !== null) {
            self::assertEquals($languages, $content->getVersionInfo()->languageCodes);
        }
        self::assertEquals($versionNo ?: 1, $content->getVersionInfo()->versionNo);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByRemoteId()
     *
     * @depends testLoadContentByRemoteId
     */
    public function testLoadContentByRemoteIdThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because no content object exists for the given remoteId
        $this->contentService->loadContentByRemoteId('a1b1c1d1e1f1a2b2c2d2e2f2a3b3c3d3');
    }

    /**
     * Test for the publishVersion() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testLoadContent
     * @depends testLoadContentInfo
     * @depends testLoadVersionInfo
     * @depends testCreateContentWithLocationCreateParameterDoesNotCreateLocationImmediately
     *
     * @group user
     * @group field-type
     */
    public function testPublishVersion()
    {
        ClockMock::withClockMock(false);
        $time = time();
        $content = $this->createContentVersion1();

        self::assertInstanceOf(Content::class, $content);
        self::assertTrue($content->contentInfo->published);
        self::assertEquals(VersionInfo::STATUS_PUBLISHED, $content->versionInfo->status);
        self::assertGreaterThanOrEqual($time, $content->contentInfo->publishedDate->getTimestamp());
        self::assertGreaterThanOrEqual($time, $content->contentInfo->modificationDate->getTimestamp());
        self::assertTrue($content->versionInfo->isPublished());
        self::assertFalse($content->versionInfo->isDraft());
        self::assertFalse($content->versionInfo->isArchived());

        return $content;
    }

    /**
     * Test for the publishVersion() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     */
    public function testPublishVersionSetsExpectedContentInfo($content)
    {
        $userReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                $content->id,
                true,
                1,
                'abcdef0123456789abcdef0123456789',
                self::ENG_US,
                $userReference->getUserId(),
                true,
            ],
            [
                $content->contentInfo->id,
                $content->contentInfo->alwaysAvailable,
                $content->contentInfo->currentVersionNo,
                $content->contentInfo->remoteId,
                $content->contentInfo->mainLanguageCode,
                $content->contentInfo->ownerId,
                $content->contentInfo->published,
            ]
        );

        self::assertNotNull($content->contentInfo->mainLocationId);
        $date = new \DateTime('1984/01/01');
        self::assertGreaterThan(
            $date->getTimestamp(),
            $content->contentInfo->publishedDate->getTimestamp()
        );
    }

    /**
     * Test for the publishVersion() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     */
    public function testPublishVersionSetsExpectedVersionInfo($content)
    {
        $currentUserReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                $currentUserReference->getUserId(),
                self::ENG_US,
                VersionInfo::STATUS_PUBLISHED,
                1,
            ],
            [
                $content->getVersionInfo()->creatorId,
                $content->getVersionInfo()->initialLanguageCode,
                $content->getVersionInfo()->status,
                $content->getVersionInfo()->versionNo,
            ]
        );

        $date = new \DateTime('1984/01/01');
        self::assertGreaterThan(
            $date->getTimestamp(),
            $content->getVersionInfo()->modificationDate->getTimestamp()
        );

        self::assertNotNull($content->getVersionInfo()->modificationDate);
        self::assertTrue($content->getVersionInfo()->isPublished());
        self::assertFalse($content->getVersionInfo()->isDraft());
        self::assertFalse($content->getVersionInfo()->isArchived());
    }

    /**
     * Test for the publishVersion() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     */
    public function testPublishVersionSetsExpectedContentType($content)
    {
        $contentType = $content->getContentType();

        self::assertEquals(
            [
                $contentType->id,
                // won't be a match as it's set to true in createContentDraftVersion1()
                //$contentType->defaultAlwaysAvailable,
                //$contentType->defaultSortField,
                //$contentType->defaultSortOrder,
            ],
            [
                $content->contentInfo->contentTypeId,
                //$content->contentInfo->alwaysAvailable,
                //$location->sortField,
                //$location->sortOrder,
            ]
        );
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion
     */
    public function testPublishVersionCreatesLocationsDefinedOnCreate(): array
    {
        $content = $this->createContentVersion1();

        $location = $this->locationService->loadLocationByRemoteId(
            '0123456789abcdef0123456789abcdef'
        );

        self::assertEquals(
            $location->getContentInfo(),
            $content->getVersionInfo()->getContentInfo()
        );

        return [$content, $location];
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersionCreatesLocationsDefinedOnCreate
     */
    public function testCreateContentWithLocationCreateParameterCreatesExpectedLocation(array $testData)
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Location $location */
        list($content, $location) = $testData;

        $parentLocationId = $this->generateId('location', 56);
        $parentLocation = $this->getRepository()->getLocationService()->loadLocation($parentLocationId);
        $mainLocationId = $content->getVersionInfo()->getContentInfo()->mainLocationId;

        $this->assertPropertiesCorrect(
            [
                'id' => $mainLocationId,
                'priority' => 23,
                'hidden' => true,
                'invisible' => true,
                'remoteId' => '0123456789abcdef0123456789abcdef',
                'parentLocationId' => $parentLocationId,
                'pathString' => $parentLocation->pathString . $mainLocationId . '/',
                'depth' => $parentLocation->depth + 1,
                'sortField' => Location::SORT_FIELD_NODE_ID,
                'sortOrder' => Location::SORT_ORDER_DESC,
            ],
            $location
        );
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     */
    public function testPublishVersionThrowsBadStateException()
    {
        $draft = $this->createContentDraftVersion1();

        // Publish the content draft
        $this->contentService->publishVersion($draft->getVersionInfo());

        $this->expectException(BadStateException::class);

        // This call will fail with a "BadStateException", because the version is already published.
        $this->contentService->publishVersion($draft->getVersionInfo());
    }

    /**
     * Test that publishVersion() does not affect publishedDate (assuming previous version exists).
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion
     */
    public function testPublishVersionDoesNotChangePublishedDate()
    {
        $publishedContent = $this->createContentVersion1();

        // force timestamps to differ
        sleep(1);

        $contentDraft = $this->contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'New name');
        $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
        $republishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

        self::assertEquals(
            $publishedContent->contentInfo->publishedDate->getTimestamp(),
            $republishedContent->contentInfo->publishedDate->getTimestamp()
        );
        self::assertGreaterThan(
            $publishedContent->contentInfo->modificationDate->getTimestamp(),
            $republishedContent->contentInfo->modificationDate->getTimestamp()
        );
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testPublishVersion
     *
     * @group user
     */
    public function testCreateContentDraft()
    {
        $content = $this->createContentVersion1();

        // Now we create a new draft from the published content
        $draftedContent = $this->contentService->createContentDraft($content->contentInfo);

        self::assertInstanceOf(
            Content::class,
            $draftedContent
        );

        return $draftedContent;
    }

    /**
     * Test for the createContentDraft() method with given language for new draft.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft
     */
    public function testCreateContentDraftInOtherLanguage()
    {
        $content = $this->createContentVersion1();

        $language = $this->getRepository()->getContentLanguageService()->loadLanguage('eng-GB');

        // Now we create a new draft from the published content
        $draftedContent = $this->contentService->createContentDraft(
            $content->contentInfo,
            null,
            null,
            $language
        );

        self::assertEquals('eng-US', $content->versionInfo->initialLanguageCode);
        self::assertEquals('eng-GB', $draftedContent->versionInfo->initialLanguageCode);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * Test that editor has access to edit own draft.
     * Note: Editors have access to version_read, which is needed to load content drafts.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testPublishVersion
     *
     * @group user
     */
    public function testCreateContentDraftAndLoadAccess()
    {
        $user = $this->createUserVersion1();

        // Set new editor as user
        $this->permissionResolver->setCurrentUserReference($user);

        // Create draft
        $draft = $this->createContentDraftVersion1(2, 'folder');

        // Try to load the draft
        $loadedDraft = $this->contentService->loadContent($draft->id);

        self::assertEquals($draft->id, $loadedDraft->id);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $draft
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftSetsExpectedProperties($draft)
    {
        self::assertEquals(
            [
                'fieldCount' => 1,
                'relationCount' => 0,
            ],
            [
                'fieldCount' => count($draft->getFields()),
                'relationCount' => count($this->getRepository()->getContentService()->loadRelationList($draft->getVersionInfo())->items),
            ]
        );
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $draft
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftSetsContentInfo($draft)
    {
        $contentInfo = $draft->contentInfo;
        $currentUserReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                $draft->id,
                true,
                1,
                self::ENG_US,
                $currentUserReference->getUserId(),
                'abcdef0123456789abcdef0123456789',
                1,
            ],
            [
                $contentInfo->id,
                $contentInfo->alwaysAvailable,
                $contentInfo->currentVersionNo,
                $contentInfo->mainLanguageCode,
                $contentInfo->ownerId,
                $contentInfo->remoteId,
                $contentInfo->getSectionId(),
            ]
        );
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $draft
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftSetsVersionInfo($draft)
    {
        $versionInfo = $draft->getVersionInfo();
        $currentUserReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                'creatorId' => $currentUserReference->getUserId(),
                'initialLanguageCode' => self::ENG_US,
                'languageCodes' => [0 => self::ENG_US],
                'status' => VersionInfo::STATUS_DRAFT,
                'versionNo' => 2,
            ],
            [
                'creatorId' => $versionInfo->creatorId,
                'initialLanguageCode' => $versionInfo->initialLanguageCode,
                'languageCodes' => $versionInfo->languageCodes,
                'status' => $versionInfo->status,
                'versionNo' => $versionInfo->versionNo,
            ]
        );
        self::assertTrue($versionInfo->isDraft());
        self::assertFalse($versionInfo->isPublished());
        self::assertFalse($versionInfo->isArchived());
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $draft
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     * @depends testLoadVersionInfo
     */
    public function testCreateContentDraftLoadVersionInfoStillLoadsPublishedVersion($draft)
    {
        $content = $this->createContentVersion1();

        // Now we create a new draft from the published content
        $this->contentService->createContentDraft($content->contentInfo);

        // This call will still load the published version
        $versionInfoPublished = $this->contentService->loadVersionInfo($content->contentInfo);

        self::assertEquals(1, $versionInfoPublished->versionNo);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testLoadContent
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftLoadContentStillLoadsPublishedVersion()
    {
        $content = $this->createContentVersion1();

        // Now we create a new draft from the published content
        $this->contentService->createContentDraft($content->contentInfo);

        // This call will still load the published content version
        $contentPublished = $this->contentService->loadContent($content->id);

        self::assertEquals(1, $contentPublished->getVersionInfo()->versionNo);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testLoadContentByRemoteId
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftLoadContentByRemoteIdStillLoadsPublishedVersion()
    {
        $content = $this->createContentVersion1();

        // Now we create a new draft from the published content
        $this->contentService->createContentDraft($content->contentInfo);

        // This call will still load the published content version
        $contentPublished = $this->contentService->loadContentByRemoteId('abcdef0123456789abcdef0123456789');

        self::assertEquals(1, $contentPublished->getVersionInfo()->versionNo);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testLoadContentByContentInfo
     * @depends testCreateContentDraft
     */
    public function testCreateContentDraftLoadContentByContentInfoStillLoadsPublishedVersion()
    {
        $content = $this->createContentVersion1();

        // Now we create a new draft from the published content
        $this->contentService->createContentDraft($content->contentInfo);

        // This call will still load the published content version
        $contentPublished = $this->contentService->loadContentByContentInfo($content->contentInfo);

        self::assertEquals(1, $contentPublished->getVersionInfo()->versionNo);
    }

    /**
     * Test for the newContentUpdateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::newContentUpdateStruct
     *
     * @group user
     */
    public function testNewContentUpdateStruct()
    {
        $updateStruct = $this->contentService->newContentUpdateStruct();

        self::assertInstanceOf(
            ContentUpdateStruct::class,
            $updateStruct
        );

        $this->assertPropertiesCorrect(
            [
                'initialLanguageCode' => null,
                'fields' => [],
            ],
            $updateStruct
        );
    }

    /**
     * Test for the updateContent() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testNewContentUpdateStruct
     * @depends testCreateContentDraft
     *
     * @group user
     * @group field-type
     */
    public function testUpdateContent()
    {
        $draftVersion2 = $this->createUpdatedDraftVersion2();

        self::assertInstanceOf(
            Content::class,
            $draftVersion2
        );

        self::assertEquals(
            $this->generateId('user', 10),
            $draftVersion2->versionInfo->creatorId,
            'creatorId is not properly set on new Version'
        );

        return $draftVersion2;
    }

    /**
     * Test for the updateContent_WithDifferentUser() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testNewContentUpdateStruct
     * @depends testCreateContentDraft
     *
     * @group user
     * @group field-type
     */
    public function testUpdateContentWithDifferentUser()
    {
        $arrayWithDraftVersion2 = $this->createUpdatedDraftVersion2NotAdmin();

        self::assertInstanceOf(
            Content::class,
            $arrayWithDraftVersion2[0]
        );

        self::assertEquals(
            $this->generateId('user', $arrayWithDraftVersion2[1]),
            $arrayWithDraftVersion2[0]->versionInfo->creatorId,
            'creatorId is not properly set on new Version'
        );

        return $arrayWithDraftVersion2[0];
    }

    /**
     * Test for the updateContent() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentSetsExpectedFields($content)
    {
        $actual = $this->normalizeFields($content->getFields());

        $expected = [
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_GB,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_US,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentThrowsBadStateException()
    {
        $content = $this->createContentVersion1();

        // Now create an update struct and modify some fields
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'An awesome² story about ezp.');
        $contentUpdateStruct->setField('name', 'An awesome²³ story about ezp.', self::ENG_GB);

        $contentUpdateStruct->initialLanguageCode = self::ENG_US;

        $this->expectException(BadStateException::class);

        // This call will fail with a "BadStateException", because $publishedContent is not a draft.
        $this->contentService->updateContent(
            $content->getVersionInfo(),
            $contentUpdateStruct
        );
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentThrowsInvalidArgumentExceptionWhenFieldTypeDoesNotAccept()
    {
        $draft = $this->createContentDraftVersion1();

        // Now create an update struct and modify some fields
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        // The name field does not accept a stdClass object as its input
        $contentUpdateStruct->setField('name', new \stdClass(), self::ENG_US);

        $this->expectException(APIInvalidArgumentException::class);
        // is not accepted
        $this->contentService->updateContent(
            $draft->getVersionInfo(),
            $contentUpdateStruct
        );
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentWhenMandatoryFieldIsEmpty()
    {
        $draft = $this->createContentDraftVersion1();

        // Now create an update struct and set a mandatory field to null
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', null);

        // Don't set this, then the above call without languageCode will fail
        $contentUpdateStruct->initialLanguageCode = self::ENG_US;

        $this->expectException(ContentFieldValidationException::class);

        // This call will fail with a "ContentFieldValidationException", because the mandatory "name" field is empty.
        $this->contentService->updateContent(
            $draft->getVersionInfo(),
            $contentUpdateStruct
        );
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentThrowsContentFieldValidationException()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'An awesome Sidelfingen folder');

        $draft = $this->contentService->createContent($contentCreate);

        $contentUpdate = $this->contentService->newContentUpdateStruct();
        // Violates string length constraint
        $contentUpdate->setField('short_name', str_repeat('a', 200), self::ENG_US);

        $this->expectException(ContentFieldValidationException::class);

        // Throws ContentFieldValidationException because the string length validation of the field "short_name" fails
        $this->contentService->updateContent($draft->getVersionInfo(), $contentUpdate);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentValidatorIgnoresRequiredFieldsOfNotUpdatedLanguages()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        // Create multilangual content
        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'An awesome Sidelfingen folder', self::ENG_US);
        $contentCreate->setField('name', 'An awesome Sidelfingen folder', self::ENG_GB);

        $contentDraft = $this->contentService->createContent($contentCreate);

        // 2. Update content type definition
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);

        $fieldDefinition = $contentType->getFieldDefinition('short_name');
        $fieldDefinitionUpdate = $contentTypeService->newFieldDefinitionUpdateStruct();
        $fieldDefinitionUpdate->identifier = 'short_name';
        $fieldDefinitionUpdate->isRequired = true;

        $contentTypeService->updateFieldDefinition(
            $contentTypeDraft,
            $fieldDefinition,
            $fieldDefinitionUpdate
        );
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        // 3. Update only eng-US translation
        $contentUpdate = $this->contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', 'An awesome Sidelfingen folder (updated)', self::ENG_US);
        $contentUpdate->setField('short_name', 'Lorem ipsum dolor');

        $this->contentService->updateContent($contentDraft->getVersionInfo(), $contentUpdate);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     */
    public function testUpdateContentWithNotUpdatingMandatoryField()
    {
        $draft = $this->createContentDraftVersion1();

        // Now create an update struct which does not overwrite mandatory
        // fields
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();

        // Don't set this, then the above call without languageCode will fail
        $contentUpdateStruct->initialLanguageCode = self::ENG_US;

        // This will only update the "description" field in the "eng-US" language
        $updatedDraft = $this->contentService->updateContent(
            $draft->getVersionInfo(),
            $contentUpdateStruct
        );

        foreach ($updatedDraft->getFields() as $field) {
            if ($field->languageCode === self::ENG_US && $field->fieldDefIdentifier === 'name' && $field->value !== null) {
                // Found field
                return;
            }
        }
        self::fail(
            'Field with identifier "name" in language "eng-US" could not be found or has empty value.'
        );
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft($contentInfo, $versionInfo)
     *
     * @depends testUpdateContent
     */
    public function testCreateContentDraftWithSecondParameter()
    {
        $contentVersion2 = $this->createContentVersion2();

        // Now we create a new draft from the initial version
        $draftedContentReloaded = $this->contentService->createContentDraft(
            $contentVersion2->contentInfo,
            $contentVersion2->getVersionInfo()
        );

        self::assertEquals(3, $draftedContentReloaded->getVersionInfo()->versionNo);
    }

    /**
     * Test for the createContentDraft() method with third parameter.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft
     */
    public function testCreateContentDraftWithThirdParameter()
    {
        $content = $this->contentService->loadContent(4);
        $user = $this->createUserVersion1();

        $draftContent = $this->contentService->createContentDraft(
            $content->contentInfo,
            $content->getVersionInfo(),
            $user
        );

        self::assertInstanceOf(
            Content::class,
            $draftContent
        );
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     * @depends testUpdateContent
     */
    public function testPublishVersionFromContentDraft()
    {
        $contentVersion2 = $this->createContentVersion2();

        $versionInfo = $this->contentService->loadVersionInfo($contentVersion2->contentInfo);

        self::assertEquals(
            [
                'status' => VersionInfo::STATUS_PUBLISHED,
                'versionNo' => 2,
            ],
            [
                'status' => $versionInfo->status,
                'versionNo' => $versionInfo->versionNo,
            ]
        );
        self::assertTrue($versionInfo->isPublished());
        self::assertFalse($versionInfo->isDraft());
        self::assertFalse($versionInfo->isArchived());
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testPublishVersionFromContentDraftArchivesOldVersion()
    {
        $contentVersion2 = $this->createContentVersion2();

        $versionInfo = $this->contentService->loadVersionInfo($contentVersion2->contentInfo, 1);

        self::assertEquals(
            [
                'status' => VersionInfo::STATUS_ARCHIVED,
                'versionNo' => 1,
            ],
            [
                'status' => $versionInfo->status,
                'versionNo' => $versionInfo->versionNo,
            ]
        );
        self::assertTrue($versionInfo->isArchived());
        self::assertFalse($versionInfo->isDraft());
        self::assertFalse($versionInfo->isPublished());
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testPublishVersionFromContentDraftUpdatesContentInfoCurrentVersion()
    {
        $contentVersion2 = $this->createContentVersion2();

        self::assertEquals(2, $contentVersion2->contentInfo->currentVersionNo);
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testPublishVersionFromOldContentDraftArchivesNewerVersionNo()
    {
        $content = $this->createContentVersion1();

        // Create a new draft with versionNo = 2
        $draftedContentVersion2 = $this->contentService->createContentDraft($content->contentInfo);

        // Create another new draft with versionNo = 3
        $draftedContentVersion3 = $this->contentService->createContentDraft($content->contentInfo);

        // Publish draft with versionNo = 3
        $this->contentService->publishVersion($draftedContentVersion3->getVersionInfo());

        // Publish the first draft with versionNo = 2
        // currentVersionNo is now 2, versionNo 3 will be archived
        $publishedDraft = $this->contentService->publishVersion($draftedContentVersion2->getVersionInfo());

        self::assertEquals(2, $publishedDraft->contentInfo->currentVersionNo);
    }

    /**
     * Test for the publishVersion() method, and that it creates limited archives.
     *
     * @todo Adapt this when per content type archive limited is added on repository content type model.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testPublishVersionNotCreatingUnlimitedArchives(): void
    {
        $content = $this->createContentVersion1();

        // load first to make sure list gets updated also (cache)
        $versionInfoList = iterator_to_array($this->contentService->loadVersions($content->contentInfo));
        self::assertCount(1, $versionInfoList);
        self::assertEquals(1, $versionInfoList[0]->versionNo);

        // Create a new draft with versionNo = 2
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        // Create a new draft with versionNo = 3
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        // Create a new draft with versionNo = 4
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        // Create a new draft with versionNo = 5
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        // Create a new draft with versionNo = 6
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        // Create a new draft with versionNo = 7
        $draftedContentVersion = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draftedContentVersion->getVersionInfo());

        $versionInfoList = iterator_to_array($this->contentService->loadVersions($content->contentInfo));

        self::assertCount(6, $versionInfoList);
        self::assertEquals(2, $versionInfoList[0]->versionNo);
        self::assertEquals(7, $versionInfoList[5]->versionNo);

        self::assertEquals(
            [
                VersionInfo::STATUS_ARCHIVED,
                VersionInfo::STATUS_ARCHIVED,
                VersionInfo::STATUS_ARCHIVED,
                VersionInfo::STATUS_ARCHIVED,
                VersionInfo::STATUS_ARCHIVED,
                VersionInfo::STATUS_PUBLISHED,
            ],
            [
                $versionInfoList[0]->status,
                $versionInfoList[1]->status,
                $versionInfoList[2]->status,
                $versionInfoList[3]->status,
                $versionInfoList[4]->status,
                $versionInfoList[5]->status,
            ]
        );
    }

    /**
     * Test for the newContentMetadataUpdateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::newContentMetadataUpdateStruct
     *
     * @group user
     */
    public function testNewContentMetadataUpdateStruct()
    {
        // Creates a new metadata update struct
        $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();

        foreach ($metadataUpdate as $propertyName => $propertyValue) {
            self::assertNull($propertyValue, "Property '{$propertyName}' initial value should be null'");
        }

        $metadataUpdate->remoteId = 'aaaabbbbccccddddeeeeffff11112222';
        $metadataUpdate->mainLanguageCode = self::ENG_GB;
        $metadataUpdate->alwaysAvailable = false;

        self::assertInstanceOf(
            ContentMetadataUpdateStruct::class,
            $metadataUpdate
        );
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testPublishVersion
     * @depends testNewContentMetadataUpdateStruct
     *
     * @group user
     */
    public function testUpdateContentMetadata()
    {
        $content = $this->createContentVersion1();

        // Creates a metadata update struct
        $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();

        $metadataUpdate->remoteId = 'aaaabbbbccccddddeeeeffff11112222';
        $metadataUpdate->mainLanguageCode = self::ENG_GB;
        $metadataUpdate->alwaysAvailable = false;
        $metadataUpdate->publishedDate = $this->createDateTime(441759600); // 1984/01/01
        $metadataUpdate->modificationDate = $this->createDateTime(441759600); // 1984/01/01

        // Update the metadata of the published content object
        $content = $this->contentService->updateContentMetadata(
            $content->contentInfo,
            $metadataUpdate
        );

        self::assertInstanceOf(
            Content::class,
            $content
        );

        return $content;
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     */
    public function testUpdateContentMetadataSetsExpectedProperties($content)
    {
        $contentInfo = $content->contentInfo;
        $currentUserReference = $this->getRepository()->getPermissionResolver()->getCurrentUserReference();

        self::assertEquals(
            [
                'remoteId' => 'aaaabbbbccccddddeeeeffff11112222',
                'sectionId' => $this->generateId('section', 1),
                'alwaysAvailable' => false,
                'currentVersionNo' => 1,
                'mainLanguageCode' => self::ENG_GB,
                'modificationDate' => $this->createDateTime(441759600),
                'ownerId' => $currentUserReference->getUserId(),
                'published' => true,
                'publishedDate' => $this->createDateTime(441759600),
            ],
            [
                'remoteId' => $contentInfo->remoteId,
                'sectionId' => $contentInfo->getSectionId(),
                'alwaysAvailable' => $contentInfo->alwaysAvailable,
                'currentVersionNo' => $contentInfo->currentVersionNo,
                'mainLanguageCode' => $contentInfo->mainLanguageCode,
                'modificationDate' => $contentInfo->modificationDate,
                'ownerId' => $contentInfo->ownerId,
                'published' => $contentInfo->published,
                'publishedDate' => $contentInfo->publishedDate,
            ]
        );
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     */
    public function testUpdateContentMetadataNotUpdatesContentVersion($content)
    {
        self::assertEquals(1, $content->getVersionInfo()->versionNo);
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     */
    public function testUpdateContentMetadataThrowsInvalidArgumentExceptionOnDuplicateRemoteId()
    {
        $content = $this->createContentVersion1();

        // Creates a metadata update struct
        $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();
        $metadataUpdate->remoteId = self::MEDIA_REMOTE_ID;

        $this->expectException(APIInvalidArgumentException::class);
        // specified remoteId is already used by the "Media" page.
        $this->contentService->updateContentMetadata(
            $content->contentInfo,
            $metadataUpdate
        );
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata
     */
    public function testUpdateContentMetadataThrowsInvalidArgumentExceptionOnNoMetadataPropertiesSet()
    {
        $contentInfo = $this->contentService->loadContentInfo(4);
        $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();

        $this->expectException(APIInvalidArgumentException::class);
        $this->contentService->updateContentMetadata($contentInfo, $contentMetadataUpdateStruct);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testUpdateContentAlwaysAvailable(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();

        $folder = $this->createFolder(['eng-GB' => 'Folder'], 2);

        $contentMetadataUpdate = $contentService->newContentMetadataUpdateStruct();
        $contentMetadataUpdate->alwaysAvailable = !$folder->contentInfo->alwaysAvailable;
        $contentService->updateContentMetadata($folder->contentInfo, $contentMetadataUpdate);

        $reloadedFolder = $contentService->loadContent($folder->id);
        self::assertEquals(
            $contentMetadataUpdate->alwaysAvailable,
            $reloadedFolder->contentInfo->alwaysAvailable
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testUpdateContentMainTranslation(): void
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();

        // create a content type which is not always available by default
        $contentType = $this->createSimpleContentType(
            'test_t',
            self::ENG_GB,
            [
                'name' => 'ibexa_string',
            ],
            false
        );

        $contentCreate = $contentService->newContentCreateStruct(
            $contentType,
            self::ENG_US
        );
        $contentCreate->setField('name', 'My content');
        $content = $contentService->publishVersion(
            $contentService->createContent(
                $contentCreate,
                [$locationService->newLocationCreateStruct(2)]
            )->getVersionInfo()
        );
        // perform sanity check
        self::assertFalse($content->contentInfo->alwaysAvailable);

        $updateStruct = $contentService->newContentMetadataUpdateStruct();
        $updateStruct->mainLanguageCode = self::ENG_GB;

        $contentService->updateContentMetadata($content->contentInfo, $updateStruct);

        $reloadedContent = $contentService->loadContent($content->id);
        self::assertEquals(self::ENG_GB, $reloadedContent->contentInfo->mainLanguageCode);

        // check that other properties remained unchanged
        self::assertStructPropertiesCorrect(
            $content->contentInfo,
            $reloadedContent->contentInfo,
            [
                'id',
                'contentTypeId',
                'name',
                'sectionId',
                'currentVersionNo',
                'published',
                'ownerId',
                'alwaysAvailable',
                'remoteId',
                'mainLocationId',
                'status',
            ]
        );
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testDeleteContent()
    {
        $contentVersion2 = $this->createContentVersion2();

        // Load the locations for this content object
        $locations = $this->locationService->loadLocations($contentVersion2->contentInfo);

        // This will delete the content, all versions and the associated locations
        $this->contentService->deleteContent($contentVersion2->contentInfo);

        $this->expectException(NotFoundException::class);

        foreach ($locations as $location) {
            $this->locationService->loadLocation($location->id);
        }
    }

    /**
     * Test for the deleteContent() method.
     *
     * Test for issue EZP-21057:
     * "contentService: Unable to delete a content with an empty file attribute"
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent()
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testDeleteContentWithEmptyBinaryField()
    {
        $contentVersion = $this->createContentVersion1EmptyBinaryField();

        // Load the locations for this content object
        $locations = $this->locationService->loadLocations($contentVersion->contentInfo);

        // This will delete the content, all versions and the associated locations
        $this->contentService->deleteContent($contentVersion->contentInfo);

        $this->expectException(NotFoundException::class);

        foreach ($locations as $location) {
            $this->locationService->loadLocation($location->id);
        }
    }

    public function testCountContentDraftsReturnsZeroByDefault(): void
    {
        self::assertSame(0, $this->contentService->countContentDrafts());
    }

    public function testCountContentDrafts(): void
    {
        // Create 5 drafts
        $this->createContentDrafts(5);

        self::assertSame(5, $this->contentService->countContentDrafts());
    }

    public function testCountContentDraftsForUsers(): void
    {
        $newUser = $this->createUserWithPolicies(
            'new_user',
            [
                ['module' => 'content', 'function' => 'create'],
                ['module' => 'content', 'function' => 'read'],
                ['module' => 'content', 'function' => 'publish'],
                ['module' => 'content', 'function' => 'edit'],
            ]
        );

        $previousUser = $this->permissionResolver->getCurrentUserReference();

        // Set new editor as user
        $this->permissionResolver->setCurrentUserReference($newUser);

        // Create a content draft as newUser
        $publishedContent = $this->createContentVersion1();
        $this->contentService->createContentDraft($publishedContent->contentInfo);

        // Reset to previous current user
        $this->permissionResolver->setCurrentUserReference($previousUser);

        // Now $contentDrafts for the previous current user and the new user
        $newUserDrafts = $this->contentService->countContentDrafts($newUser);
        $previousUserDrafts = $this->contentService->countContentDrafts();

        self::assertSame(1, $newUserDrafts);
        self::assertSame(0, $previousUserDrafts);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList()
     */
    public function testLoadContentDraftsReturnsEmptyArrayByDefault()
    {
        $contentDrafts = $this->contentService->loadContentDraftList();

        self::assertSame([], $contentDrafts->items);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList()
     *
     * @depends testCreateContentDraft
     */
    public function testLoadContentDraftList(): void
    {
        // "Media" content object
        $mediaContentInfo = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // "Ibexa Demo Design ..." content object
        $demoDesignContentInfo = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        // Create some drafts
        $this->contentService->createContentDraft($mediaContentInfo);
        $this->contentService->createContentDraft($demoDesignContentInfo);

        // Now $contentDrafts should contain two drafted versions
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\DraftList\ContentDraftListItemInterface[] $draftedVersions */
        $draftedVersions = iterator_to_array($this->contentService->loadContentDraftList()->getIterator());

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $draftedVersionInfo0 */
        $draftedVersionInfo0 = $draftedVersions[0]->getVersionInfo();

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $draftedVersionInfo1 */
        $draftedVersionInfo1 = $draftedVersions[1]->getVersionInfo();

        $actual = [
            $draftedVersionInfo0->status,
            $draftedVersionInfo0->getContentInfo()->remoteId,
            $draftedVersionInfo1->status,
            $draftedVersionInfo1->getContentInfo()->remoteId,
        ];
        sort($actual, SORT_STRING);

        self::assertEquals(
            [
                VersionInfo::STATUS_DRAFT,
                VersionInfo::STATUS_DRAFT,
                self::DEMO_DESIGN_REMOTE_ID,
                self::MEDIA_REMOTE_ID,
            ],
            $actual
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList($user)
     */
    public function testLoadContentDraftsWithFirstParameter()
    {
        $user = $this->createUserVersion1();

        // Get current user
        $oldCurrentUser = $this->permissionResolver->getCurrentUserReference();

        // Set new editor as user
        $this->permissionResolver->setCurrentUserReference($user);

        // "Media" content object
        $mediaContentInfo = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // Create a content draft
        $this->contentService->createContentDraft($mediaContentInfo);

        // Reset to previous current user
        $this->permissionResolver->setCurrentUserReference($oldCurrentUser);

        // Now $contentDrafts for the previous current user and the new user
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\DraftList\ContentDraftListItemInterface[] $newCurrentUserDrafts */
        $newCurrentUserDrafts = iterator_to_array($this->contentService->loadContentDraftList($user)->getIterator());
        $oldCurrentUserDrafts = iterator_to_array($this->contentService->loadContentDraftList()->getIterator());

        self::assertSame([], $oldCurrentUserDrafts);

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $newCurrentUserDraftVersionInfo */
        $newCurrentUserDraftVersionInfo = $newCurrentUserDrafts[0]->getVersionInfo();
        self::assertEquals(
            [
                VersionInfo::STATUS_DRAFT,
                self::MEDIA_REMOTE_ID,
            ],
            [
                $newCurrentUserDraftVersionInfo->status,
                $newCurrentUserDraftVersionInfo->getContentInfo()->remoteId,
            ]
        );
        self::assertTrue($newCurrentUserDraftVersionInfo->isDraft());
        self::assertFalse($newCurrentUserDraftVersionInfo->isArchived());
        self::assertFalse($newCurrentUserDraftVersionInfo->isPublished());
    }

    /**
     * Test for the loadContentDraftList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList()
     */
    public function testLoadContentDraftListWithPaginationParameters(): void
    {
        // Create some drafts
        $publishedContent = $this->createContentVersion1();
        $draftContentA = $this->contentService->createContentDraft($publishedContent->contentInfo);
        $draftContentB = $this->contentService->createContentDraft($draftContentA->contentInfo);
        $draftContentC = $this->contentService->createContentDraft($draftContentB->contentInfo);
        $draftContentD = $this->contentService->createContentDraft($draftContentC->contentInfo);
        $draftContentE = $this->contentService->createContentDraft($draftContentD->contentInfo);

        $draftsOnPage1 = $this->contentService->loadContentDraftList(null, 0, 2);
        $draftsOnPage2 = $this->contentService->loadContentDraftList(null, 2, 2);

        self::assertSame(5, $draftsOnPage1->totalCount);
        self::assertSame(5, $draftsOnPage2->totalCount);
        self::assertEquals($draftContentE->getVersionInfo(), $draftsOnPage1->items[0]->getVersionInfo());
        self::assertEquals($draftContentD->getVersionInfo(), $draftsOnPage1->items[1]->getVersionInfo());
        self::assertEquals($draftContentC->getVersionInfo(), $draftsOnPage2->items[0]->getVersionInfo());
        self::assertEquals($draftContentB->getVersionInfo(), $draftsOnPage2->items[1]->getVersionInfo());
    }

    /**
     * Test for the loadContentDraftList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList($user)
     */
    public function testLoadContentDraftListWithForUserWithLimitation(): void
    {
        $oldUser = $this->permissionResolver->getCurrentUserReference();

        $parentContent = $this->createFolder(['eng-US' => 'parentFolder'], 2);
        $content = $this->createFolder(['eng-US' => 'parentFolder'], $parentContent->contentInfo->mainLocationId);

        // User has limitation to read versions only for `$content`, not for `$parentContent`
        $newUser = $this->createUserWithVersionReadLimitations([$content->contentInfo->mainLocationId]);

        $this->permissionResolver->setCurrentUserReference($newUser);

        $contentDraftUnauthorized = $this->contentService->createContentDraft($parentContent->contentInfo);
        $contentDraftA = $this->contentService->createContentDraft($content->contentInfo);
        $contentDraftB = $this->contentService->createContentDraft($content->contentInfo);

        $newUserDraftList = $this->contentService->loadContentDraftList($newUser, 0);
        self::assertSame(3, $newUserDraftList->totalCount);
        self::assertEquals($contentDraftB->getVersionInfo(), $newUserDraftList->items[0]->getVersionInfo());
        self::assertEquals($contentDraftA->getVersionInfo(), $newUserDraftList->items[1]->getVersionInfo());
        self::assertEquals(
            new UnauthorizedContentDraftListItem('content', 'versionread', ['contentId' => $contentDraftUnauthorized->id]),
            $newUserDraftList->items[2]
        );

        // Reset to previous user
        $this->permissionResolver->setCurrentUserReference($oldUser);

        $oldUserDraftList = $this->contentService->loadContentDraftList();

        self::assertSame(0, $oldUserDraftList->totalCount);
        self::assertSame([], $oldUserDraftList->items);
    }

    /**
     * Test for the loadContentDraftList() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentDraftList()
     */
    public function testLoadAllContentDraftList(): void
    {
        // Create more drafts then default pagination limit
        $this->createContentDrafts(12);

        self::assertCount(12, $this->contentService->loadContentDraftList());
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfo($contentInfo, $versionNo)
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testLoadVersionInfoWithSecondParameter()
    {
        $publishedContent = $this->createContentVersion1();

        $this->contentService->createContentDraft($publishedContent->contentInfo);

        // Will return the VersionInfo of the $draftContent
        $versionInfo = $this->contentService->loadVersionInfoById($publishedContent->id, 2);

        self::assertEquals(2, $versionInfo->versionNo);

        // Check that ContentInfo contained in VersionInfo has correct main Location id set
        self::assertEquals(
            $publishedContent->getVersionInfo()->getContentInfo()->mainLocationId,
            $versionInfo->getContentInfo()->mainLocationId
        );
    }

    /**
     * Test for the loadVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfo($contentInfo, $versionNo)
     *
     * @depends testLoadVersionInfoWithSecondParameter
     */
    public function testLoadVersionInfoThrowsNotFoundExceptionWithSecondParameter()
    {
        $draft = $this->createContentDraftVersion1();

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because not versionNo 2 exists for this content object.
        $this->contentService->loadVersionInfo($draft->contentInfo, 2);
    }

    /**
     * Test for the loadVersionInfoById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById($contentId, $versionNo)
     *
     * @depends testLoadVersionInfoWithSecondParameter
     */
    public function testLoadVersionInfoByIdWithSecondParameter()
    {
        $publishedContent = $this->createContentVersion1();

        $draftContent = $this->contentService->createContentDraft($publishedContent->contentInfo);

        // Will return the VersionInfo of the $draftContent
        $versionInfo = $this->contentService->loadVersionInfoById($publishedContent->id, 2);

        self::assertEquals(2, $versionInfo->versionNo);

        // Check that ContentInfo contained in VersionInfo has correct main Location id set
        self::assertEquals(
            $publishedContent->getVersionInfo()->getContentInfo()->mainLocationId,
            $versionInfo->getContentInfo()->mainLocationId
        );

        return [
            'versionInfo' => $versionInfo,
            'draftContent' => $draftContent,
        ];
    }

    /**
     * Test for the returned value of the loadVersionInfoById() method.
     *
     * @depends testLoadVersionInfoByIdWithSecondParameter
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById
     *
     * @param array $data
     */
    public function testLoadVersionInfoByIdWithSecondParameterSetsExpectedVersionInfo(array $data)
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo $versionInfo */
        $versionInfo = $data['versionInfo'];
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $draftContent */
        $draftContent = $data['draftContent'];

        $this->assertPropertiesCorrect(
            [
                'names' => [
                    self::ENG_US => 'An awesome forum',
                ],
                'contentInfo' => new ContentInfo([
                    'id' => $draftContent->contentInfo->id,
                    'contentTypeId' => 28,
                    'name' => 'An awesome forum',
                    'sectionId' => 1,
                    'currentVersionNo' => 1,
                    'published' => true,
                    'ownerId' => 14,
                    // this Content Object is created at the test runtime
                    'modificationDate' => $versionInfo->contentInfo->modificationDate,
                    'publishedDate' => $versionInfo->contentInfo->publishedDate,
                    'alwaysAvailable' => 1,
                    'remoteId' => 'abcdef0123456789abcdef0123456789',
                    'mainLanguageCode' => self::ENG_US,
                    'mainLocationId' => $draftContent->contentInfo->mainLocationId,
                    'status' => ContentInfo::STATUS_PUBLISHED,
                ]),
                'id' => $draftContent->versionInfo->id,
                'versionNo' => 2,
                'creatorId' => 14,
                'status' => 0,
                'initialLanguageCode' => self::ENG_US,
                'languageCodes' => [
                    self::ENG_US,
                ],
            ],
            $versionInfo
        );
    }

    /**
     * Test for the loadVersionInfoById() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersionInfoById($contentId, $versionNo)
     */
    public function testLoadVersionInfoByIdThrowsNotFoundExceptionWithSecondParameter()
    {
        $content = $this->createContentVersion1();

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because not versionNo 2 exists for this content object.
        $this->contentService->loadVersionInfoById($content->id, 2);
    }

    /**
     * Test for the loadContentByVersionInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByVersionInfo($versionInfo, $languages)
     *
     * @depends testCreateContent
     * @depends testLoadContentByVersionInfo
     */
    public function testLoadContentByVersionInfoWithSecondParameter()
    {
        $sectionId = $this->generateId('section', 1);
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);

        $contentCreateStruct->setField('name', 'Sindelfingen forum²');

        $contentCreateStruct->setField('name', 'Sindelfingen forum²³', self::ENG_GB);

        $contentCreateStruct->remoteId = 'abcdef0123456789abcdef0123456789';
        // $sectionId contains the ID of section 1
        $contentCreateStruct->sectionId = $sectionId;
        $contentCreateStruct->alwaysAvailable = true;

        // Create a new content draft
        $content = $this->contentService->createContent($contentCreateStruct);

        // Now publish this draft
        $publishedContent = $this->contentService->publishVersion($content->getVersionInfo());

        // Will return a content instance with fields in "eng-US"
        $reloadedContent = $this->contentService->loadContentByVersionInfo(
            $publishedContent->getVersionInfo(),
            [
                self::ENG_GB,
            ],
            false
        );

        $actual = [];
        foreach ($reloadedContent->getFields() as $field) {
            $actual[] = new Field(
                [
                    'id' => 0,
                    'value' => $field->value !== null, // Actual value tested by FieldType integration tests
                    'languageCode' => $field->languageCode,
                    'fieldDefIdentifier' => $field->fieldDefIdentifier,
                ]
            );
        }
        usort(
            $actual,
            static function ($field1, $field2): int {
                if (0 === ($return = strcasecmp($field1->fieldDefIdentifier, $field2->fieldDefIdentifier))) {
                    return strcasecmp($field1->languageCode, $field2->languageCode);
                }

                return $return;
            }
        );

        $expected = [
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_GB,
                    'fieldDefIdentifier' => 'name',
                ]
            ),
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByContentInfo($contentInfo, $languages)
     *
     * @depends testLoadContentByContentInfo
     */
    public function testLoadContentByContentInfoWithLanguageParameters()
    {
        $sectionId = $this->generateId('section', 1);
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

        $contentCreateStruct = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);

        $contentCreateStruct->setField('name', 'Sindelfingen forum²');

        $contentCreateStruct->setField('name', 'Sindelfingen forum²³', self::ENG_GB);

        $contentCreateStruct->remoteId = 'abcdef0123456789abcdef0123456789';
        // $sectionId contains the ID of section 1
        $contentCreateStruct->sectionId = $sectionId;
        $contentCreateStruct->alwaysAvailable = true;

        // Create a new content draft
        $content = $this->contentService->createContent($contentCreateStruct);

        // Now publish this draft
        $publishedContent = $this->contentService->publishVersion($content->getVersionInfo());

        // Will return a content instance with fields in "eng-US"
        $reloadedContent = $this->contentService->loadContentByContentInfo(
            $publishedContent->contentInfo,
            [
                self::ENG_US,
            ],
            null,
            false
        );

        $actual = $this->normalizeFields($reloadedContent->getFields());

        $expected = [
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_US,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
        ];

        self::assertEquals($expected, $actual);

        // Will return a content instance with fields in "eng-GB" (versions prior to 6.0.0-beta9 returned "eng-US" also)
        $reloadedContent = $this->contentService->loadContentByContentInfo(
            $publishedContent->contentInfo,
            [
                self::ENG_GB,
            ],
            null,
            true
        );

        $actual = $this->normalizeFields($reloadedContent->getFields());

        $expected = [
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_GB,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
        ];

        self::assertEquals($expected, $actual);

        // Will return a content instance with fields in main language "eng-US", as "fre-FR" does not exists
        $reloadedContent = $this->contentService->loadContentByContentInfo(
            $publishedContent->contentInfo,
            [
                'fre-FR',
            ],
            null,
            true
        );

        $actual = $this->normalizeFields($reloadedContent->getFields());

        $expected = [
            new Field(
                [
                    'id' => 0,
                    'value' => true,
                    'languageCode' => self::ENG_US,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
        ];

        self::assertEquals($expected, $actual);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByContentInfo($contentInfo, $languages, $versionNo)
     *
     * @depends testLoadContentByContentInfo
     */
    public function testLoadContentByContentInfoWithVersionNumberParameter()
    {
        $publishedContent = $this->createContentVersion1();

        $this->contentService->createContentDraft($publishedContent->contentInfo);

        // This content instance is identical to $draftContent
        $draftContentReloaded = $this->contentService->loadContentByContentInfo(
            $publishedContent->contentInfo,
            null,
            2
        );

        self::assertEquals(
            2,
            $draftContentReloaded->getVersionInfo()->versionNo
        );

        // Check that ContentInfo contained in reloaded draft Content has correct main Location id set
        self::assertEquals(
            $publishedContent->versionInfo->contentInfo->mainLocationId,
            $draftContentReloaded->versionInfo->contentInfo->mainLocationId
        );
    }

    /**
     * Test for the loadContentByContentInfo() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByContentInfo($contentInfo, $languages, $versionNo)
     *
     * @depends testLoadContentByContentInfoWithVersionNumberParameter
     */
    public function testLoadContentByContentInfoThrowsNotFoundExceptionWithVersionNumberParameter()
    {
        $content = $this->createContentVersion1();

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because no content with versionNo = 2 exists.
        $this->contentService->loadContentByContentInfo($content->contentInfo, null, 2);
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent($contentId, $languages)
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testLoadContentWithPrioritizedLanguages()
    {
        $draft = $this->createMultipleLanguageDraftVersion1();

        // This draft contains those fields localized with "eng-GB"
        $draftLocalized = $this->contentService->loadContent($draft->id, [self::ENG_GB], null, false);

        $this->assertLocaleFieldsEquals($draftLocalized->getFields(), self::ENG_GB);

        return $draftLocalized;
    }

    /**
     * Test for the loadContent() method using undefined translation.
     *
     * @depends testLoadContentWithPrioritizedLanguages
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $contentDraft
     */
    public function testLoadContentWithPrioritizedLanguagesThrowsNotFoundException(Content $contentDraft)
    {
        $this->expectException(NotFoundException::class);

        $this->contentService->loadContent($contentDraft->id, [self::GER_DE], null, false);
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent
     *
     * @depends testLoadContentWithPrioritizedLanguages
     */
    public function testLoadContentPassTroughPrioritizedLanguagesToContentType(Content $content): void
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentType(
            $content->contentInfo->contentTypeId,
            [self::ENG_GB]
        );

        self::assertEquals($contentType, $content->getContentType());
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent($contentId, $languages, $versionNo)
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testLoadContentWithThirdParameter()
    {
        $publishedContent = $this->createContentVersion1();

        $this->contentService->createContentDraft($publishedContent->contentInfo);

        // This content instance is identical to $draftContent
        $draftContentReloaded = $this->contentService->loadContent($publishedContent->id, null, 2);

        self::assertEquals(2, $draftContentReloaded->getVersionInfo()->versionNo);

        // Check that ContentInfo contained in reloaded draft Content has correct main Location id set
        self::assertEquals(
            $publishedContent->versionInfo->contentInfo->mainLocationId,
            $draftContentReloaded->versionInfo->contentInfo->mainLocationId
        );
    }

    /**
     * Test for the loadContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContent($contentId, $languages, $versionNo)
     *
     * @depends testLoadContentWithThirdParameter
     */
    public function testLoadContentThrowsNotFoundExceptionWithThirdParameter()
    {
        $content = $this->createContentVersion1();

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because for this content object no versionNo=2 exists.
        $this->contentService->loadContent($content->id, null, 2);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByRemoteId($remoteId, $languages)
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testLoadContentByRemoteIdWithSecondParameter()
    {
        $draft = $this->createMultipleLanguageDraftVersion1();

        $this->contentService->publishVersion($draft->versionInfo);

        // This draft contains those fields localized with "eng-GB"
        $draftLocalized = $this->contentService->loadContentByRemoteId(
            $draft->contentInfo->remoteId,
            [self::ENG_GB],
            null,
            false
        );

        $this->assertLocaleFieldsEquals($draftLocalized->getFields(), self::ENG_GB);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByRemoteId($remoteId, $languages, $versionNo)
     *
     * @depends testPublishVersionFromContentDraft
     */
    public function testLoadContentByRemoteIdWithThirdParameter()
    {
        $publishedContent = $this->createContentVersion1();

        $this->contentService->createContentDraft($publishedContent->contentInfo);

        // This content instance is identical to $draftContent
        $draftContentReloaded = $this->contentService->loadContentByRemoteId(
            $publishedContent->contentInfo->remoteId,
            null,
            2
        );

        self::assertEquals(2, $draftContentReloaded->getVersionInfo()->versionNo);

        // Check that ContentInfo contained in reloaded draft Content has correct main Location id set
        self::assertEquals(
            $publishedContent->versionInfo->contentInfo->mainLocationId,
            $draftContentReloaded->versionInfo->contentInfo->mainLocationId
        );
    }

    /**
     * Test for the loadContentByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadContentByRemoteId($remoteId, $languages, $versionNo)
     *
     * @depends testLoadContentByRemoteIdWithThirdParameter
     */
    public function testLoadContentByRemoteIdThrowsNotFoundExceptionWithThirdParameter()
    {
        $content = $this->createContentVersion1();

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFoundException", because for this content object no versionNo=2 exists.
        $this->contentService->loadContentByRemoteId(
            $content->contentInfo->remoteId,
            null,
            2
        );
    }

    /**
     * Test that retrieval of translated name field respects prioritized language list.
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[]|null $languageCodes
     */
    public function testLoadContentWithPrioritizedLanguagesList($languageCodes)
    {
        $content = $this->createContentVersion2();

        $content = $this->contentService->loadContent($content->id, $languageCodes);

        $expectedName = $content->getVersionInfo()->getName(
            isset($languageCodes[0]) ? $languageCodes[0] : null
        );
        $nameValue = $content->getFieldValue('name');
        /** @var \Ibexa\Core\FieldType\TextLine\Value $nameValue */
        self::assertEquals($expectedName, $nameValue->text);
        self::assertEquals($expectedName, $content->getVersionInfo()->getName());
        // Also check value on shortcut method on content
        self::assertEquals($expectedName, $content->getName());
    }

    /**
     * @return array
     */
    public function getPrioritizedLanguageList()
    {
        return [
            [[self::ENG_US]],
            [[self::ENG_GB]],
            [[self::ENG_GB, self::ENG_US]],
            [[self::ENG_US, self::ENG_GB]],
        ];
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion()
     *
     * @depends testLoadContent
     * @depends testCreateContent
     * @depends testPublishVersion
     * @depends testCreateContentDraft
     */
    public function testDeleteVersion()
    {
        $content = $this->createContentVersion1();

        // Create new draft, because published or last version of the Content can't be deleted
        $draft = $this->contentService->createContentDraft(
            $content->getVersionInfo()->getContentInfo()
        );

        // Delete the previously created draft
        $this->contentService->deleteVersion($draft->getVersionInfo());

        $versions = iterator_to_array(
            $this->contentService->loadVersions($content->getVersionInfo()->getContentInfo())
        );

        self::assertCount(1, $versions);
        self::assertEquals(
            $content->getVersionInfo()->id,
            $versions[0]->id
        );
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion()
     *
     * @depends testLoadContent
     * @depends testCreateContent
     * @depends testPublishVersion
     */
    public function testDeleteVersionThrowsBadStateExceptionOnPublishedVersion()
    {
        $content = $this->createContentVersion1();

        $this->expectException(BadStateException::class);

        // This call will fail with a "BadStateException", because the content version is currently published.
        $this->contentService->deleteVersion($content->getVersionInfo());
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion()
     *
     * @depends testLoadContent
     * @depends testCreateContent
     * @depends testPublishVersion
     */
    public function testDeleteVersionWorksIfOnlyVersionIsDraft()
    {
        $draft = $this->createContentDraftVersion1();

        $this->contentService->deleteVersion($draft->getVersionInfo());

        $this->expectException(NotFoundException::class);

        // This call will fail with a "NotFound", because we allow to delete content if remaining version is draft.
        // Can normally only happen if there where always only a draft to begin with, simplifies UI edit API usage.
        $this->contentService->loadContentInfo($draft->id);
    }

    /**
     * Test for the loadVersions() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersions()
     *
     * @depends testPublishVersion
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo[]
     */
    public function testLoadVersions()
    {
        $contentVersion2 = $this->createContentVersion2();

        // Load versions of this ContentInfo instance
        $versions = $this->contentService->loadVersions($contentVersion2->contentInfo);

        $expectedVersionsOrder = [
            $this->contentService->loadVersionInfo($contentVersion2->contentInfo, 1),
            $this->contentService->loadVersionInfo($contentVersion2->contentInfo, 2),
        ];

        self::assertEquals($expectedVersionsOrder, $versions);

        return iterator_to_array($versions);
    }

    /**
     * Test for the loadVersions() method.
     *
     * @depends testLoadVersions
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadVersions
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo[] $versions
     */
    public function testLoadVersionsSetsExpectedVersionInfo(array $versions)
    {
        self::assertCount(2, $versions);

        $expectedVersions = [
            [
                'versionNo' => 1,
                'creatorId' => 14,
                'status' => VersionInfo::STATUS_ARCHIVED,
                'initialLanguageCode' => self::ENG_US,
                'languageCodes' => [self::ENG_US],
            ],
            [
                'versionNo' => 2,
                'creatorId' => 10,
                'status' => VersionInfo::STATUS_PUBLISHED,
                'initialLanguageCode' => self::ENG_US,
                'languageCodes' => [self::ENG_US, self::ENG_GB],
            ],
        ];

        $this->assertPropertiesCorrect($expectedVersions[0], $versions[0]);
        $this->assertPropertiesCorrect($expectedVersions[1], $versions[1]);
        self::assertEqualsWithDelta(
            $versions[0]->creationDate->getTimestamp(),
            $versions[1]->creationDate->getTimestamp(),
            2,
            'Creation time did not match within delta of 2 seconds',
        );
        self::assertEqualsWithDelta(
            $versions[0]->modificationDate->getTimestamp(),
            $versions[1]->modificationDate->getTimestamp(),
            2,
            'Creation time did not match within delta of 2 seconds',
        );
        self::assertTrue($versions[0]->isArchived());
        self::assertFalse($versions[0]->isDraft());
        self::assertFalse($versions[0]->isPublished());

        self::assertTrue($versions[1]->isPublished());
        self::assertFalse($versions[1]->isDraft());
        self::assertFalse($versions[1]->isArchived());
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent()
     *
     * @depends testPublishVersionFromContentDraft
     *
     * @group field-type
     */
    public function testCopyContent()
    {
        $parentLocationId = $this->generateId('location', 56);

        $contentVersion2 = $this->createMultipleLanguageContentVersion2();

        // Configure new target location
        $targetLocationCreate = $this->locationService->newLocationCreateStruct($parentLocationId);

        $targetLocationCreate->priority = 42;
        $targetLocationCreate->hidden = true;
        $targetLocationCreate->remoteId = '01234abcdef5678901234abcdef56789';
        $targetLocationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $targetLocationCreate->sortOrder = Location::SORT_ORDER_DESC;

        // Copy content with all versions and drafts
        $contentCopied = $this->contentService->copyContent(
            $contentVersion2->contentInfo,
            $targetLocationCreate
        );

        self::assertInstanceOf(
            Content::class,
            $contentCopied
        );

        self::assertNotEquals(
            $contentVersion2->contentInfo->remoteId,
            $contentCopied->contentInfo->remoteId
        );

        self::assertNotEquals(
            $contentVersion2->id,
            $contentCopied->id
        );

        self::assertCount(
            2,
            $this->contentService->loadVersions($contentCopied->contentInfo)
        );

        self::assertEquals(2, $contentCopied->getVersionInfo()->versionNo);

        $this->assertAllFieldsEquals($contentCopied->getFields());

        $this->assertDefaultContentStates($contentCopied->contentInfo);

        self::assertNotNull(
            $contentCopied->contentInfo->mainLocationId,
            'Expected main location to be set given we provided a LocationCreateStruct'
        );
    }

    /**
     * Test for the copyContent() method with ibexa.site_access.config.default.content.retain_owner_on_copy set to false
     * See settings/test/integration_legacy.yml for service override.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent()
     *
     * @depends testPublishVersionFromContentDraft
     *
     * @group field-type
     */
    public function testCopyContentWithNewOwner()
    {
        $parentLocationId = $this->generateId('location', 56);

        $userService = $this->getRepository()->getUserService();

        $owner = $this->createUser('new_owner', 'foo', 'bar');
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $contentVersion2 */
        $contentVersion2 = $this->createContentDraftVersion1(
            $parentLocationId,
            self::FORUM_IDENTIFIER,
            'name',
            $owner
        );

        // Configure new target location
        $targetLocationCreate = $this->locationService->newLocationCreateStruct($parentLocationId);

        $targetLocationCreate->priority = 42;
        $targetLocationCreate->hidden = true;
        $targetLocationCreate->remoteId = '01234abcdef5678901234abcdef56789';
        $targetLocationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $targetLocationCreate->sortOrder = Location::SORT_ORDER_DESC;

        $this->contentService->publishVersion($contentVersion2->versionInfo);
        $this->contentService->createContentDraft($contentVersion2->contentInfo);

        // Copy content with all versions and drafts
        $contentCopied = $this->contentService->copyContent(
            $contentVersion2->contentInfo,
            $targetLocationCreate
        );

        self::assertEquals(
            $owner->id,
            $contentVersion2->contentInfo->ownerId
        );
        $newOwnerId = $userService->loadUserByLogin('admin')->getUserId();
        self::assertEquals(
            $newOwnerId,
            $contentCopied->contentInfo->ownerId
        );
        $versions = $this->contentService->loadVersions($contentCopied->contentInfo);
        self::assertCount(2, $versions);

        foreach ($versions as $version) {
            self::assertEquals(
                $newOwnerId,
                $version->creatorId
            );
        }
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent($contentInfo, $destinationLocationCreateStruct, $versionInfo)
     *
     * @depends testCopyContent
     */
    public function testCopyContentWithGivenVersion()
    {
        $parentLocationId = $this->generateId('location', 56);

        $contentVersion2 = $this->createContentVersion2();

        // Configure new target location
        $targetLocationCreate = $this->locationService->newLocationCreateStruct($parentLocationId);

        $targetLocationCreate->priority = 42;
        $targetLocationCreate->hidden = true;
        $targetLocationCreate->remoteId = '01234abcdef5678901234abcdef56789';
        $targetLocationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $targetLocationCreate->sortOrder = Location::SORT_ORDER_DESC;

        // Copy only the initial version
        $contentCopied = $this->contentService->copyContent(
            $contentVersion2->contentInfo,
            $targetLocationCreate,
            $this->contentService->loadVersionInfo($contentVersion2->contentInfo, 1)
        );

        self::assertInstanceOf(
            Content::class,
            $contentCopied
        );

        self::assertNotEquals(
            $contentVersion2->contentInfo->remoteId,
            $contentCopied->contentInfo->remoteId
        );

        self::assertNotEquals(
            $contentVersion2->id,
            $contentCopied->id
        );

        self::assertCount(
            1,
            $this->contentService->loadVersions($contentCopied->contentInfo)
        );

        self::assertEquals(1, $contentCopied->getVersionInfo()->versionNo);

        self::assertNotNull(
            $contentCopied->contentInfo->mainLocationId,
            'Expected main location to be set given we provided a LocationCreateStruct'
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     *
     * @covers  \Ibexa\Contracts\Core\Repository\ContentService::addRelation
     *
     * @depends testPublishVersionFromContentDraft
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     */
    public function testAddRelation(): array
    {
        $draft = $this->createContentDraftVersion1();

        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // Create relation between new content object and "Media" page
        $relation = $this->contentService->addRelation(
            $draft->getVersionInfo(),
            $media
        );

        self::assertInstanceOf(Relation::class, $relation);

        return array_filter(array_map(
            static fn (RelationListItemInterface $relationListItem): ?Relation => $relationListItem->getRelation(),
            $this->contentService->loadRelationList($draft->getVersionInfo())->items
        ));
    }

    /**
     * Test for the addRelation() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::addRelation()
     *
     * @depends testAddRelation
     */
    public function testAddRelationAddsRelationToContent($relations)
    {
        self::assertCount(
            1,
            $relations
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     */
    protected function assertExpectedRelations($relations)
    {
        self::assertEquals(
            [
                'type' => RelationType::COMMON->value,
                'sourceFieldDefinitionIdentifier' => null,
                'sourceContentInfo' => 'abcdef0123456789abcdef0123456789',
                'destinationContentInfo' => self::MEDIA_REMOTE_ID,
            ],
            [
                'type' => $relations[0]->type,
                'sourceFieldDefinitionIdentifier' => $relations[0]->sourceFieldDefinitionIdentifier,
                'sourceContentInfo' => $relations[0]->sourceContentInfo->remoteId,
                'destinationContentInfo' => $relations[0]->destinationContentInfo->remoteId,
            ]
        );
    }

    /**
     * Test for the addRelation() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::addRelation()
     *
     * @depends testAddRelation
     */
    public function testAddRelationSetsExpectedRelations($relations)
    {
        $this->assertExpectedRelations($relations);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft
     *
     * @depends testAddRelationSetsExpectedRelations
     */
    public function testCreateContentDraftWithRelations()
    {
        $draft = $this->createContentDraftVersion1();
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // Create relation between new content object and "Media" page
        $this->contentService->addRelation(
            $draft->getVersionInfo(),
            $media
        );

        $content = $this->contentService->publishVersion($draft->versionInfo);
        $newDraft = $this->contentService->createContentDraft($content->contentInfo);

        return array_filter(array_map(
            static fn (RelationListItemInterface $relationListItem): ?Relation => $relationListItem->getRelation(),
            $this->contentService->loadRelationList($newDraft->getVersionInfo())->items
        ));
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Relation[]
     *
     * @depends testCreateContentDraftWithRelations
     */
    public function testCreateContentDraftWithRelationsCreatesRelations(array $relations): array
    {
        self::assertCount(
            1,
            $relations
        );

        return $relations;
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Relation[] $relations
     *
     * @depends testCreateContentDraftWithRelationsCreatesRelations
     */
    public function testCreateContentDraftWithRelationsCreatesExpectedRelations($relations)
    {
        $this->assertExpectedRelations($relations);
    }

    /**
     * Test for the addRelation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::addRelation()
     *
     * @depends testAddRelation
     */
    public function testAddRelationThrowsBadStateException()
    {
        $content = $this->createContentVersion1();

        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        $this->expectException(BadStateException::class);

        // This call will fail with a "BadStateException", because content is published and not a draft.
        $this->contentService->addRelation(
            $content->getVersionInfo(),
            $media
        );
    }

    /**
     * Test for the loadRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadRelationList()
     *
     * @depends testAddRelation
     * @depends loadRelationList
     */
    public function testLoadRelationsSkipsArchivedContent()
    {
        $trashService = $this->getRepository()->getTrashService();

        $draft = $this->createContentDraftVersion1();

        // Load other content objects
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        // Create relation between new content object and "Media" page
        $this->contentService->addRelation(
            $draft->getVersionInfo(),
            $media
        );

        // Create another relation with the "Demo Design" page
        $this->contentService->addRelation(
            $draft->getVersionInfo(),
            $demoDesign
        );

        $demoDesignLocation = $this->locationService->loadLocation($demoDesign->mainLocationId);

        // Trashing Content's last Location will change its status to archived,
        // in this case relation towards it will not be loaded.
        $trashService->trash($demoDesignLocation);

        // Load all items
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface[] $items */
        $items = iterator_to_array(
            $this->contentService->loadRelationList($draft->getVersionInfo())->getIterator()
        );

        self::assertCount(1, $items);
        $relation = $items[0]->getRelation();
        self::assertNotNull($relation);
        self::assertEquals(
            [
                [
                    'sourceContentInfo' => 'abcdef0123456789abcdef0123456789',
                    'destinationContentInfo' => self::MEDIA_REMOTE_ID,
                ],
            ],
            [
                [
                    'sourceContentInfo' => $relation->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $relation->destinationContentInfo->remoteId,
                ],
            ]
        );
    }

    /**
     * Test for the loadRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadRelationList()
     *
     * @depends testAddRelation
     * @depends loadRelationList
     */
    public function testLoadRelationsSkipsDraftContent()
    {
        $draft = $this->createContentDraftVersion1();

        // Load other content objects
        $media = $this->contentService->loadContentByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        // Create draft of "Media" page
        $mediaDraft = $this->contentService->createContentDraft($media->contentInfo);

        // Create relation between "Media" page and new content object draft.
        // This relation will not be loaded before the draft is published.
        $this->contentService->addRelation(
            $mediaDraft->getVersionInfo(),
            $draft->getVersionInfo()->getContentInfo()
        );

        // Create another relation with the "Demo Design" page
        $this->contentService->addRelation(
            $mediaDraft->getVersionInfo(),
            $demoDesign
        );

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface[] $items */
        $items = iterator_to_array(
            $this->contentService->loadRelationList($mediaDraft->getVersionInfo())->getIterator()
        );

        self::assertCount(1, $items);
        $relation = $items[0]->getRelation();
        self::assertNotNull($relation);
        self::assertEquals(
            [
                [
                    'sourceContentInfo' => self::MEDIA_REMOTE_ID,
                    'destinationContentInfo' => self::DEMO_DESIGN_REMOTE_ID,
                ],
            ],
            [
                [
                    'sourceContentInfo' => $relation->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $relation->destinationContentInfo->remoteId,
                ],
            ]
        );
    }

    public function testCountRelations(): void
    {
        $draft = $this->createContentWithRelations();

        self::assertEquals(2, $this->contentService->countRelations($draft->getVersionInfo()));
    }

    public function testCountRelationsReturnsZeroByDefault(): void
    {
        $draft = $this->createContentDraftVersion1();

        self::assertSame(0, $this->contentService->countRelations($draft->getVersionInfo()));
    }

    public function testCountRelationsForUnauthorizedUser(): void
    {
        $draft = $this->createContentWithRelations();
        $mediaUser = $this->createMediaUserVersion1();
        $this->permissionResolver->setCurrentUserReference($mediaUser);

        self::assertSame(0, $this->contentService->countRelations($draft->getVersionInfo()));
    }

    public function testCountRelationsWithType(): void
    {
        $draft = $this->createContentDraft('folder', 56, ['name' => 'relation target']);
        $content = $this->createContentWithFieldRelation($this->contentService->publishVersion($draft->getVersionInfo()));

        self::assertEquals(
            0,
            $this->contentService->countRelations($content->getVersionInfo(), RelationType::ASSET)
        );
        self::assertEquals(
            1,
            $this->contentService->countRelations($content->getVersionInfo(), RelationType::FIELD)
        );
    }

    public function testLoadRelationList(): void
    {
        $draft = $this->createContentWithRelations();
        $relationList = $this->contentService->loadRelationList($draft->getVersionInfo());
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        self::assertSame(2, $relationList->totalCount);

        $relation1 = $relationList->items[0]->getRelation();
        $relation2 = $relationList->items[1]->getRelation();

        self::assertNotNull($relation1);
        self::assertNotNull($relation2);

        self::assertEquals(
            $demoDesign,
            $relation1->getDestinationContentInfo()
        );

        self::assertEquals(
            $media,
            $relation2->getDestinationContentInfo()
        );
    }

    public function testLoadRelationListWithPagination(): void
    {
        $draft = $this->createContentWithRelations();
        $versionInfo = $draft->getVersionInfo();
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        $relationPage1 = $this->contentService->loadRelationList($versionInfo, 0, 1);
        $relationPage2 = $this->contentService->loadRelationList($versionInfo, 1, 2);

        self::assertSame(2, $relationPage1->totalCount);
        self::assertSame(2, $relationPage2->totalCount);

        $relation1 = $relationPage1->items[0]->getRelation();
        $relation2 = $relationPage2->items[0]->getRelation();

        self::assertNotNull($relation1);
        self::assertNotNull($relation2);

        self::assertEquals(
            $demoDesign,
            $relation1->getDestinationContentInfo()
        );
        self::assertEquals(
            $media,
            $relation2->getDestinationContentInfo()
        );
    }

    public function testLoadRelationListWithType(): void
    {
        $draft = $this->createContentDraft('folder', 56, ['name' => 'relation target']);
        $content = $this->createContentWithFieldRelation($this->contentService->publishVersion($draft->getVersionInfo()));

        $relationList = $this->contentService->loadRelationList($content->getVersionInfo(), 0, 10, RelationType::FIELD);
        self::assertCount(
            1,
            $relationList->items
        );
        self::assertEquals(
            $draft->getId(),
            $relationList->items[0]->getRelation()?->getDestinationContentInfo()->getId()
        );
    }

    /**
     * Test for the countReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::countReverseRelations
     */
    public function testCountReverseRelations(): void
    {
        $contentWithReverseRelations = $this->createContentWithReverseRelations([
            $this->contentService->createContentDraft(
                $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
            ),
            $this->contentService->createContentDraft(
                $this->createFolder([self::ENG_GB => 'Bar'], 2)->contentInfo
            ),
        ]);

        $contentInfo = $contentWithReverseRelations->content->getVersionInfo()->getContentInfo();

        self::assertEquals(2, $this->contentService->countReverseRelations($contentInfo));
    }

    public function testCountReverseRelationsWithType(): void
    {
        $draft = $this->createContentDraft('folder', 56, ['name' => 'relation target']);
        $relationTargetContent = $this->contentService->publishVersion($draft->getVersionInfo());
        $this->createContentWithFieldRelation($relationTargetContent);

        self::assertEquals(
            0,
            $this->contentService->countReverseRelations($relationTargetContent->getContentInfo(), RelationType::ASSET)
        );
        self::assertEquals(
            1,
            $this->contentService->countReverseRelations($relationTargetContent->getContentInfo(), RelationType::FIELD)
        );
    }

    /**
     * Test for the countReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::countReverseRelations
     */
    public function testCountReverseRelationsReturnsZeroByDefault(): void
    {
        $draft = $this->createContentDraftVersion1();

        self::assertSame(0, $this->contentService->countReverseRelations($draft->getVersionInfo()->getContentInfo()));
    }

    /**
     * Test for the countReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::countReverseRelations
     */
    public function testCountReverseRelationsForUnauthorizedUser(): void
    {
        $contentWithReverseRelations = $this->createContentWithReverseRelations([
            $this->contentService->createContentDraft(
                $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
            ),
        ]);
        $mediaUser = $this->createMediaUserVersion1();
        $this->permissionResolver->setCurrentUserReference($mediaUser);

        $contentInfo = $contentWithReverseRelations->content->contentInfo;

        self::assertSame(0, $this->contentService->countReverseRelations($contentInfo));
    }

    /**
     * Test for the loadReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelations()
     *
     * @depends testAddRelation
     */
    public function testLoadReverseRelations()
    {
        $versionInfo = $this->createContentVersion1()->getVersionInfo();
        $contentInfo = $versionInfo->getContentInfo();

        // Create some drafts
        $mediaDraft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID)
        );
        $demoDesignDraft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID)
        );

        // Create relation between new content object and "Media" page
        $relation1 = $this->contentService->addRelation(
            $mediaDraft->getVersionInfo(),
            $contentInfo
        );

        // Create another relation with the "Demo Design" page
        $relation2 = $this->contentService->addRelation(
            $demoDesignDraft->getVersionInfo(),
            $contentInfo
        );

        // Publish drafts, so relations become active
        $this->contentService->publishVersion($mediaDraft->getVersionInfo());
        $this->contentService->publishVersion($demoDesignDraft->getVersionInfo());

        $relations = $this->contentService->loadRelationList($versionInfo);
        $reverseRelations = iterator_to_array($this->contentService->loadReverseRelations($contentInfo));

        self::assertEquals($contentInfo->id, $relation1->getDestinationContentInfo()->id);
        self::assertEquals($mediaDraft->id, $relation1->getSourceContentInfo()->id);

        self::assertEquals($contentInfo->id, $relation2->getDestinationContentInfo()->id);
        self::assertEquals($demoDesignDraft->id, $relation2->getSourceContentInfo()->id);

        self::assertCount(0, $relations);
        self::assertCount(2, $reverseRelations);

        usort(
            $reverseRelations,
            static function ($rel1, $rel2): int {
                return strcasecmp(
                    $rel2->getSourceContentInfo()->remoteId,
                    $rel1->getSourceContentInfo()->remoteId
                );
            }
        );

        self::assertEquals(
            [
                [
                    'sourceContentInfo' => self::MEDIA_REMOTE_ID,
                    'destinationContentInfo' => 'abcdef0123456789abcdef0123456789',
                ],
                [
                    'sourceContentInfo' => self::DEMO_DESIGN_REMOTE_ID,
                    'destinationContentInfo' => 'abcdef0123456789abcdef0123456789',
                ],
            ],
            [
                [
                    'sourceContentInfo' => $reverseRelations[0]->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $reverseRelations[0]->destinationContentInfo->remoteId,
                ],
                [
                    'sourceContentInfo' => $reverseRelations[1]->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $reverseRelations[1]->destinationContentInfo->remoteId,
                ],
            ]
        );
    }

    /**
     * Test for the loadReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelations()
     *
     * @depends testAddRelation
     * @depends testLoadReverseRelations
     */
    public function testLoadReverseRelationsSkipsArchivedContent()
    {
        $trashService = $this->getRepository()->getTrashService();

        $versionInfo = $this->createContentVersion1()->getVersionInfo();
        $contentInfo = $versionInfo->getContentInfo();

        // Create some drafts
        $mediaDraft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID)
        );
        $demoDesignDraft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID)
        );

        // Create relation between new content object and "Media" page
        $relation1 = $this->contentService->addRelation(
            $mediaDraft->getVersionInfo(),
            $contentInfo
        );

        // Create another relation with the "Demo Design" page
        $relation2 = $this->contentService->addRelation(
            $demoDesignDraft->getVersionInfo(),
            $contentInfo
        );

        // Publish drafts, so relations become active
        $this->contentService->publishVersion($mediaDraft->getVersionInfo());
        $this->contentService->publishVersion($demoDesignDraft->getVersionInfo());

        $demoDesignLocation = $this->locationService->loadLocation($demoDesignDraft->contentInfo->mainLocationId);

        // Trashing Content's last Location will change its status to archived,
        // in this case relation from it will not be loaded.
        $trashService->trash($demoDesignLocation);

        // Load all relations
        $relations = $this->contentService->loadRelationList($versionInfo);
        $reverseRelations = iterator_to_array($this->contentService->loadReverseRelations($contentInfo));

        self::assertEquals($contentInfo->id, $relation1->getDestinationContentInfo()->id);
        self::assertEquals($mediaDraft->id, $relation1->getSourceContentInfo()->id);

        self::assertEquals($contentInfo->id, $relation2->getDestinationContentInfo()->id);
        self::assertEquals($demoDesignDraft->id, $relation2->getSourceContentInfo()->id);

        self::assertCount(0, $relations);
        self::assertCount(1, $reverseRelations);

        self::assertEquals(
            [
                [
                    'sourceContentInfo' => self::MEDIA_REMOTE_ID,
                    'destinationContentInfo' => 'abcdef0123456789abcdef0123456789',
                ],
            ],
            [
                [
                    'sourceContentInfo' => $reverseRelations[0]->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $reverseRelations[0]->destinationContentInfo->remoteId,
                ],
            ]
        );
    }

    /**
     * Test for the loadReverseRelations() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelations()
     *
     * @depends testAddRelation
     * @depends testLoadReverseRelations
     */
    public function testLoadReverseRelationsSkipsDraftContent()
    {
        // Load "Media" page Content
        $media = $this->contentService->loadContentByRemoteId(self::MEDIA_REMOTE_ID);

        // Create some drafts
        $newDraftVersionInfo = $this->createContentDraftVersion1()->getVersionInfo();
        $demoDesignDraft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID)
        );

        // Create relation between "Media" page and new content object
        $relation1 = $this->contentService->addRelation(
            $newDraftVersionInfo,
            $media->contentInfo
        );

        // Create another relation with the "Demo Design" page
        $relation2 = $this->contentService->addRelation(
            $demoDesignDraft->getVersionInfo(),
            $media->contentInfo
        );

        // Publish drafts, so relations become active
        $this->contentService->publishVersion($demoDesignDraft->getVersionInfo());
        // We will not publish new Content draft, therefore relation from it
        // will not be loaded as reverse relation for "Media" page

        $relations = $this->contentService->loadRelationList($media->versionInfo);
        $reverseRelations = iterator_to_array($this->contentService->loadReverseRelations($media->contentInfo));

        self::assertEquals($media->contentInfo->id, $relation1->getDestinationContentInfo()->id);
        self::assertEquals($newDraftVersionInfo->contentInfo->id, $relation1->getSourceContentInfo()->id);

        self::assertEquals($media->contentInfo->id, $relation2->getDestinationContentInfo()->id);
        self::assertEquals($demoDesignDraft->id, $relation2->getSourceContentInfo()->id);

        self::assertCount(0, $relations);
        self::assertCount(1, $reverseRelations);

        self::assertEquals(
            [
                [
                    'sourceContentInfo' => self::DEMO_DESIGN_REMOTE_ID,
                    'destinationContentInfo' => self::MEDIA_REMOTE_ID,
                ],
            ],
            [
                [
                    'sourceContentInfo' => $reverseRelations[0]->sourceContentInfo->remoteId,
                    'destinationContentInfo' => $reverseRelations[0]->destinationContentInfo->remoteId,
                ],
            ]
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelationList
     */
    public function testLoadReverseRelationList(): void
    {
        $draft1 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
        );
        $draft2 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Bar'], 2)->contentInfo
        );
        $draft3 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Baz'], 2)->contentInfo
        );

        $contentWithReverseRelations = $this->createContentWithReverseRelations([
            $draft1,
            $draft2,
            $draft3,
        ]);

        $contentInfo = $contentWithReverseRelations->content->contentInfo;

        $reverseRelationList = $this->contentService->loadReverseRelationList($contentInfo);

        self::assertSame(3, $reverseRelationList->totalCount);
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[2]->contentInfo,
            $reverseRelationList->items[0]->getRelation()->sourceContentInfo
        );
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[1]->contentInfo,
            $reverseRelationList->items[1]->getRelation()->sourceContentInfo
        );
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[0]->contentInfo,
            $reverseRelationList->items[2]->getRelation()->sourceContentInfo
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelationList
     */
    public function testLoadReverseRelationListWithPagination(): void
    {
        $draft1 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
        );
        $draft2 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Bar'], 2)->contentInfo
        );
        $draft3 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Baz'], 2)->contentInfo
        );

        $contentWithReverseRelations = $this->createContentWithReverseRelations([
            $draft1,
            $draft2,
            $draft3,
        ]);

        $contentInfo = $contentWithReverseRelations->content->contentInfo;

        $reverseRelationPage1 = $this->contentService->loadReverseRelationList($contentInfo, 0, 2);
        $reverseRelationPage2 = $this->contentService->loadReverseRelationList($contentInfo, 2, 2);
        self::assertSame(3, $reverseRelationPage1->totalCount);
        self::assertSame(3, $reverseRelationPage2->totalCount);
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[2]->contentInfo,
            $reverseRelationPage1->items[0]->getRelation()->sourceContentInfo
        );
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[1]->contentInfo,
            $reverseRelationPage1->items[1]->getRelation()->sourceContentInfo
        );
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[0]->contentInfo,
            $reverseRelationPage2->items[0]->getRelation()->sourceContentInfo
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelationList
     */
    public function testLoadReverseRelationListSkipsArchivedContent(): void
    {
        $trashService = $this->getRepository()->getTrashService();

        $draft1 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
        );
        $draft2 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Bar'], 2)->contentInfo
        );
        $draft3 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Baz'], 2)->contentInfo
        );

        $contentWithReverseRelations = $this->createContentWithReverseRelations([
            $draft1,
            $draft2,
            $draft3,
        ]);

        $locationToTrash = $this->locationService->loadLocation($draft3->contentInfo->mainLocationId);

        // Trashing Content's last Location will change its status to archived, in this case relation from it will not be loaded.
        $trashService->trash($locationToTrash);

        $contentInfo = $contentWithReverseRelations->content->contentInfo;
        $reverseRelationList = $this->contentService->loadReverseRelationList($contentInfo);

        self::assertSame(2, $reverseRelationList->totalCount);
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[1]->contentInfo,
            $reverseRelationList->items[0]->getRelation()->sourceContentInfo
        );
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[0]->contentInfo,
            $reverseRelationList->items[1]->getRelation()->sourceContentInfo
        );
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::loadReverseRelationList
     */
    public function testLoadReverseRelationListSkipsDraftContent()
    {
        $draft1 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Foo'], 2)->contentInfo
        );

        $contentWithReverseRelations = $this->createContentWithReverseRelations([$draft1]);

        $contentInfo = $contentWithReverseRelations->content->contentInfo;

        // create a relation, but without publishing it
        $draft2 = $this->contentService->createContentDraft(
            $this->createFolder([self::ENG_GB => 'Bar'], 2)->contentInfo
        );
        $this->contentService->addRelation(
            $draft2->getVersionInfo(),
            $contentInfo
        );

        $reverseRelationList = $this->contentService->loadReverseRelationList($contentInfo);

        self::assertSame(1, $reverseRelationList->totalCount);
        self::assertEquals(
            $contentWithReverseRelations->reverseRelations[0]->contentInfo,
            $reverseRelationList->items[0]->getRelation()->sourceContentInfo
        );
    }

    public function testLoadReverseRelationListWithType(): void
    {
        $draft = $this->createContentDraft('folder', 56, ['name' => 'relation target']);
        $targetContent = $this->contentService->publishVersion($draft->getVersionInfo());
        $this->createContentWithFieldRelation($targetContent);

        $relationList = $this->contentService->loadReverseRelationList($targetContent->getContentInfo(), 0, 10, RelationType::FIELD);
        self::assertCount(
            1,
            $relationList->items
        );
        self::assertEquals(
            $draft->getId(),
            $relationList->items[0]->getRelation()?->getDestinationContentInfo()->getId()
        );
    }

    /**
     * Test for the deleteRelation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteRelation()
     *
     * @depends testLoadRelationList
     */
    public function testDeleteRelation()
    {
        $draft = $this->createContentDraftVersion1();

        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);

        // Establish some relations
        $this->contentService->addRelation($draft->getVersionInfo(), $media);
        $this->contentService->addRelation($draft->getVersionInfo(), $demoDesign);

        // Delete one of the currently created relations
        $this->contentService->deleteRelation($draft->getVersionInfo(), $media);

        // The relations array now contains only one element
        $relations = $this->contentService->loadRelationList($draft->getVersionInfo());

        self::assertCount(1, $relations);
    }

    /**
     * Test for the deleteRelation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteRelation()
     *
     * @depends testDeleteRelation
     */
    public function testDeleteRelationThrowsBadStateException()
    {
        $content = $this->createContentVersion1();

        // Load the destination object
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // Create a new draft
        $draftVersion2 = $this->contentService->createContentDraft($content->contentInfo);

        // Add a relation
        $this->contentService->addRelation($draftVersion2->getVersionInfo(), $media);

        // Publish new version
        $contentVersion2 = $this->contentService->publishVersion(
            $draftVersion2->getVersionInfo()
        );

        $this->expectException(BadStateException::class);

        // This call will fail with a "BadStateException", because content is published and not a draft.
        $this->contentService->deleteRelation(
            $contentVersion2->getVersionInfo(),
            $media
        );
    }

    /**
     * Test for the deleteRelation() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteRelation()
     *
     * @depends testDeleteRelation
     */
    public function testDeleteRelationThrowsInvalidArgumentException()
    {
        $draft = $this->createContentDraftVersion1();

        // Load the destination object
        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);

        // This call will fail with a "InvalidArgumentException", because no relation exists between $draft and $media.
        $this->expectException(APIInvalidArgumentException::class);
        $this->contentService->deleteRelation(
            $draft->getVersionInfo(),
            $media
        );
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     * @depends testLoadContent
     */
    public function testCreateContentInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentTypeService = $this->getRepository()->getContentTypeService();

        // Start a transaction
        $repository->beginTransaction();

        try {
            $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

            // Get a content create struct and set mandatory properties
            $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
            $contentCreate->setField('name', 'Sindelfingen forum');

            $contentCreate->remoteId = 'abcdef0123456789abcdef0123456789';
            $contentCreate->alwaysAvailable = true;

            // Create a new content object
            $contentId = $this->contentService->createContent($contentCreate)->id;
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $this->contentService->loadContent($contentId);
        } catch (NotFoundException $e) {
            // This is expected
            return;
        }

        self::fail('Content object still exists after rollback.');
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent()
     *
     * @depends testCreateContent
     * @depends testLoadContent
     */
    public function testCreateContentInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentTypeService = $repository->getContentTypeService();

        // Start a transaction
        $repository->beginTransaction();

        try {
            $contentType = $contentTypeService->loadContentTypeByIdentifier(self::FORUM_IDENTIFIER);

            // Get a content create struct and set mandatory properties
            $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
            $contentCreate->setField('name', 'Sindelfingen forum');

            $contentCreate->remoteId = 'abcdef0123456789abcdef0123456789';
            $contentCreate->alwaysAvailable = true;

            // Create a new content object
            $contentId = $this->contentService->createContent($contentCreate)->id;

            // Commit changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load the new content object
        $content = $this->contentService->loadContent($contentId);

        self::assertEquals($contentId, $content->id);
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent($contentCreateStruct, $locationCreateStructs)
     *
     * @depends testCreateContentWithLocationCreateParameterDoesNotCreateLocationImmediately
     * @depends testLoadContentThrowsNotFoundException
     */
    public function testCreateContentWithLocationCreateParameterInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        // Start a transaction
        $repository->beginTransaction();

        try {
            $draft = $this->createContentDraftVersion1();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $contentId = $draft->id;

        // Roleback the transaction
        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $this->contentService->loadContent($contentId);
        } catch (NotFoundException $e) {
            return;
        }

        self::fail('Can still load content object after rollback.');
    }

    /**
     * Test for the createContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContent($contentCreateStruct, $locationCreateStructs)
     *
     * @depends testCreateContentWithLocationCreateParameterDoesNotCreateLocationImmediately
     * @depends testLoadContentThrowsNotFoundException
     */
    public function testCreateContentWithLocationCreateParameterInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        // Start a transaction
        $repository->beginTransaction();

        try {
            $draft = $this->createContentDraftVersion1();

            $contentId = $draft->id;

            // Roleback the transaction
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load the new content object
        $content = $this->contentService->loadContent($contentId);

        self::assertEquals($contentId, $content->id);
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     * @depends testLoadContent
     */
    public function testCreateContentDraftInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Load the user group content object
        $content = $this->contentService->loadContent($contentId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Create a new draft
            $drafted = $this->contentService->createContentDraft($content->contentInfo);

            // Store version number for later reuse
            $versionNo = $drafted->versionInfo->versionNo;
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback
        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $this->contentService->loadContent($contentId, null, $versionNo);
        } catch (NotFoundException $e) {
            return;
        }

        self::fail('Can still load content draft after rollback');
    }

    /**
     * Test for the createContentDraft() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::createContentDraft()
     *
     * @depends testCreateContentDraft
     * @depends testLoadContent
     */
    public function testCreateContentDraftInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Load the user group content object
        $content = $this->contentService->loadContent($contentId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Create a new draft
            $drafted = $this->contentService->createContentDraft($content->contentInfo);

            // Store version number for later reuse
            $versionNo = $drafted->versionInfo->versionNo;

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $content = $this->contentService->loadContent($contentId, null, $versionNo);

        self::assertEquals(
            $versionNo,
            $content->getVersionInfo()->versionNo
        );
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     * @depends testLoadContent
     */
    public function testPublishVersionInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Load the user group content object
        $content = $this->contentService->loadContent($contentId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            $draftVersion = $this->contentService->createContentDraft($content->contentInfo)->getVersionInfo();

            // Publish a new version
            $content = $this->contentService->publishVersion($draftVersion);

            // Store version number for later reuse
            $versionNo = $content->versionInfo->versionNo;
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback
        $repository->rollback();

        try {
            // This call will fail with a "NotFoundException"
            $this->contentService->loadContent($contentId, null, $versionNo);
        } catch (NotFoundException $e) {
            return;
        }

        self::fail('Can still load content draft after rollback');
    }

    /**
     * Test for the publishVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::publishVersion()
     *
     * @depends testPublishVersion
     * @depends testLoadVersionInfo
     */
    public function testPublishVersionInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        // Load the user group content object
        $template = $this->contentService->loadContent(self::ADMINISTRATORS_USER_GROUP_ID);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Publish a new version
            $content = $this->contentService->publishVersion(
                $this->contentService->createContentDraft($template->contentInfo)->getVersionInfo()
            );

            // Store version number for later reuse
            $versionNo = $content->versionInfo->versionNo;

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load current version info
        $versionInfo = $this->contentService->loadVersionInfo($content->contentInfo);

        self::assertEquals($versionNo, $versionInfo->versionNo);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     * @depends testLoadContent
     * @depends testLoadContentInfo
     */
    public function testUpdateContentInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Create a new user group draft
        $draft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfo($contentId)
        );

        // Get an update struct and change the group name
        $contentUpdate = $this->contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', self::ADMINISTRATORS_USER_GROUP_NAME, self::ENG_US);

        // Start a transaction
        $repository->beginTransaction();

        try {
            // Update the group name
            $draft = $this->contentService->updateContent(
                $draft->getVersionInfo(),
                $contentUpdate
            );

            // Publish updated version
            $this->contentService->publishVersion($draft->getVersionInfo());
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes.
        $repository->rollback();

        // Name will still be "Administrator users"
        $name = $this->contentService->loadContent($contentId)->getFieldValue('name');

        self::assertEquals('Administrator users', $name);
    }

    /**
     * Test for the updateContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContent()
     *
     * @depends testUpdateContent
     * @depends testLoadContent
     * @depends testLoadContentInfo
     */
    public function testUpdateContentInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Create a new user group draft
        $draft = $this->contentService->createContentDraft(
            $this->contentService->loadContentInfo($contentId)
        );

        // Get an update struct and change the group name
        $contentUpdate = $this->contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', self::ADMINISTRATORS_USER_GROUP_NAME, self::ENG_US);

        // Start a transaction
        $repository->beginTransaction();

        try {
            // Update the group name
            $draft = $this->contentService->updateContent(
                $draft->getVersionInfo(),
                $contentUpdate
            );

            // Publish updated version
            $this->contentService->publishVersion($draft->getVersionInfo());

            // Commit all changes.
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Name is now "Administrators"
        $name = $this->contentService->loadContent($contentId)->getFieldValue('name', self::ENG_US);

        self::assertEquals(self::ADMINISTRATORS_USER_GROUP_NAME, $name);
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     * @depends testLoadContentInfo
     */
    public function testUpdateContentMetadataInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Load a ContentInfo object
        $contentInfo = $this->contentService->loadContentInfo($contentId);

        // Store remoteId for later testing
        $remoteId = $contentInfo->remoteId;

        // Start a transaction
        $repository->beginTransaction();

        try {
            // Get metadata update struct and change remoteId
            $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();
            $metadataUpdate->remoteId = md5(microtime(true));

            // Update the metadata of the published content object
            $this->contentService->updateContentMetadata(
                $contentInfo,
                $metadataUpdate
            );
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes.
        $repository->rollback();

        // Load current remoteId
        $remoteIdReloaded = $this->contentService->loadContentInfo($contentId)->remoteId;

        self::assertEquals($remoteId, $remoteIdReloaded);
    }

    /**
     * Test for the updateContentMetadata() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     * @depends testLoadContentInfo
     */
    public function testUpdateContentMetadataInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Load a ContentInfo object
        $contentInfo = $this->contentService->loadContentInfo($contentId);

        // Store remoteId for later testing
        $remoteId = $contentInfo->remoteId;

        // Start a transaction
        $repository->beginTransaction();

        try {
            // Get metadata update struct and change remoteId
            $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();
            $metadataUpdate->remoteId = md5(microtime(true));

            // Update the metadata of the published content object
            $this->contentService->updateContentMetadata(
                $contentInfo,
                $metadataUpdate
            );

            // Commit all changes.
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Load current remoteId
        $remoteIdReloaded = $this->contentService->loadContentInfo($contentId)->remoteId;

        self::assertNotEquals($remoteId, $remoteIdReloaded);
    }

    /**
     * Test for the updateContentMetadata() method, and how cache + transactions play together.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::updateContentMetadata()
     *
     * @depends testUpdateContentMetadata
     * @depends testLoadContentInfo
     */
    public function testUpdateContentMetadataCheckWithinTransaction()
    {
        $repository = $this->getRepository();
        $contentService = $repository->getContentService();
        $contentId = $this->generateId('object', 12);

        // Load a ContentInfo object, and warmup cache
        $contentInfo = $contentService->loadContentInfo($contentId);

        // Store remoteId for later testing
        $remoteId = $contentInfo->remoteId;

        // Start a transaction
        $repository->beginTransaction();

        try {
            // Get metadata update struct and change remoteId
            $metadataUpdate = $contentService->newContentMetadataUpdateStruct();
            $metadataUpdate->remoteId = md5(microtime(true));

            // Update the metadata of the published content object
            $contentService->updateContentMetadata(
                $contentInfo,
                $metadataUpdate
            );

            // Check that it's been updated
            $remoteIdReloaded = $contentService->loadContentInfo($contentId)->remoteId;
            self::assertNotEquals($remoteId, $remoteIdReloaded);

            // Commit all changes.
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion()
     *
     * @depends testCreateContent
     * @depends testLoadContentInfo
     * @depends testLoadContentDraftList
     */
    public function testDeleteVersionInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Create a new draft
            $draft = $this->contentService->createContentDraft(
                $this->contentService->loadContentInfo($contentId)
            );

            $this->contentService->deleteVersion($draft->getVersionInfo());
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes.
        $repository->rollback();

        // This array will be empty
        $drafts = $this->contentService->loadContentDraftList();

        self::assertSame([], $drafts->items);
    }

    /**
     * Test for the deleteVersion() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion()
     *
     * @depends testCreateContent
     * @depends testLoadContentInfo
     * @depends testLoadContentDrafts
     */
    public function testDeleteVersionInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::ADMINISTRATORS_USER_GROUP_ID);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Create a new draft
            $draft = $this->contentService->createContentDraft(
                $this->contentService->loadContentInfo($contentId)
            );

            $this->contentService->deleteVersion($draft->getVersionInfo());

            // Commit all changes.
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // This array will contain no element
        $drafts = $this->contentService->loadContentDraftList();

        self::assertSame([], $drafts->items);
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent()
     *
     * @depends testDeleteContent
     * @depends testLoadContentInfo
     */
    public function testDeleteContentInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::MEMBERS_USER_GROUP_ID);

        // Load a ContentInfo instance
        $contentInfo = $this->contentService->loadContentInfo($contentId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Delete content object
            $this->contentService->deleteContent($contentInfo);
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $repository->rollback();

        // This call will return the original content object
        $contentInfo = $this->contentService->loadContentInfo($contentId);

        self::assertEquals($contentId, $contentInfo->id);
    }

    /**
     * Test for the deleteContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteContent()
     *
     * @depends testDeleteContent
     * @depends testLoadContentInfo
     */
    public function testDeleteContentInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::MEMBERS_USER_GROUP_ID);

        // Load a ContentInfo instance
        $contentInfo = $this->contentService->loadContentInfo($contentId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Delete content object
            $this->contentService->deleteContent($contentInfo);

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Deleted content info is not found anymore
        try {
            $this->contentService->loadContentInfo($contentId);
        } catch (NotFoundException $e) {
            return;
        }

        self::fail('Can still load ContentInfo after commit.');
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent()
     *
     * @depends testCopyContent
     */
    public function testCopyContentInTransactionWithRollback()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::MEMBERS_USER_GROUP_ID);
        $locationId = $this->generateId('location', self::ADMINISTRATORS_USER_GROUP_LOCATION_ID);

        // Load content object to copy
        $content = $this->contentService->loadContent($contentId);

        // Create new target location
        $locationCreate = $this->locationService->newLocationCreateStruct($locationId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Copy content with all versions and drafts
            $this->contentService->copyContent(
                $content->contentInfo,
                $locationCreate
            );
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        // Rollback all changes
        $repository->rollback();

        $this->refreshSearch($repository);

        // This array will only contain a single admin user object
        $locations = $this->locationService->loadLocationChildren(
            $this->locationService->loadLocation($locationId)
        )->locations;

        self::assertCount(1, $locations);
    }

    /**
     * Test for the copyContent() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::copyContent()
     *
     * @depends testCopyContent
     */
    public function testCopyContentInTransactionWithCommit()
    {
        $repository = $this->getRepository();

        $contentId = $this->generateId('object', self::MEMBERS_USER_GROUP_ID);
        $locationId = $this->generateId('location', self::ADMINISTRATORS_USER_GROUP_LOCATION_ID);

        // Load content object to copy
        $content = $this->contentService->loadContent($contentId);

        // Create new target location
        $locationCreate = $this->locationService->newLocationCreateStruct($locationId);

        // Start a new transaction
        $repository->beginTransaction();

        try {
            // Copy content with all versions and drafts
            $this->contentService->copyContent(
                $content->contentInfo,
                $locationCreate
            );

            // Commit all changes
            $repository->commit();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $this->refreshSearch($repository);

        // This will contain the admin user and the new child location
        $locations = $this->locationService->loadLocationChildren(
            $this->locationService->loadLocation($locationId)
        )->locations;

        self::assertCount(2, $locations);
    }

    public function testURLAliasesCreatedForNewContent()
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $draft = $this->createContentDraftVersion1();

        // Automatically creates a new URLAlias for the content
        $liveContent = $this->contentService->publishVersion($draft->getVersionInfo());

        $location = $this->locationService->loadLocation(
            $liveContent->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $aliases = $urlAliasService->listLocationAliases($location, false);

        $this->assertAliasesCorrect(
            [
                '/Design/Plain-site/An-awesome-forum' => [
                    'type' => URLAlias::LOCATION,
                    'destination' => $location->id,
                    'path' => '/Design/Plain-site/An-awesome-forum',
                    'languageCodes' => [self::ENG_US],
                    'isHistory' => false,
                    'isCustom' => false,
                    'forward' => false,
                ],
            ],
            $aliases
        );
    }

    public function testURLAliasesCreatedForUpdatedContent()
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $draft = $this->createUpdatedDraftVersion2();

        $location = $this->locationService->loadLocation(
            $draft->getVersionInfo()->getContentInfo()->mainLocationId
        );

        // Load and assert URL aliases before publishing updated Content, so that
        // SPI cache is warmed up and cache invalidation is also tested.
        $aliases = $urlAliasService->listLocationAliases($location, false);

        $this->assertAliasesCorrect(
            [
                '/Design/Plain-site/An-awesome-forum' => [
                    'type' => URLAlias::LOCATION,
                    'destination' => $location->id,
                    'path' => '/Design/Plain-site/An-awesome-forum',
                    'languageCodes' => [self::ENG_US],
                    'alwaysAvailable' => true,
                    'isHistory' => false,
                    'isCustom' => false,
                    'forward' => false,
                ],
            ],
            $aliases
        );

        // Automatically marks old aliases for the content as history
        // and creates new aliases, based on the changes
        $liveContent = $this->contentService->publishVersion($draft->getVersionInfo());

        $location = $this->locationService->loadLocation(
            $liveContent->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $aliases = $urlAliasService->listLocationAliases($location, false);

        $this->assertAliasesCorrect(
            [
                '/Design/Plain-site/An-awesome-forum2' => [
                    'type' => URLAlias::LOCATION,
                    'destination' => $location->id,
                    'path' => '/Design/Plain-site/An-awesome-forum2',
                    'languageCodes' => [self::ENG_US],
                    'alwaysAvailable' => true,
                    'isHistory' => false,
                    'isCustom' => false,
                    'forward' => false,
                ],
                '/Design/Plain-site/An-awesome-forum23' => [
                    'type' => URLAlias::LOCATION,
                    'destination' => $location->id,
                    'path' => '/Design/Plain-site/An-awesome-forum23',
                    'languageCodes' => [self::ENG_GB],
                    'alwaysAvailable' => true,
                    'isHistory' => false,
                    'isCustom' => false,
                    'forward' => false,
                ],
            ],
            $aliases
        );
    }

    public function testCustomURLAliasesNotHistorizedOnUpdatedContent()
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $content = $this->createContentVersion1();

        // Create a custom URL alias
        $urlAliasService->createUrlAlias(
            $this->locationService->loadLocation(
                $content->getVersionInfo()->getContentInfo()->mainLocationId
            ),
            '/my/fancy/story-about-ibexa-dxp',
            self::ENG_US
        );

        $draftVersion2 = $this->contentService->createContentDraft($content->contentInfo);

        $contentUpdate = $this->contentService->newContentUpdateStruct();
        $contentUpdate->initialLanguageCode = self::ENG_US;
        $contentUpdate->setField('name', 'Amazing Bielefeld forum');

        $draftVersion2 = $this->contentService->updateContent(
            $draftVersion2->getVersionInfo(),
            $contentUpdate
        );

        // Only marks auto-generated aliases as history
        // the custom one is left untouched
        $liveContent = $this->contentService->publishVersion($draftVersion2->getVersionInfo());

        $location = $this->locationService->loadLocation(
            $liveContent->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $aliases = $urlAliasService->listLocationAliases($location);

        $this->assertAliasesCorrect(
            [
                '/my/fancy/story-about-ibexa-dxp' => [
                    'type' => URLAlias::LOCATION,
                    'destination' => $location->id,
                    'path' => '/my/fancy/story-about-ibexa-dxp',
                    'languageCodes' => [self::ENG_US],
                    'isHistory' => false,
                    'isCustom' => true,
                    'forward' => false,
                    'alwaysAvailable' => false,
                ],
            ],
            $aliases
        );
    }

    /**
     * Test to ensure that old versions are not affected by updates to newer
     * drafts.
     */
    public function testUpdatingDraftDoesNotUpdateOldVersions()
    {
        $contentVersion2 = $this->createContentVersion2();

        $loadedContent1 = $this->contentService->loadContent($contentVersion2->id, null, 1);
        $loadedContent2 = $this->contentService->loadContent($contentVersion2->id, null, 2);

        self::assertNotEquals(
            $loadedContent1->getFieldValue('name', self::ENG_US),
            $loadedContent2->getFieldValue('name', self::ENG_US)
        );
    }

    /**
     * Test scenario with writer and publisher users.
     * Writer can only create content. Publisher can publish this content.
     */
    public function testPublishWorkflow()
    {
        $this->createRoleWithPolicies('Publisher', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'create'],
            ['module' => 'content', 'function' => 'publish'],
        ]);

        $this->createRoleWithPolicies('Writer', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'create'],
        ]);

        $writerUser = $this->createCustomUserWithLogin(
            'writer',
            'writer@example.com',
            self::WRITERS_USER_GROUP_NAME,
            'Writer'
        );

        $publisherUser = $this->createCustomUserWithLogin(
            'publisher',
            'publisher@example.com',
            'Publishers',
            'Publisher'
        );

        $this->permissionResolver->setCurrentUserReference($writerUser);
        $draft = $this->createContentDraftVersion1();

        $this->permissionResolver->setCurrentUserReference($publisherUser);
        $content = $this->contentService->publishVersion($draft->versionInfo);

        $this->contentService->loadContent($content->id);
    }

    /**
     * Test publish / content policy is required to be able to publish content.
     */
    public function testPublishContentWithoutPublishPolicyThrowsException()
    {
        $this->createRoleWithPolicies('Writer', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'create'],
            ['module' => 'content', 'function' => 'edit'],
        ]);
        $writerUser = $this->createCustomUserWithLogin(
            'writer',
            'writer@example.com',
            self::WRITERS_USER_GROUP_NAME,
            'Writer'
        );
        $this->permissionResolver->setCurrentUserReference($writerUser);

        $this->expectException(CoreUnauthorizedException::class);
        $this->expectExceptionMessageMatches('/The User does not have the \'publish\' \'content\' permission/');

        $this->createContentVersion1();
    }

    /**
     * Test removal of the specific translation from all the Versions of a Content Object.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslation()
    {
        $content = $this->createContentVersion2();

        // create multiple versions to exceed archive limit
        for ($i = 0; $i < 5; ++$i) {
            $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
            $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
            $contentDraft = $this->contentService->updateContent(
                $contentDraft->versionInfo,
                $contentUpdateStruct
            );
            $this->contentService->publishVersion($contentDraft->versionInfo);
        }

        $this->contentService->deleteTranslation($content->contentInfo, self::ENG_GB);

        $this->assertTranslationDoesNotExist(self::ENG_GB, $content->id);
    }

    /**
     * Test deleting a Translation which is initial for some Version, updates initialLanguageCode
     * with mainLanguageCode (assuming they are different).
     */
    public function testDeleteTranslationUpdatesInitialLanguageCodeVersion()
    {
        $content = $this->createContentVersion2();
        // create another, copied, version
        $contentDraft = $this->contentService->updateContent(
            $this->contentService->createContentDraft($content->contentInfo)->versionInfo,
            $this->contentService->newContentUpdateStruct()
        );
        $publishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

        // remove first version with only one translation as it is not the subject of this test
        $this->contentService->deleteVersion(
            $this->contentService->loadVersionInfo($publishedContent->contentInfo, 1)
        );

        // sanity check
        self::assertEquals(self::ENG_US, $content->contentInfo->mainLanguageCode);
        self::assertEquals(self::ENG_US, $content->versionInfo->initialLanguageCode);

        // update mainLanguageCode so it is different than initialLanguageCode for Version
        $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
        $contentMetadataUpdateStruct->mainLanguageCode = self::ENG_GB;
        $content = $this->contentService->updateContentMetadata($publishedContent->contentInfo, $contentMetadataUpdateStruct);

        $this->contentService->deleteTranslation($content->contentInfo, self::ENG_US);

        $this->assertTranslationDoesNotExist(self::ENG_US, $content->id);
    }

    /**
     * Test removal of the specific translation properly updates languages of the URL alias.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslationUpdatesUrlAlias()
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $content = $this->createContentVersion2();
        $mainLocation = $this->locationService->loadLocation($content->contentInfo->mainLocationId);

        // create custom URL alias for Content main Location
        $urlAliasService->createUrlAlias($mainLocation, '/my-custom-url', self::ENG_GB);

        // create secondary Location for Content
        $secondaryLocation = $this->locationService->createLocation(
            $content->contentInfo,
            $this->locationService->newLocationCreateStruct(2)
        );

        // create custom URL alias for Content secondary Location
        $urlAliasService->createUrlAlias($secondaryLocation, '/my-secondary-url', self::ENG_GB);

        // delete Translation
        $this->contentService->deleteTranslation($content->contentInfo, self::ENG_GB);

        foreach ([$mainLocation, $secondaryLocation] as $location) {
            // check auto-generated URL aliases
            foreach ($urlAliasService->listLocationAliases($location, false) as $alias) {
                self::assertNotContains(self::ENG_GB, $alias->languageCodes);
            }

            // check custom URL aliases
            foreach ($urlAliasService->listLocationAliases($location) as $alias) {
                self::assertNotContains(self::ENG_GB, $alias->languageCodes);
            }
        }
    }

    /**
     * Test removal of a main translation throws BadStateException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslationMainLanguageThrowsBadStateException()
    {
        $content = $this->createContentVersion2();

        // delete first version which has only one translation
        $this->contentService->deleteVersion($this->contentService->loadVersionInfo($content->contentInfo, 1));

        // try to delete main translation
        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage('The provided translation is the main translation of the Content item');

        $this->contentService->deleteTranslation($content->contentInfo, $content->contentInfo->mainLanguageCode);
    }

    /**
     * Test removal of a Translation is possible when some archived Versions have only this Translation.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslationDeletesSingleTranslationVersions()
    {
        // content created by the createContentVersion1 method has eng-US translation only.
        $content = $this->createContentVersion1();

        // create new version and add eng-GB translation
        $contentDraft = $this->contentService->createContentDraft($content->contentInfo);
        $contentUpdateStruct = $this->contentService->newContentUpdateStruct();
        $contentUpdateStruct->setField('name', 'Awesome Board', self::ENG_GB);
        $contentDraft = $this->contentService->updateContent($contentDraft->versionInfo, $contentUpdateStruct);
        $publishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

        // update mainLanguageCode to avoid exception related to that
        $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
        $contentMetadataUpdateStruct->mainLanguageCode = self::ENG_GB;

        $content = $this->contentService->updateContentMetadata($publishedContent->contentInfo, $contentMetadataUpdateStruct);

        $this->contentService->deleteTranslation($content->contentInfo, self::ENG_US);

        $this->assertTranslationDoesNotExist(self::ENG_US, $content->id);
    }

    /**
     * Test removal of the translation by the user who is not allowed to delete a content
     * throws UnauthorizedException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslationThrowsUnauthorizedException()
    {
        $content = $this->createContentVersion2();

        // create user that can read/create/edit but cannot delete content
        $this->createRoleWithPolicies('Writer', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'versionread'],
            ['module' => 'content', 'function' => 'create'],
            ['module' => 'content', 'function' => 'edit'],
        ]);
        $writerUser = $this->createCustomUserWithLogin(
            'writer',
            'writer@example.com',
            self::WRITERS_USER_GROUP_NAME,
            'Writer'
        );
        $this->permissionResolver->setCurrentUserReference($writerUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('The User does not have the \'remove\' \'content\' permission');

        $this->contentService->deleteTranslation($content->contentInfo, self::ENG_GB);
    }

    /**
     * Test removal of a non-existent translation throws InvalidArgumentException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslation
     */
    public function testDeleteTranslationThrowsInvalidArgumentException()
    {
        // content created by the createContentVersion1 method has eng-US translation only.
        $content = $this->createContentVersion1();

        $this->expectException(APIInvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'$languageCode\' is invalid: ger-DE does not exist in the Content item');

        $this->contentService->deleteTranslation($content->contentInfo, self::GER_DE);
    }

    /**
     * Test deleting a Translation from Draft.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraft()
    {
        $languageCode = self::ENG_GB;
        $content = $this->createMultipleLanguageContentVersion2();
        $draft = $this->contentService->createContentDraft($content->contentInfo);
        $draft = $this->contentService->deleteTranslationFromDraft($draft->versionInfo, $languageCode);
        $content = $this->contentService->publishVersion($draft->versionInfo);

        $loadedContent = $this->contentService->loadContent($content->id);
        self::assertNotContains($languageCode, $loadedContent->versionInfo->languageCodes);
        self::assertEmpty($loadedContent->getFieldsByLanguage($languageCode));
    }

    /**
     * Get values for multilingual field.
     *
     * @return array
     */
    public function providerForDeleteTranslationFromDraftRemovesUrlAliasOnPublishing()
    {
        return [
            [
                [self::ENG_US => 'US Name', self::ENG_GB => 'GB Name'],
            ],
            [
                [self::ENG_US => 'Same Name', self::ENG_GB => 'Same Name'],
            ],
        ];
    }

    /**
     * Test deleting a Translation from Draft removes previously stored URL aliases for published Content.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     *
     * @dataProvider providerForDeleteTranslationFromDraftRemovesUrlAliasOnPublishing
     *
     * @param string[] $fieldValues translated field values
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testDeleteTranslationFromDraftRemovesUrlAliasOnPublishing(array $fieldValues)
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        // set language code to be removed
        $languageCode = self::ENG_GB;
        $draft = $this->createMultilingualContentDraft(
            'folder',
            2,
            self::ENG_US,
            [
                'name' => [
                    self::ENG_GB => $fieldValues[self::ENG_GB],
                    self::ENG_US => $fieldValues[self::ENG_US],
                ],
            ]
        );
        $content = $this->contentService->publishVersion($draft->versionInfo);

        // create secondary location
        $this->locationService->createLocation(
            $content->contentInfo,
            $this->locationService->newLocationCreateStruct(5)
        );

        // sanity check
        $locations = $this->locationService->loadLocations($content->contentInfo);
        self::assertCount(2, $locations, 'Sanity check: Expected to find 2 Locations');
        foreach ($locations as $location) {
            $urlAliasService->createUrlAlias($location, '/us-custom_' . $location->id, self::ENG_US);
            $urlAliasService->createUrlAlias($location, '/gb-custom_' . $location->id, self::ENG_GB);

            // check default URL aliases
            $aliases = $urlAliasService->listLocationAliases($location, false, $languageCode);
            self::assertNotEmpty($aliases, 'Sanity check: URL alias for the translation does not exist');

            // check custom URL aliases
            $aliases = $urlAliasService->listLocationAliases($location, true, $languageCode);
            self::assertNotEmpty($aliases, 'Sanity check: Custom URL alias for the translation does not exist');
        }

        // delete translation and publish new version
        $draft = $this->contentService->createContentDraft($content->contentInfo);
        $draft = $this->contentService->deleteTranslationFromDraft($draft->versionInfo, $languageCode);
        $this->contentService->publishVersion($draft->versionInfo);

        // check that aliases does not exist
        foreach ($locations as $location) {
            // check default URL aliases
            $aliases = $urlAliasService->listLocationAliases($location, false, $languageCode);
            self::assertEmpty($aliases, 'URL alias for the deleted translation still exists');

            // check custom URL aliases
            $aliases = $urlAliasService->listLocationAliases($location, true, $languageCode);
            self::assertEmpty($aliases, 'Custom URL alias for the deleted translation still exists');
        }
    }

    /**
     * Test that URL aliases for deleted Translations are properly archived.
     */
    public function testDeleteTranslationFromDraftArchivesUrlAliasOnPublishing()
    {
        $urlAliasService = $this->getRepository()->getURLAliasService();

        $content = $this->contentService->publishVersion(
            $this->createMultilingualContentDraft(
                'folder',
                2,
                self::ENG_US,
                [
                    'name' => [
                        self::ENG_GB => 'BritishEnglishContent',
                        self::ENG_US => 'AmericanEnglishContent',
                    ],
                ]
            )->versionInfo
        );

        $unrelatedContent = $this->contentService->publishVersion(
            $this->createMultilingualContentDraft(
                'folder',
                2,
                self::ENG_US,
                [
                    'name' => [
                        self::ENG_GB => 'AnotherBritishContent',
                        self::ENG_US => 'AnotherAmericanContent',
                    ],
                ]
            )->versionInfo
        );

        $urlAlias = $urlAliasService->lookup('/BritishEnglishContent');
        self::assertFalse($urlAlias->isHistory);
        self::assertEquals($urlAlias->path, '/BritishEnglishContent');
        self::assertEquals($urlAlias->destination, $content->contentInfo->mainLocationId);

        $draft = $this->contentService->deleteTranslationFromDraft(
            $this->contentService->createContentDraft($content->contentInfo)->versionInfo,
            self::ENG_GB
        );
        $content = $this->contentService->publishVersion($draft->versionInfo);

        $urlAlias = $urlAliasService->lookup('/BritishEnglishContent');
        self::assertTrue($urlAlias->isHistory);
        self::assertEquals($urlAlias->path, '/BritishEnglishContent');
        self::assertEquals($urlAlias->destination, $content->contentInfo->mainLocationId);

        $unrelatedUrlAlias = $urlAliasService->lookup('/AnotherBritishContent');
        self::assertFalse($unrelatedUrlAlias->isHistory);
        self::assertEquals($unrelatedUrlAlias->path, '/AnotherBritishContent');
        self::assertEquals($unrelatedUrlAlias->destination, $unrelatedContent->contentInfo->mainLocationId);
    }

    /**
     * Test deleting a Translation from Draft which has single Translation throws BadStateException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraftThrowsBadStateExceptionOnSingleTranslation()
    {
        // create Content with single Translation
        $publishedContent = $this->contentService->publishVersion(
            $this->createContentDraft(
                self::FORUM_IDENTIFIER,
                2,
                ['name' => 'Eng-US Version name']
            )->versionInfo
        );

        // update mainLanguageCode to avoid exception related to trying to delete main Translation
        $contentMetadataUpdateStruct = $this->contentService->newContentMetadataUpdateStruct();
        $contentMetadataUpdateStruct->mainLanguageCode = self::ENG_GB;
        $publishedContent = $this->contentService->updateContentMetadata(
            $publishedContent->contentInfo,
            $contentMetadataUpdateStruct
        );

        // create single Translation Version from the first one
        $draft = $this->contentService->createContentDraft(
            $publishedContent->contentInfo,
            $publishedContent->versionInfo
        );

        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage('The provided translation is the only translation in this version');

        // attempt to delete Translation
        $this->contentService->deleteTranslationFromDraft($draft->versionInfo, self::ENG_US);
    }

    /**
     * Test deleting the Main Translation from Draft throws BadStateException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraftThrowsBadStateExceptionOnMainTranslation()
    {
        $mainLanguageCode = self::ENG_US;
        $draft = $this->createMultilingualContentDraft(
            self::FORUM_IDENTIFIER,
            2,
            $mainLanguageCode,
            [
                'name' => [
                    self::ENG_US => 'An awesome eng-US forum',
                    self::ENG_GB => 'An awesome eng-GB forum',
                ],
            ]
        );

        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage('the specified translation is the main translation of the Content item');

        $this->contentService->deleteTranslationFromDraft($draft->versionInfo, $mainLanguageCode);
    }

    /**
     * Test deleting the Translation from Published Version throws BadStateException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraftThrowsBadStateExceptionOnPublishedVersion()
    {
        $languageCode = self::ENG_US;
        $content = $this->createMultipleLanguageContentVersion2();
        $draft = $this->contentService->createContentDraft($content->contentInfo);
        $publishedContent = $this->contentService->publishVersion($draft->versionInfo);

        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage('The version is not a draft');

        $this->contentService->deleteTranslationFromDraft($publishedContent->versionInfo, $languageCode);
    }

    /**
     * Test deleting a Translation from Draft throws UnauthorizedException if user cannot edit Content.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraftThrowsUnauthorizedException()
    {
        $languageCode = self::ENG_GB;
        $content = $this->createMultipleLanguageContentVersion2();
        $draft = $this->contentService->createContentDraft($content->contentInfo);

        // create user that can read/create/delete but cannot edit or content
        $this->createRoleWithPolicies('Writer', [
            ['module' => 'content', 'function' => 'read'],
            ['module' => 'content', 'function' => 'versionread'],
            ['module' => 'content', 'function' => 'create'],
            ['module' => 'content', 'function' => 'delete'],
        ]);
        $writerUser = $this->createCustomUserWithLogin(
            'user',
            'user@example.com',
            self::WRITERS_USER_GROUP_NAME,
            'Writer'
        );
        $this->permissionResolver->setCurrentUserReference($writerUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('The User does not have the \'edit\' \'content\' permission');

        $this->contentService->deleteTranslationFromDraft($draft->versionInfo, $languageCode);
    }

    /**
     * Test deleting a non-existent Translation from Draft throws InvalidArgumentException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteTranslationFromDraft
     */
    public function testDeleteTranslationFromDraftThrowsInvalidArgumentException()
    {
        $languageCode = self::GER_DE;
        $content = $this->createMultipleLanguageContentVersion2();
        $draft = $this->contentService->createContentDraft($content->contentInfo);
        $this->expectException(APIInvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/The version \(ContentId=\d+, VersionNo=\d+\) is not translated into ger-DE/');
        $this->contentService->deleteTranslationFromDraft($draft->versionInfo, $languageCode);
    }

    /**
     * Test loading list of Content items.
     */
    public function testLoadContentListByContentInfo()
    {
        $allLocationsCount = $this->locationService->getAllLocationsCount();
        $contentInfoList = array_map(
            static function (Location $location) {
                return $location->contentInfo;
            },
            $this->locationService->loadAllLocations(0, $allLocationsCount)
        );

        $contentList = $this->contentService->loadContentListByContentInfo($contentInfoList);
        self::assertCount(count($contentInfoList), $contentList);
        foreach ($contentList as $content) {
            try {
                $loadedContent = $this->contentService->loadContent($content->id);
                self::assertEquals($loadedContent, $content, "Failed to properly bulk-load Content {$content->id}");
            } catch (NotFoundException $e) {
                self::fail("Failed to load Content {$content->id}: {$e->getMessage()}");
            } catch (UnauthorizedException $e) {
                self::fail("Failed to load Content {$content->id}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Test loading content versions after removing exactly two drafts.
     *
     * @see https://issues.ibexa.co/browse/EZP-30271
     *
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::deleteVersion
     */
    public function testLoadVersionsAfterDeletingTwoDrafts()
    {
        $content = $this->createFolder([self::ENG_GB => 'Foo'], 2);

        // First update and publish
        $modifiedContent = $this->updateFolder($content, [self::ENG_GB => 'Foo1']);
        $content = $this->contentService->publishVersion($modifiedContent->versionInfo);

        // Second update and publish
        $modifiedContent = $this->updateFolder($content, [self::ENG_GB => 'Foo2']);
        $content = $this->contentService->publishVersion($modifiedContent->versionInfo);

        // Create drafts
        $this->updateFolder($content, [self::ENG_GB => 'Foo3']);
        $this->updateFolder($content, [self::ENG_GB => 'Foo4']);

        $versions = iterator_to_array($this->contentService->loadVersions($content->contentInfo));

        foreach ($versions as $key => $version) {
            if ($version->isDraft()) {
                $this->contentService->deleteVersion($version);
                unset($versions[$key]);
            }
        }

        self::assertEquals($versions, $this->contentService->loadVersions($content->contentInfo));
    }

    /**
     * Tests loading list of content versions of status draft.
     */
    public function testLoadVersionsOfStatusDraft()
    {
        $content = $this->createContentVersion1();

        $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->createContentDraft($content->contentInfo);

        $versions = $this->contentService->loadVersions($content->contentInfo, VersionInfo::STATUS_DRAFT);

        self::assertSame(\iterator_count($versions), 3);
    }

    /**
     * Tests loading list of content versions of status archived.
     */
    public function testLoadVersionsOfStatusArchived()
    {
        $content = $this->createContentVersion1();

        $draft1 = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draft1->versionInfo);

        $draft2 = $this->contentService->createContentDraft($content->contentInfo);
        $this->contentService->publishVersion($draft2->versionInfo);

        $versions = $this->contentService->loadVersions($content->contentInfo, VersionInfo::STATUS_ARCHIVED);

        self::assertSame(\iterator_count($versions), 2);
    }

    /**
     * Asserts that all aliases defined in $expectedAliasProperties with the
     * given properties are available in $actualAliases and not more.
     *
     * @param array $expectedAliasProperties
     * @param array $actualAliases
     */
    private function assertAliasesCorrect(array $expectedAliasProperties, array $actualAliases)
    {
        foreach ($actualAliases as $actualAlias) {
            if (!isset($expectedAliasProperties[$actualAlias->path])) {
                self::fail(
                    sprintf(
                        'Alias with path "%s" in languages "%s" not expected.',
                        $actualAlias->path,
                        implode(', ', $actualAlias->languageCodes)
                    )
                );
            }

            foreach ($expectedAliasProperties[$actualAlias->path] as $propertyName => $propertyValue) {
                self::assertEquals(
                    $propertyValue,
                    $actualAlias->$propertyName,
                    sprintf(
                        'Property $%s incorrect for alias with path "%s" in languages "%s".',
                        $propertyName,
                        $actualAlias->path,
                        implode(', ', $actualAlias->languageCodes)
                    )
                );
            }

            unset($expectedAliasProperties[$actualAlias->path]);
        }

        if (!empty($expectedAliasProperties)) {
            self::fail(
                sprintf(
                    'Missing expected aliases with paths "%s".',
                    implode('", "', array_keys($expectedAliasProperties))
                )
            );
        }
    }

    /**
     * Asserts that the given fields are equal to the default fields fixture.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $fields
     */
    private function assertAllFieldsEquals(array $fields)
    {
        $actual = $this->normalizeFields($fields);
        $expected = $this->normalizeFields($this->createFieldsFixture());

        self::assertEquals($expected, $actual);
    }

    /**
     * Asserts that the given fields are equal to a language filtered set of the
     * default fields fixture.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $fields
     * @param string $languageCode
     */
    private function assertLocaleFieldsEquals(array $fields, $languageCode)
    {
        $actual = $this->normalizeFields($fields);

        $expected = [];
        foreach ($this->normalizeFields($this->createFieldsFixture()) as $field) {
            if ($field->languageCode !== $languageCode) {
                continue;
            }
            $expected[] = $field;
        }

        self::assertEquals($expected, $actual);
    }

    /**
     * This method normalizes a set of fields and returns a normalized set.
     *
     * Normalization means it resets the storage specific field id to zero and
     * it sorts the field by their identifier and their language code. In
     * addition, the field value is removed, since this one depends on the
     * specific FieldType, which is tested in a dedicated integration test.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Field[] $fields
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field[]
     */
    private function normalizeFields(array $fields)
    {
        $normalized = [];
        foreach ($fields as $field) {
            $normalized[] = new Field(
                [
                    'id' => 0,
                    'value' => $field->value !== null,
                    'languageCode' => $field->languageCode,
                    'fieldDefIdentifier' => $field->fieldDefIdentifier,
                    'fieldTypeIdentifier' => $field->fieldTypeIdentifier,
                ]
            );
        }
        usort(
            $normalized,
            static function ($field1, $field2): int {
                if (0 === ($return = strcasecmp($field1->fieldDefIdentifier, $field2->fieldDefIdentifier))) {
                    return strcasecmp($field1->languageCode, $field2->languageCode);
                }

                return $return;
            }
        );

        return $normalized;
    }

    /**
     * Asserts that given Content has default ContentStates.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo $contentInfo
     */
    private function assertDefaultContentStates(ContentInfo $contentInfo)
    {
        $objectStateService = $this->getRepository()->getObjectStateService();

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
     * Assert that given Content has no references to a translation specified by the $languageCode.
     *
     * @param string $languageCode
     * @param int $contentId
     */
    private function assertTranslationDoesNotExist($languageCode, $contentId)
    {
        $content = $this->contentService->loadContent($contentId);

        foreach ($content->fields as $field) {
            /** @var array $field */
            self::assertArrayNotHasKey($languageCode, $field);
            self::assertNotEquals($languageCode, $content->contentInfo->mainLanguageCode);
            self::assertArrayNotHasKey($languageCode, $content->versionInfo->getNames());
            self::assertNotEquals($languageCode, $content->versionInfo->initialLanguageCode);
            self::assertNotContains($languageCode, $content->versionInfo->languageCodes);
        }
        foreach ($this->contentService->loadVersions($content->contentInfo) as $versionInfo) {
            self::assertArrayNotHasKey($languageCode, $versionInfo->getNames());
            self::assertNotEquals($languageCode, $versionInfo->contentInfo->mainLanguageCode);
            self::assertNotEquals($languageCode, $versionInfo->initialLanguageCode);
            self::assertNotContains($languageCode, $versionInfo->languageCodes);
        }
    }

    /**
     * Returns the default fixture of fields used in most tests.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Field[]
     */
    private function createFieldsFixture()
    {
        return [
            new Field(
                [
                    'id' => 0,
                    'value' => 'An awesome multi-lang forum²',
                    'languageCode' => self::ENG_US,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
            new Field(
                [
                    'id' => 0,
                    'value' => 'An awesome multi-lang forum²³',
                    'languageCode' => self::ENG_GB,
                    'fieldDefIdentifier' => 'name',
                    'fieldTypeIdentifier' => 'ibexa_string',
                ]
            ),
        ];
    }

    /**
     * Gets expected property values for the "Media" ContentInfo ValueObject.
     *
     * @return array
     */
    private function getExpectedMediaContentInfoProperties()
    {
        return [
            'id' => self::MEDIA_CONTENT_ID,
            'contentTypeId' => 1,
            'name' => 'Media',
            'sectionId' => 3,
            'currentVersionNo' => 1,
            'published' => true,
            'ownerId' => 14,
            'modificationDate' => $this->createDateTime(1060695457),
            'publishedDate' => $this->createDateTime(1060695457),
            'alwaysAvailable' => 1,
            'remoteId' => self::MEDIA_REMOTE_ID,
            'mainLanguageCode' => self::ENG_US,
            'mainLocationId' => 43,
            'status' => ContentInfo::STATUS_PUBLISHED,
        ];
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::hideContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testHideContent(): void
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $locationCreateStructs = array_map(
            function (Location $parentLocation) {
                return $this->locationService->newLocationCreateStruct($parentLocation->id);
            },
            $this->createParentLocationsForHideReveal(2)
        );

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'Folder to hide');

        $content = $this->contentService->createContent(
            $contentCreate,
            $locationCreateStructs
        );

        $publishedContent = $this->contentService->publishVersion($content->versionInfo);
        $locations = $this->locationService->loadLocations($publishedContent->contentInfo);

        // Sanity check
        self::assertCount(3, $locations);
        self::assertCount(0, $this->filterHiddenLocations($locations));

        $this->contentService->hideContent($publishedContent->contentInfo);

        $locations = $this->locationService->loadLocations($publishedContent->contentInfo);
        self::assertCount(3, $locations);
        self::assertCount(3, $this->filterHiddenLocations($locations));
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\ContentService::revealContent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testRevealContent()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $locationCreateStructs = array_map(
            function (Location $parentLocation) {
                return $this->locationService->newLocationCreateStruct($parentLocation->id);
            },
            $this->createParentLocationsForHideReveal(2)
        );

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'Folder to hide');

        $locationCreateStructs[0]->hidden = true;

        $content = $this->contentService->createContent(
            $contentCreate,
            $locationCreateStructs
        );

        $publishedContent = $this->contentService->publishVersion($content->versionInfo);
        $locations = $this->locationService->loadLocations($publishedContent->contentInfo);

        // Sanity check
        $hiddenLocations = $this->filterHiddenLocations($locations);
        self::assertCount(3, $locations);
        self::assertCount(1, $hiddenLocations);

        $this->contentService->hideContent($publishedContent->contentInfo);
        self::assertCount(
            3,
            $this->filterHiddenLocations(
                $this->locationService->loadLocations($publishedContent->contentInfo)
            )
        );

        $this->contentService->revealContent($publishedContent->contentInfo);

        $locations = $this->locationService->loadLocations($publishedContent->contentInfo);
        $hiddenLocationsAfterReveal = $this->filterHiddenLocations($locations);
        self::assertCount(3, $locations);
        self::assertCount(1, $hiddenLocationsAfterReveal);
        self::assertEquals($hiddenLocations, $hiddenLocationsAfterReveal);
    }

    /**
     * @depends testRevealContent
     */
    public function testRevealContentWithHiddenParent()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentNames = [
            'Parent Content',
            'Child (Nesting 1)',
            'Child (Nesting 2)',
            'Child (Nesting 3)',
            'Child (Nesting 4)',
        ];

        $parentLocation = $this->locationService->newLocationCreateStruct(
            $this->generateId('location', 2)
        );

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $contents */
        $contents = [];

        foreach ($contentNames as $contentName) {
            $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
            $contentCreate->setField('name', $contentName);

            $content = $this->contentService->createContent($contentCreate, [$parentLocation]);
            $contents[] = $publishedContent = $this->contentService->publishVersion($content->versionInfo);

            $parentLocation = $this->locationService->newLocationCreateStruct(
                $this->generateId('location', $publishedContent->contentInfo->mainLocationId)
            );
        }

        $this->contentService->hideContent($contents[0]->contentInfo);
        $this->contentService->hideContent($contents[2]->contentInfo);
        $this->contentService->revealContent($contents[2]->contentInfo);

        $parentContent = $this->contentService->loadContent($contents[0]->id);
        $parentLocation = $this->locationService->loadLocation($parentContent->contentInfo->mainLocationId);
        $parentSublocations = $this->locationService->loadLocationList([
            $contents[1]->contentInfo->mainLocationId,
            $contents[2]->contentInfo->mainLocationId,
            $contents[3]->contentInfo->mainLocationId,
            $contents[4]->contentInfo->mainLocationId,
        ]);

        // Parent remains invisible
        self::assertTrue($parentLocation->invisible);

        // All parent sublocations remain invisible as well
        foreach ($parentSublocations as $parentSublocation) {
            self::assertTrue($parentSublocation->invisible);
        }
    }

    /**
     * @depends testRevealContent
     */
    public function testRevealContentWithHiddenChildren()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentNames = [
            'Parent Content',
            'Child (Nesting 1)',
            'Child (Nesting 2)',
            'Child (Nesting 3)',
            'Child (Nesting 4)',
        ];

        $parentLocation = $this->locationService->newLocationCreateStruct(
            $this->generateId('location', 2)
        );

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $contents */
        $contents = [];

        foreach ($contentNames as $contentName) {
            $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
            $contentCreate->setField('name', $contentName);

            $content = $this->contentService->createContent($contentCreate, [$parentLocation]);
            $contents[] = $publishedContent = $this->contentService->publishVersion($content->versionInfo);

            $parentLocation = $this->locationService->newLocationCreateStruct(
                $this->generateId('location', $publishedContent->contentInfo->mainLocationId)
            );
        }

        $this->contentService->hideContent($contents[0]->contentInfo);
        $this->contentService->hideContent($contents[2]->contentInfo);
        $this->contentService->revealContent($contents[0]->contentInfo);

        $directChildContent = $this->contentService->loadContent($contents[1]->id);
        $directChildLocation = $this->locationService->loadLocation($directChildContent->contentInfo->mainLocationId);

        $childContent = $this->contentService->loadContent($contents[2]->id);
        $childLocation = $this->locationService->loadLocation($childContent->contentInfo->mainLocationId);
        $childSublocations = $this->locationService->loadLocationList([
            $contents[3]->contentInfo->mainLocationId,
            $contents[4]->contentInfo->mainLocationId,
        ]);

        // Direct child content is not hidden
        self::assertFalse($directChildContent->contentInfo->isHidden);

        // Direct child content location is still invisible
        self::assertFalse($directChildLocation->invisible);

        // Child content is still hidden
        self::assertTrue($childContent->contentInfo->isHidden);

        // Child content location is still invisible
        self::assertTrue($childLocation->invisible);

        // All childs sublocations remain invisible as well
        foreach ($childSublocations as $childSublocation) {
            self::assertTrue($childSublocation->invisible);
        }
    }

    public function testHideContentWithParentLocation()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->setField('name', 'Parent');

        $content = $this->contentService->createContent(
            $contentCreate,
            [
                $this->locationService->newLocationCreateStruct(
                    $this->generateId('location', 2)
                ),
            ]
        );

        $publishedContent = $this->contentService->publishVersion($content->versionInfo);

        $this->contentService->hideContent($publishedContent->contentInfo);

        $locations = iterator_to_array($this->locationService->loadLocations($publishedContent->contentInfo));

        $childContentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $childContentCreate->setField('name', 'Child');

        $childContent = $this->contentService->createContent(
            $childContentCreate,
            [
                $this->locationService->newLocationCreateStruct(
                    $locations[0]->id
                ),
            ]
        );

        $publishedChildContent = $this->contentService->publishVersion($childContent->versionInfo);

        $childLocations = iterator_to_array($this->locationService->loadLocations($publishedChildContent->contentInfo));

        self::assertTrue($locations[0]->hidden);
        self::assertTrue($locations[0]->invisible);

        self::assertFalse($childLocations[0]->hidden);
        self::assertTrue($childLocations[0]->invisible);
    }

    public function testChangeContentName()
    {
        $contentDraft = $this->createContentDraft(
            'folder',
            $this->generateId('location', 2),
            [
                'name' => 'Marco',
            ]
        );

        $publishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);
        $contentMetadataUpdateStruct = new ContentMetadataUpdateStruct([
            'name' => 'Polo',
        ]);
        $this->contentService->updateContentMetadata($publishedContent->contentInfo, $contentMetadataUpdateStruct);

        $updatedContent = $this->contentService->loadContent($publishedContent->id);

        self::assertEquals('Marco', $publishedContent->contentInfo->name);
        self::assertEquals('Polo', $updatedContent->contentInfo->name);
    }

    public function testCopyTranslationsFromPublishedToDraft()
    {
        $contentDraft = $this->createContentDraft(
            'folder',
            $this->generateId('location', 2),
            [
                'name' => 'Folder US',
            ]
        );

        $publishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

        $deDraft = $this->contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
            'fields' => $contentDraft->getFields(),
        ]);

        $contentUpdateStruct->setField('name', 'Folder GER', self::GER_DE);

        $deContent = $this->contentService->updateContent($deDraft->versionInfo, $contentUpdateStruct);

        $updatedContent = $this->contentService->loadContent($deContent->id, null, $deContent->versionInfo->versionNo);
        self::assertEquals(
            [
                self::ENG_US => 'Folder US',
                self::GER_DE => 'Folder GER',
            ],
            $updatedContent->fields['name']
        );

        $gbDraft = $this->contentService->createContentDraft($publishedContent->contentInfo);

        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_GB,
            'fields' => $contentDraft->getFields(),
        ]);

        $contentUpdateStruct->setField('name', 'Folder GB', self::ENG_GB);

        $gbContent = $this->contentService->updateContent($gbDraft->versionInfo, $contentUpdateStruct);
        $this->contentService->publishVersion($gbDraft->versionInfo);
        $updatedContent = $this->contentService->loadContent($gbContent->id, null, $gbContent->versionInfo->versionNo);
        self::assertEquals(
            [
                self::ENG_US => 'Folder US',
                self::ENG_GB => 'Folder GB',
            ],
            $updatedContent->fields['name']
        );

        $dePublished = $this->contentService->publishVersion($deDraft->versionInfo);
        self::assertEquals(
            [
                self::ENG_US => 'Folder US',
                self::GER_DE => 'Folder GER',
                self::ENG_GB => 'Folder GB',
            ],
            $dePublished->fields['name']
        );
    }

    public function testCopyTranslationsFromInvalidPublishedContentToDraft()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        // Create content type for testing
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct('test_copy_translation');
        $contentTypeCreateStruct->mainLanguageCode = 'eng-US';
        $contentTypeCreateStruct->names = ['eng-US' => 'Test content type for Copy Translations'];
        $fieldDefinition = $contentTypeService->newFieldDefinitionCreateStruct('name', 'ibexa_string');
        $fieldDefinition->position = 1;
        $fieldDefinition->isTranslatable = true;
        $contentTypeCreateStruct->addFieldDefinition($fieldDefinition);
        $contentTypeService->publishContentTypeDraft(
            $contentTypeService->createContentType(
                $contentTypeCreateStruct,
                [$contentTypeService->loadContentTypeGroupByIdentifier('Content')]
            )
        );
        $contentType = $contentTypeService->loadContentTypeByIdentifier('test_copy_translation');

        // Create entry content
        $contentDraft = $this->createContentDraft(
            'test_copy_translation',
            $this->generateId('location', 2),
            [
                'name' => 'Folder US',
            ]
        );
        $publishedContent = $this->contentService->publishVersion($contentDraft->versionInfo);

        // Create translation draft that would act as an OLD version
        $deDraft = $this->contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
            'fields' => $contentDraft->getFields(),
        ]);

        $contentUpdateStruct->setField('name', 'Folder GER', self::GER_DE);
        $deContent = $this->contentService->updateContent($deDraft->versionInfo, $contentUpdateStruct);

        // Update published version, as copying is only done when there is a diff between published and draft
        $gbDraft = $this->contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_US,
        ]);
        $contentUpdateStruct->setField('name', 'Folder US 2', self::ENG_US);

        $gbContent = $this->contentService->updateContent($gbDraft->versionInfo, $contentUpdateStruct);
        $this->contentService->publishVersion($gbContent->versionInfo);

        // Update content type with new required field
        $contentTypeDraft = $contentTypeService->createContentTypeDraft($contentType);
        $fieldDefinition = $contentTypeService->newFieldDefinitionCreateStruct('req_field', 'ibexa_string');
        $fieldDefinition->position = 2;
        $fieldDefinition->isRequired = true;
        $fieldDefinition->isTranslatable = true;
        $contentTypeService->addFieldDefinition($contentTypeDraft, $fieldDefinition);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        // Reload previous german draft, it is now in invalid state for both ENG_US and GER_DE
        $invalidContentDraft = $this->contentService->loadContent($deContent->id, null, $deContent->versionInfo->versionNo);
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::GER_DE,
        ]);
        $contentUpdateStruct->setField('req_field', 'Required field DE', self::GER_DE);

        $this->contentService->updateContent($invalidContentDraft->versionInfo, $contentUpdateStruct);
        $this->contentService->publishVersion($invalidContentDraft->versionInfo, [self::GER_DE]);

        $publishedContent = $this->contentService->loadContent($deContent->id, null, $deContent->versionInfo->versionNo);

        self::assertEquals(
            [
                self::GER_DE => 'Folder GER',
                self::ENG_US => 'Folder US 2',
            ],
            $publishedContent->fields['name']
        );
        // Missing values were copied from last updated draft
        self::assertEquals(
            [
                self::GER_DE => 'Required field DE',
                self::ENG_US => 'Required field DE',
            ],
            $publishedContent->fields['req_field']
        );
    }

    /**
     * Create structure of parent folders with Locations to be used for Content hide/reveal tests.
     *
     * @param int $parentLocationId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Location[] A list of Locations aimed to be parents
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createParentLocationsForHideReveal(int $parentLocationId): array
    {
        $parentFoldersLocationsIds = [
            $this->createFolder([self::ENG_US => 'P1'], $parentLocationId)->contentInfo->mainLocationId,
            $this->createFolder([self::ENG_US => 'P2'], $parentLocationId)->contentInfo->mainLocationId,
            $this->createFolder([self::ENG_US => 'P3'], $parentLocationId)->contentInfo->mainLocationId,
        ];

        return array_values([...$this->locationService->loadLocationList($parentFoldersLocationsIds)]);
    }

    /**
     * Filter Locations list by hidden only.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Location[] $locations
     *
     * @return array
     */
    private function filterHiddenLocations(array $locations): array
    {
        return array_values(
            array_filter(
                $locations,
                static function (Location $location) {
                    return $location->hidden;
                }
            )
        );
    }

    public function testPublishVersionWithSelectedLanguages()
    {
        $publishedContent = $this->createFolder(
            [
                self::ENG_US => 'Published US',
                self::GER_DE => 'Published DE',
            ],
            $this->generateId('location', 2)
        );

        $draft = $this->contentService->createContentDraft($publishedContent->contentInfo);
        $contentUpdateStruct = new ContentUpdateStruct([
            'initialLanguageCode' => self::ENG_US,
        ]);
        $contentUpdateStruct->setField('name', 'Draft 1 US', self::ENG_US);
        $contentUpdateStruct->setField('name', 'Draft 1 DE', self::GER_DE);

        $this->contentService->updateContent($draft->versionInfo, $contentUpdateStruct);
        $this->contentService->publishVersion($draft->versionInfo, ['ger-DE']);
        $content = $this->contentService->loadContent($draft->contentInfo->id);

        self::assertEquals(
            [
                self::ENG_US => 'Published US',
                self::GER_DE => 'Draft 1 DE',
            ],
            $content->fields['name']
        );
    }

    public function testCreateContentWithRomanianSpecialCharsInTitle()
    {
        $baseName = 'ȘșțȚdfdf';
        $expectedPath = '/SstTdfdf';

        $this->createFolder([self::ENG_US => $baseName], 2);

        $urlAliasService = $this->getRepository()->getURLAliasService();
        $urlAlias = $urlAliasService->lookup($expectedPath);
        self::assertSame($expectedPath, $urlAlias->path);
    }

    /**
     * @param int $amountOfDrafts
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createContentDrafts(int $amountOfDrafts): void
    {
        if (0 >= $amountOfDrafts) {
            throw new InvalidArgumentException('$amountOfDrafts', 'Must be greater then 0');
        }

        $publishedContent = $this->createContentVersion1();

        for ($i = 1; $i <= $amountOfDrafts; ++$i) {
            $this->contentService->createContentDraft($publishedContent->contentInfo);
        }
    }

    /**
     * @param array $limitationValues
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function createUserWithVersionReadLimitations(array $limitationValues = []): User
    {
        $limitations = [
            new LocationLimitation(['limitationValues' => $limitationValues]),
        ];

        return $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'versionread', 'limitations' => $limitations],
                ['module' => 'content', 'function' => 'create'],
                ['module' => 'content', 'function' => 'read'],
                ['module' => 'content', 'function' => 'edit'],
            ]
        );
    }

    /**
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content[] $drafts
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     *
     * @return object
     */
    private function createContentWithReverseRelations(array $drafts)
    {
        $contentWithReverseRelations = new class() {
            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content */
            public $content;

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content[] */
            public $reverseRelations;
        };
        $content = $this->createContentVersion1();
        $versionInfo = $content->getVersionInfo();
        $contentInfo = $versionInfo->getContentInfo();
        $contentWithReverseRelations->content = $content;

        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $draft */
        foreach ($drafts as $draft) {
            $this->contentService->addRelation(
                $draft->getVersionInfo(),
                $contentInfo
            );

            $contentWithReverseRelations->reverseRelations[] = $this->contentService->publishVersion($draft->getVersionInfo());
        }

        return $contentWithReverseRelations;
    }

    private function createContentWithRelations(): Content
    {
        $draft = $this->createContentDraftVersion1();
        $versionInfo = $draft->getVersionInfo();

        $media = $this->contentService->loadContentInfoByRemoteId(self::MEDIA_REMOTE_ID);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId(self::DEMO_DESIGN_REMOTE_ID);
        $this->contentService->addRelation(
            $versionInfo,
            $media
        );

        $this->contentService->addRelation(
            $versionInfo,
            $demoDesign
        );

        return $draft;
    }

    private function createContentWithFieldRelation(Content $targetContent): Content
    {
        $draft = $this->createContentDraft(
            'gallery',
            56,
            [
                'name' => 'Content With Single Relation',
                'image' => new RelationValue($targetContent->getId()),
            ]
        );

        return $this->contentService->publishVersion($draft->getVersionInfo());
    }

    public function testLoadContentWithinGracePeriod(): void
    {
        $repository = $this->getRepository();

        $contentTypeService = $repository->getContentTypeService();
        $contentType = $contentTypeService->loadContentTypeByIdentifier('folder');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, self::ENG_US);
        $contentCreate->modificationDate = new \DateTime('2025-04-01 14:00:00');
        $contentCreate->setField('name', 'My awesome Folder');

        ClockMock::withClockMock(strtotime('2025-04-01 14:00:01'));
        $content = $this->contentService->createContent($contentCreate);
        $unPublishedVersionOneContent = $this->contentService->publishVersion($content->getVersionInfo());

        $this->contentService->publishVersion(
            $this->updateFolder($content, [self::ENG_US => 'Updated Name'])->getVersionInfo()
        );

        $anonymousUserId = $this->generateId('user', 10);
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($anonymousUserId));

        $this->setGracePeriod(10);

        //Reset clock, to make sure that upfront operations did not exceed grace period.
        ClockMock::withClockMock(strtotime('2025-04-01 14:00:02'));
        $this->contentService->loadContent($unPublishedVersionOneContent->getId(), null, $unPublishedVersionOneContent->getVersionInfo()->versionNo);

        ClockMock::sleep(20);
        $this->expectException(CoreUnauthorizedException::class);
        $this->contentService->loadContent($unPublishedVersionOneContent->getId(), null, $unPublishedVersionOneContent->getVersionInfo()->versionNo);

        ClockMock::withClockMock(false);
    }

    private function setGracePeriod(int $value): void
    {
        $reflection = new ReflectionClass($this->contentService);
        $serviceProperty = $reflection->getProperty('service');
        $serviceProperty->setAccessible(true);

        $service = $serviceProperty->getValue($this->contentService);

        $serviceReflection = new ReflectionClass($service);
        $innerServiceProperty = $serviceReflection->getProperty('innerService');
        $innerServiceProperty->setAccessible(true);

        $innerService = $innerServiceProperty->getValue($service);

        $innerServiceReflection = new ReflectionClass($innerService);
        $settingsProperty = $innerServiceReflection->getProperty('settings');
        $settingsProperty->setAccessible(true);

        $settings = $settingsProperty->getValue($innerService);
        $settings['grace_period_in_seconds'] = $value;

        $settingsProperty->setValue($innerService, $settings);
    }
}
