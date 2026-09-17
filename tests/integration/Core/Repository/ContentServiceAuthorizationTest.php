<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\Relation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LanguageLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\LocationLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Core\Repository\ContentService;
use Ibexa\Core\Repository\Repository as CoveredRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the ContentServiceAuthorization using in memory storage.
 */
#[CoversClass(ContentService::class)]
#[CoversMethod(ContentService::class, 'createContent')]
#[CoversMethod(ContentService::class, 'loadContentInfo')]
#[CoversMethod(CoveredRepository::class, 'sudo')]
#[CoversMethod(ContentService::class, 'loadContentInfoList')]
#[CoversMethod(ContentService::class, 'loadContentInfoByRemoteId')]
#[CoversMethod(ContentService::class, 'loadVersionInfo')]
#[CoversMethod(ContentService::class, 'loadVersionInfoById')]
#[CoversMethod(ContentService::class, 'loadContentByContentInfo')]
#[CoversMethod(ContentService::class, 'loadContentByVersionInfo')]
#[CoversMethod(ContentService::class, 'loadContent')]
#[CoversMethod(ContentService::class, 'loadContentByRemoteId')]
#[CoversMethod(ContentService::class, 'updateContentMetadata')]
#[CoversMethod(ContentService::class, 'deleteContent')]
#[CoversMethod(ContentService::class, 'createContentDraft')]
#[CoversMethod(ContentService::class, 'countContentDrafts')]
#[CoversMethod(ContentService::class, 'loadContentDraftList')]
#[CoversMethod(ContentService::class, 'updateContent')]
#[CoversMethod(ContentService::class, 'publishVersion')]
#[CoversMethod(ContentService::class, 'deleteVersion')]
#[CoversMethod(ContentService::class, 'loadVersions')]
#[CoversMethod(ContentService::class, 'copyContent')]
#[CoversMethod(ContentService::class, 'loadRelationList')]
#[CoversMethod(ContentService::class, 'loadReverseRelations')]
#[CoversMethod(ContentService::class, 'addRelation')]
#[CoversMethod(ContentService::class, 'deleteRelation')]
#[Group('integration')]
#[Group('authorization')]
class ContentServiceAuthorizationTest extends BaseContentServiceTestCase
{
    /** @var \Ibexa\Contracts\Core\Repository\Values\User\User */
    private $administratorUser;

    /** @var \Ibexa\Contracts\Core\Repository\Values\User\User */
    private $anonymousUser;

    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    private $repository;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    private $userService;

    /** @var \Ibexa\Contracts\Core\Repository\ContentService */
    private $contentService;

    public function setUp(): void
    {
        parent::setUp();

        $anonymousUserId = $this->generateId('user', 10);
        $administratorUserId = $this->generateId('user', 14);

        $this->repository = $this->getRepository();
        $this->permissionResolver = $this->repository->getPermissionResolver();
        $this->userService = $this->repository->getUserService();
        $this->contentService = $this->repository->getContentService();

        $this->administratorUser = $this->userService->loadUser($administratorUserId);
        $this->anonymousUser = $this->userService->loadUser($anonymousUserId);
    }

    /**
     * Test for the createContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    public function testCreateContentThrowsUnauthorizedException()
    {
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $contentTypeService = $this->getRepository()->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier('forum');

        $contentCreate = $this->contentService->newContentCreateStruct($contentType, 'eng-US');
        $contentCreate->setField('name', 'Awesome Sindelfingen forum');

        $contentCreate->remoteId = 'abcdef0123456789abcdef0123456789';
        $contentCreate->alwaysAvailable = true;

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'create\' \'content\'/');

        $this->contentService->createContent($contentCreate);
    }

    /**
     * Test for the createContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContent')]
    public function testCreateContentThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'create\' \'content\'/');

        $this->createContentDraftVersion1();
    }

    /**
     * Test for the loadContentInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentInfo')]
    public function testLoadContentInfoThrowsUnauthorizedException()
    {
        $contentId = $this->generateId('object', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        // $contentId contains a content object ID not accessible for anonymous
        $this->contentService->loadContentInfo($contentId);
    }

    /**
     * Test for the sudo() method.
     */
    #[Depends('testLoadContentInfoThrowsUnauthorizedException')]
    public function testSudo()
    {
        $repository = $this->getRepository();
        $contentId = $this->generateId('object', 10);
        $this->setRestrictedEditorUser();

        $contentInfo = $repository->sudo(static function (Repository $repository) use ($contentId) {
            return $repository->getContentService()->loadContentInfo($contentId);
        });

        self::assertInstanceOf(
            ContentInfo::class,
            $contentInfo
        );
    }

    /**
     * Test for the loadContentInfoList() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentInfoList')]
    public function testLoadContentInfoListSkipsUnauthorizedItems()
    {
        $contentId = $this->generateId('object', 10);
        $this->setRestrictedEditorUser();

        self::assertCount(0, $this->contentService->loadContentInfoList([$contentId]));
    }

    /**
     * Test for the loadContentInfoByRemoteId() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentInfoByRemoteId')]
    public function testLoadContentInfoByRemoteIdThrowsUnauthorizedException()
    {
        $anonymousRemoteId = 'faaeb9be3bd98ed09f606fc16d144eca';

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentInfoByRemoteId($anonymousRemoteId);
    }

    /**
     * Test for the loadVersionInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersionInfo')]
    public function testLoadVersionInfoThrowsUnauthorizedException()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadVersionInfo($contentInfo);
    }

    /**
     * Test for the loadVersionInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersionInfoWithSecondParameter')]
    public function testLoadVersionInfoThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadVersionInfo($contentInfo, 2);
    }

    /**
     * Test for the loadVersionInfoById() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersionInfoById')]
    public function testLoadVersionInfoByIdThrowsUnauthorizedException()
    {
        $anonymousUserId = $this->generateId('user', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadVersionInfoById($anonymousUserId);
    }

    /**
     * Test for the loadVersionInfoById() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersionInfoByIdWithSecondParameter')]
    public function testLoadVersionInfoByIdThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $anonymousUserId = $this->generateId('user', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadVersionInfoById($anonymousUserId, 2);
    }

    /**
     * Test for the loadVersionInfoById() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersionInfoById')]
    public function testLoadVersionInfoByIdThrowsUnauthorizedExceptionForFirstDraft()
    {
        $contentDraft = $this->createContentDraftVersion1();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        // content versionread policy is needed because it is a draft
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->loadVersionInfoById(
            $contentDraft->id,
            $contentDraft->contentInfo->currentVersionNo
        );
    }

    /**
     * Test for the loadContentByContentInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByContentInfo')]
    public function testLoadContentByContentInfoThrowsUnauthorizedException()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByContentInfo($contentInfo);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByContentInfoWithLanguageParameters')]
    public function testLoadContentByContentInfoThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByContentInfo($contentInfo, ['eng-US']);
    }

    /**
     * Test for the loadContentByContentInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByContentInfoWithVersionNumberParameter')]
    public function testLoadContentByContentInfoThrowsUnauthorizedExceptionWithThirdParameter()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByContentInfo($contentInfo, ['eng-US'], 2);
    }

    /**
     * Test for the loadContentByVersionInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByVersionInfo')]
    public function testLoadContentByVersionInfoThrowsUnauthorizedException()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $versionInfo = $this->contentService->loadVersionInfo($contentInfo);

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByVersionInfo($versionInfo);
    }

    /**
     * Test for the loadContentByVersionInfo() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByVersionInfoWithSecondParameter')]
    public function testLoadContentByVersionInfoThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $contentInfo = $this->getContentInfoForAnonymousUser();

        $versionInfo = $this->contentService->loadVersionInfo($contentInfo);

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByVersionInfo($versionInfo, ['eng-US']);
    }

    /**
     * Test for the loadContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContent')]
    public function testLoadContentThrowsUnauthorizedException()
    {
        $anonymousUserId = $this->generateId('user', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContent($anonymousUserId);
    }

    /**
     * Test for the loadContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentWithPrioritizedLanguages')]
    public function testLoadContentThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $anonymousUserId = $this->generateId('user', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContent($anonymousUserId, ['eng-US']);
    }

    /**
     * Test for the loadContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentWithThirdParameter')]
    public function testLoadContentThrowsUnauthorizedExceptionWithThirdParameter()
    {
        $anonymousUserId = $this->generateId('user', 10);
        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContent($anonymousUserId, ['eng-US'], 2);
    }

    /**
     * Test for the loadContent() method on a draft.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContent')]
    public function testLoadContentThrowsUnauthorizedExceptionOnDrafts()
    {
        $editorUser = $this->createUserVersion1();

        $this->permissionResolver->setCurrentUserReference($editorUser);

        // Create draft with this user
        $draft = $this->createContentDraftVersion1(2, 'folder');

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        // Try to load the draft with anonymous user to make sure access won't be allowed by throwing an exception
        $this->expectException(UnauthorizedException::class);
        // content versionread policy is needed because it is a draft
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->loadContent($draft->id);
    }

    /**
     * Test for the ContentService::loadContent() method on an archive.
     *
     * This test the version permission on loading archived versions
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContent')]
    public function testLoadContentThrowsUnauthorizedExceptionsOnArchives()
    {
        $contentTypeService = $this->getRepository()->getContentTypeService();

        // set admin as current user
        $this->permissionResolver->setCurrentUserReference($this->administratorUser);

        // create folder
        $newStruct = $this->contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            'eng-US'
        );
        $newStruct->setField('name', 'Test Folder');
        $draft = $this->contentService->createContent(
            $newStruct,
            [$this->repository->getLocationService()->newLocationCreateStruct(2)]
        );
        $object = $this->contentService->publishVersion($draft->versionInfo);

        // update folder to make an archived version
        $updateStruct = $this->contentService->newContentUpdateStruct();
        $updateStruct->setField('name', 'Test Folder Updated');
        $draftUpdated = $this->contentService->updateContent(
            $this->contentService->createContentDraft($object->contentInfo)->versionInfo,
            $updateStruct
        );
        $objectUpdated = $this->contentService->publishVersion($draftUpdated->versionInfo);

        // set an anonymous as current user
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        // content versionread policy is needed because it is a draft
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->loadContent($objectUpdated->id, null, 1);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByRemoteId')]
    public function testLoadContentByRemoteIdThrowsUnauthorizedException()
    {
        $anonymousRemoteId = 'faaeb9be3bd98ed09f606fc16d144eca';

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByRemoteId($anonymousRemoteId);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByRemoteIdWithSecondParameter')]
    public function testLoadContentByRemoteIdThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $anonymousRemoteId = 'faaeb9be3bd98ed09f606fc16d144eca';

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByRemoteId($anonymousRemoteId, ['eng-US']);
    }

    /**
     * Test for the loadContentByRemoteId() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadContentByRemoteIdWithThirdParameter')]
    public function testLoadContentByRemoteIdThrowsUnauthorizedExceptionWithThirdParameter()
    {
        $anonymousRemoteId = 'faaeb9be3bd98ed09f606fc16d144eca';

        $this->setRestrictedEditorUser();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->loadContentByRemoteId($anonymousRemoteId, ['eng-US'], 2);
    }

    /**
     * Test for the updateContentMetadata() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testUpdateContentMetadata')]
    public function testUpdateContentMetadataThrowsUnauthorizedException()
    {
        $content = $this->createContentVersion1();

        $contentInfo = $content->contentInfo;

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $metadataUpdate = $this->contentService->newContentMetadataUpdateStruct();

        $metadataUpdate->remoteId = 'aaaabbbbccccddddeeeeffff11112222';
        $metadataUpdate->mainLanguageCode = 'eng-US';
        $metadataUpdate->alwaysAvailable = false;
        $metadataUpdate->publishedDate = $this->createDateTime();
        $metadataUpdate->modificationDate = $this->createDateTime();

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'edit\' \'content\'/');

        $this->contentService->updateContentMetadata(
            $contentInfo,
            $metadataUpdate
        );
    }

    /**
     * Test for the deleteContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testDeleteContent')]
    public function testDeleteContentThrowsUnauthorizedException()
    {
        $contentVersion2 = $this->createContentVersion2();

        $contentInfo = $contentVersion2->contentInfo;

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'remove\' \'content\'/');

        $this->contentService->deleteContent($contentInfo);
    }

    public function testDeleteContentThrowsUnauthorizedExceptionWithLanguageLimitation(): void
    {
        $contentVersion2 = $this->createMultipleLanguageContentVersion2();
        $contentInfo = $contentVersion2->contentInfo;
        $limitations = [
            new LanguageLimitation(['limitationValues' => ['eng-US']]),
        ];

        $user = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'remove', 'limitations' => $limitations],
            ]
        );

        $this->permissionResolver->setCurrentUserReference($user);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'remove\' \'content\'/');

        $this->contentService->deleteContent($contentInfo);
    }

    public function testDeleteContentWithLanguageLimitation(): void
    {
        $contentVersion2 = $this->createMultipleLanguageContentVersion2();
        $contentInfo = $contentVersion2->contentInfo;

        $limitations = [
            new LanguageLimitation(['limitationValues' => ['eng-US', 'eng-GB']]),
        ];

        $user = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'remove', 'limitations' => $limitations],
            ]
        );

        $this->permissionResolver->setCurrentUserReference($user);

        self::assertSame([$contentInfo->mainLocationId], $this->contentService->deleteContent($contentInfo));
    }

    /**
     * Test for the createContentDraft() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContentDraft')]
    public function testCreateContentDraftThrowsUnauthorizedException()
    {
        $content = $this->createContentVersion1();

        $contentInfo = $content->contentInfo;

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'edit\' \'content\'/');

        $this->contentService->createContentDraft($contentInfo);
    }

    /**
     * Test for the createContentDraft() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCreateContentDraftWithSecondParameter')]
    public function testCreateContentDraftThrowsUnauthorizedExceptionWithSecondParameter()
    {
        $content = $this->createContentVersion1();

        $contentInfo = $content->contentInfo;
        $versionInfo = $content->getVersionInfo();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'edit\' \'content\'/');

        $this->contentService->createContentDraft($contentInfo, $versionInfo);
    }

    /**
     * Test for the countContentDrafts() method.
     */
    public function testCountContentDraftsReturnZero()
    {
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        self::assertSame(0, $this->contentService->countContentDrafts());
    }

    #[DependsExternal(ContentServiceTest::class, 'testLoadContentDraftList')]
    public function testLoadContentDraftListReturnsEmptyListForUserWithoutVersionReadAccess(): void
    {
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $draftList = $this->contentService->loadContentDraftList();

        self::assertSame(0, $draftList->totalCount);
        self::assertEmpty($draftList->items);
    }

    #[DependsExternal(ContentServiceTest::class, 'testLoadContentDraftList')]
    public function testLoadContentDraftListReturnsEmptyListForUserWithoutVersionReadAccessWithUser(): void
    {
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $draftList = $this->contentService->loadContentDraftList($this->administratorUser);

        self::assertSame(0, $draftList->totalCount);
        self::assertEmpty($draftList->items);
    }

    /**
     * Test for the updateContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testUpdateContent')]
    public function testUpdateContentThrowsUnauthorizedException()
    {
        $draftVersion2 = $this->createContentDraftVersion2();

        $versionInfo = $draftVersion2->getVersionInfo();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        // Create an update struct and modify some fields
        $contentUpdate = $this->contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', 'An awesome² story about ezp.');
        $contentUpdate->setField('name', 'An awesome²³ story about ezp.', 'eng-GB');

        $contentUpdate->initialLanguageCode = 'eng-US';

        $this->expectException(UnauthorizedException::class);
        /* TODO - the `content/edit` policy should be probably needed */
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->updateContent($versionInfo, $contentUpdate);
    }

    /**
     * Test for the publishVersion() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testPublishVersion')]
    public function testPublishVersionThrowsUnauthorizedException()
    {
        $draft = $this->createContentDraftVersion1();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'publish\' \'content\'/');

        $this->contentService->publishVersion($draft->getVersionInfo());
    }

    /**
     * Test for the deleteVersion() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testDeleteVersion')]
    public function testDeleteVersionThrowsUnauthorizedException()
    {
        $draft = $this->createContentDraftVersion1();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'versionremove\' \'content\'/');

        $this->contentService->deleteVersion($draft->getVersionInfo());
    }

    /**
     * Test for the loadVersions() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadVersions')]
    public function testLoadVersionsThrowsUnauthorizedException()
    {
        $contentVersion2 = $this->createContentVersion2();

        $contentInfo = $contentVersion2->contentInfo;

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->loadVersions($contentInfo);
    }

    /**
     * Test for the copyContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCopyContent')]
    public function testCopyContentThrowsUnauthorizedException()
    {
        $parentLocationId = $this->generateId('location', 52);

        $locationService = $this->repository->getLocationService();

        $contentVersion2 = $this->createMultipleLanguageContentVersion2();

        $contentInfo = $contentVersion2->contentInfo;

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        // Configure new target location
        $targetLocationCreate = $locationService->newLocationCreateStruct($parentLocationId);

        $targetLocationCreate->priority = 42;
        $targetLocationCreate->hidden = true;
        $targetLocationCreate->remoteId = '01234abcdef5678901234abcdef56789';
        $targetLocationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $targetLocationCreate->sortOrder = Location::SORT_ORDER_DESC;

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'read\' \'content\'/');

        $this->contentService->copyContent(
            $contentInfo,
            $targetLocationCreate
        );
    }

    /**
     * Test for the copyContent() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testCopyContentWithGivenVersion')]
    public function testCopyContentThrowsUnauthorizedExceptionWithGivenVersion()
    {
        $parentLocationId = $this->generateId('location', 52);

        $contentVersion2 = $this->createContentVersion2();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        // Configure new target location
        $targetLocationCreate = $this->repository->getLocationService()->newLocationCreateStruct($parentLocationId);

        $targetLocationCreate->priority = 42;
        $targetLocationCreate->hidden = true;
        $targetLocationCreate->remoteId = '01234abcdef5678901234abcdef56789';
        $targetLocationCreate->sortField = Location::SORT_FIELD_NODE_ID;
        $targetLocationCreate->sortOrder = Location::SORT_ORDER_DESC;

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->copyContent(
            $contentVersion2->contentInfo,
            $targetLocationCreate,
            $this->contentService->loadVersionInfo($contentVersion2->contentInfo, 1)
        );
    }

    /**
     * Test for the loadRelations() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadRelationList')]
    public function testLoadRelationsReturnsEmptyListForUserWithoutReadAccess(): void
    {
        $mediaEditor = $this->createMediaUserVersion1();

        $setupRemoteId = '241d538ce310074e602f29f49e44e938';

        $versionInfo = $this->contentService->loadVersionInfo(
            $this->contentService->loadContentInfoByRemoteId(
                $setupRemoteId
            )
        );

        $this->permissionResolver->setCurrentUserReference($mediaEditor);

        $relationList = $this->contentService->loadRelationList($versionInfo);

        self::assertSame(0, $relationList->totalCount);
        self::assertEmpty($relationList->items);
    }

    /**
     * Test for the loadRelations() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadRelationList')]
    public function testLoadRelationsForDraftVersionReturnsEmptyListForUserWithoutVersionReadAccess(): void
    {
        $draft = $this->createContentDraftVersion1();

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $relationList = $this->contentService->loadRelationList($draft->versionInfo);

        self::assertSame(0, $relationList->totalCount);
        self::assertEmpty($relationList->items);
    }

    /**
     * Test for the loadReverseRelations() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testLoadReverseRelations')]
    public function testLoadReverseRelationsThrowsUnauthorizedException()
    {
        $mediaEditor = $this->createMediaUserVersion1();

        $mediaRemoteId = 'a6e35cbcb7cd6ae4b691f3eee30cd262';

        $contentInfo = $this->contentService->loadContentInfoByRemoteId($mediaRemoteId);

        $this->permissionResolver->setCurrentUserReference($mediaEditor);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'reverserelatedlist\' \'content\'/');

        $this->contentService->loadReverseRelations($contentInfo);
    }

    /**
     * Test for the addRelation() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testAddRelation')]
    public function testAddRelationThrowsUnauthorizedException()
    {
        $mediaRemoteId = 'a6e35cbcb7cd6ae4b691f3eee30cd262';

        $draft = $this->createContentDraftVersion1();

        $versionInfo = $draft->getVersionInfo();

        $media = $this->contentService->loadContentInfoByRemoteId($mediaRemoteId);

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->addRelation(
            $versionInfo,
            $media
        );
    }

    /**
     * Test for the deleteRelation() method.
     */
    #[DependsExternal(ContentServiceTest::class, 'testDeleteRelation')]
    public function testDeleteRelationThrowsUnauthorizedException()
    {
        $mediaRemoteId = 'a6e35cbcb7cd6ae4b691f3eee30cd262';
        $demoDesignRemoteId = '8b8b22fe3c6061ed500fbd2b377b885f';

        $draft = $this->createContentDraftVersion1();

        $versionInfo = $draft->getVersionInfo();

        $media = $this->contentService->loadContentInfoByRemoteId($mediaRemoteId);
        $demoDesign = $this->contentService->loadContentInfoByRemoteId($demoDesignRemoteId);

        // Establish some relations
        $this->contentService->addRelation($draft->getVersionInfo(), $media);
        $this->contentService->addRelation($draft->getVersionInfo(), $demoDesign);

        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessageMatches('/\'versionread\' \'content\'/');

        $this->contentService->deleteRelation($versionInfo, $media);
    }

    /**
     * Creates a pseudo editor with a limitation to objects in the "Media/Images"
     * subtree.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    private function createAnonymousWithEditorRole()
    {
        $roleService = $this->repository->getRoleService();

        $user = $this->anonymousUser;
        $role = $roleService->loadRoleByIdentifier('Editor');

        // Assign "Editor" role with limitation to "Media/Images"
        $roleService->assignRoleToUser(
            $role,
            $user,
            new SubtreeLimitation(
                [
                    'limitationValues' => ['/1/43/51/'],
                ]
            )
        );

        return $this->userService->loadUser($user->id);
    }

    /**
     * Test that for a user that doesn't have access (read permissions) to a
     * related object, executing loadRelationList() would not throw any exception, and
     * would instead expose the non-readable related object(s) as unauthorized list items.
     */
    #[DependsExternal(ContentServiceTest::class, 'testAddRelation')]
    public function testLoadRelationsWithUnauthorizedRelations()
    {
        $mainLanguage = 'eng-GB';

        $contentTypeService = $this->repository->getContentTypeService();
        $locationService = $this->repository->getLocationService();
        $sectionService = $this->repository->getSectionService();

        // set the current user as admin to create the environment to test
        $this->permissionResolver->setCurrentUserReference($this->administratorUser);

        // create section
        // since anonymous users have their read permissions to specific sections
        // the created section will be non-readable to them
        $sectionCreate = $sectionService->newSectionCreateStruct();
        $sectionCreate->identifier = 'private';
        $sectionCreate->name = 'Private Section';
        $section = $sectionService->createSection($sectionCreate);

        // create objects for testing
        // here we will create 4 objects which 2 will be readable by an anonymous
        // user, and the other 2 wont these last 2 will go to a private section
        // where anonymous can't read, just like:
        // readable object 1 -> /Main Folder
        // readable object 2 -> /Main Folder/Available Folder
        // non-readable object 1 -> /Restricted Folder
        // non-readable object 2 -> /Restricted Folder/Unavailable Folder
        //
        // here is created - readable object 1 -> /Main Folder
        $mainFolder = $this->createFolder([$mainLanguage => 'Main Folder'], 2);

        // here is created readable object 2 -> /Main Folder/Available Folder
        $availableFolder = $this->createFolder(
            [$mainLanguage => 'Avaliable Folder'],
            $mainFolder->contentInfo->mainLocationId
        );

        // here is created the non-readable object 1 -> /Restricted Folder
        $restrictedFolderCreate = $this->contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            $mainLanguage
        );
        $restrictedFolderCreate->setField('name', 'Restricted Folder');
        $restrictedFolderCreate->sectionId = $section->id;
        $restrictedFolder = $this->contentService->publishVersion(
            $this->contentService->createContent(
                $restrictedFolderCreate,
                [$locationService->newLocationCreateStruct(2)]
            )->versionInfo
        );

        // here is created non-readable object 2 -> /Restricted Folder/Unavailable Folder
        $unavailableFolder = $this->createFolder(
            [$mainLanguage => 'Unavailable Folder'],
            $restrictedFolder->contentInfo->mainLocationId
        );

        // this will be our test object, which will have all the relations (as source)
        // and it is readable by the anonymous user
        $testFolderCreate = $this->contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            $mainLanguage
        );
        $testFolderCreate->setField('name', 'Test Folder');
        $testFolderDraft = $this->contentService->createContent(
            $testFolderCreate,
            [$locationService->newLocationCreateStruct(2)]
        )->versionInfo;

        // add relations to test folder (as source)
        // the first 2 will be read by the user
        // and the other 2 wont
        //
        // create relation from Test Folder to Main Folder
        $mainRelation = $this->contentService->addRelation(
            $testFolderDraft,
            $mainFolder->getVersionInfo()->getContentInfo()
        );
        // create relation from Test Folder to Available Folder
        $availableRelation = $this->contentService->addRelation(
            $testFolderDraft,
            $availableFolder->getVersionInfo()->getContentInfo()
        );
        // create relation from Test Folder to Restricted Folder
        $this->contentService->addRelation(
            $testFolderDraft,
            $restrictedFolder->getVersionInfo()->getContentInfo()
        );
        //create relation from Test Folder to Unavailable Folder
        $this->contentService->addRelation(
            $testFolderDraft,
            $unavailableFolder->getVersionInfo()->getContentInfo()
        );

        // publish Test Folder
        $testFolder = $this->contentService->publishVersion($testFolderDraft);

        // set the current user to be an anonymous user since we want to test that
        // if the user doesn't have access to an related object that object wont
        // be loaded and no exception will be thrown
        $this->permissionResolver->setCurrentUserReference($this->anonymousUser);

        // finaly load relations ( verify no exception is thrown )
        $actualRelations = $this->contentService->loadRelationList($testFolder->getVersionInfo());

        // assert results
        // one item per relation, non-readable targets come back as UnauthorizedRelationListItem
        self::assertCount(
            4,
            $actualRelations->items,
            'Expected one list item per relation, including the unauthorized ones'
        );

        // verify that the only readable relations are from the 2 readable objects
        // Main Folder and Available Folder
        $expectedRelations = [
            $mainRelation->destinationContentInfo->id => $mainRelation,
            $availableRelation->destinationContentInfo->id => $availableRelation,
        ];

        $unauthorizedItemsCount = 0;

        // assert each relation
        /**
         * @var \Ibexa\Contracts\Core\Repository\Values\Content\RelationList\RelationListItemInterface $relationListItem
         */
        foreach ($actualRelations as $relationListItem) {
            if (!$relationListItem->hasRelation()) {
                // non-readable target
                ++$unauthorizedItemsCount;
                continue;
            }

            /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Relation $relation */
            $relation = $relationListItem->getRelation();
            $destination = $relation->destinationContentInfo;
            self::assertArrayHasKey(
                $destination->id,
                $expectedRelations,
                "Non expected relation with '{$destination->id}' id found"
            );
            $expected = $expectedRelations[$destination->id]->destinationContentInfo;
            self::assertEquals(
                $expected->id,
                $destination->id,
                "Expected relation with '{$expected->id}' id found '{$destination->id}' id"
            );
            self::assertEquals(
                $expected->name,
                $destination->name,
                "Expected relation with '{$expected->name}' name found '{$destination->name}' name"
            );

            // remove from list
            unset($expectedRelations[$destination->id]);
        }

        // verify all expected (readable) relations were found
        self::assertCount(
            0,
            $expectedRelations,
            "Expected to find '" . (count($expectedRelations) + count($actualRelations->items))
            . "' relations found '" . count($actualRelations->items) . "'"
        );

        // verify the 2 non-readable relations came back as unauthorized placeholders
        self::assertSame(
            2,
            $unauthorizedItemsCount,
            'Expected the 2 relations towards non-readable content to be reported as unauthorized'
        );
    }

    /**
     * Test copying Content to the authorized Location (limited by policies).
     */
    public function testCopyContentToAuthorizedLocation()
    {
        $locationService = $this->repository->getLocationService();
        $roleService = $this->repository->getRoleService();

        // Create and publish folders for the test case
        $folderDraft = $this->createContentDraft('folder', 2, ['name' => 'Folder1']);
        $this->contentService->publishVersion($folderDraft->versionInfo);
        $authorizedFolderDraft = $this->createContentDraft('folder', 2, ['name' => 'AuthorizedFolder']);
        $authorizedFolder = $this->contentService->publishVersion($authorizedFolderDraft->versionInfo);

        // Prepare Role for the test case
        $roleIdentifier = 'authorized_folder';
        $roleCreateStruct = $roleService->newRoleCreateStruct($roleIdentifier);
        $locationLimitation = new LocationLimitation(
            ['limitationValues' => [$authorizedFolder->contentInfo->mainLocationId]]
        );
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('content', 'read'));
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('content', 'versionread'));
        $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('content', 'manage_locations'));

        $policyCreateStruct = $roleService->newPolicyCreateStruct('content', 'create');
        $policyCreateStruct->addLimitation($locationLimitation);
        $roleCreateStruct->addPolicy($policyCreateStruct);

        $roleDraft = $roleService->createRole($roleCreateStruct);
        $roleService->publishRoleDraft($roleDraft);

        // Create a user with that Role
        $user = $this->createCustomUserVersion1('Users', $roleIdentifier);
        $this->permissionResolver->setCurrentUserReference($user);

        // Test copying Content to the authorized Location
        $this->contentService->copyContent(
            $authorizedFolder->contentInfo,
            $locationService->newLocationCreateStruct(
                $authorizedFolder->contentInfo->mainLocationId
            )
        );
    }

    /**
     * Test copying Content to the authorized Location (limited by policies).
     */
    public function testCopyContentToAuthorizedLocationWithSubtreeLimitation()
    {
        $locationService = $this->repository->getLocationService();

        // Create and publish folders for the test case
        $folderDraft = $this->createContentDraft('folder', 2, ['name' => 'Folder1']);
        $this->contentService->publishVersion($folderDraft->versionInfo);
        $authorizedFolderDraft = $this->createContentDraft('folder', 2, ['name' => 'AuthorizedFolder']);
        $authorizedFolder = $this->contentService->publishVersion($authorizedFolderDraft->versionInfo);

        // Prepare Role for the test case
        $roleIdentifier = 'authorized_subree';
        $subtreeLimitation = new SubtreeLimitation(
            ['limitationValues' => ['/1/2']]
        );
        $policiesData = [
            [
                'module' => 'content',
                'function' => 'read',
                'limitations' => [$subtreeLimitation],
            ],
            [
                'module' => 'content',
                'function' => 'versionread',
                'limitations' => [$subtreeLimitation],
            ],
            [
                'module' => 'content',
                'function' => 'create',
                'limitations' => [$subtreeLimitation],
            ],
            [
                'module' => 'content',
                'function' => 'manage_locations',
            ],
        ];

        $this->createRoleWithPolicies($roleIdentifier, $policiesData);

        // Create a user with that Role
        $user = $this->createCustomUserVersion1('Users', $roleIdentifier);
        $this->permissionResolver->setCurrentUserReference($user);

        // Test copying Content to the authorized Location
        $this->contentService->copyContent(
            $authorizedFolder->contentInfo,
            $locationService->newLocationCreateStruct(
                $authorizedFolder->contentInfo->mainLocationId
            )
        );
    }

    /**
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    private function getContentInfoForAnonymousUser(): ContentInfo
    {
        $anonymousUserId = $this->generateId('user', 10);

        return $this->contentService->loadContentInfo($anonymousUserId);
    }

    private function setRestrictedEditorUser(): void
    {
        $this->permissionResolver->setCurrentUserReference($this->createAnonymousWithEditorRole());
    }
}
