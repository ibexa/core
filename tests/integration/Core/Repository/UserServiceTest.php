<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\ParameterType;
use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\BadStateException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Values\Content\ContentInfo;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\Content\VersionInfo as APIVersionInfo;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordInfo;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordValidationContext;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;
use Ibexa\Core\FieldType\User\Type;
use Ibexa\Core\FieldType\ValidationError;
use Ibexa\Core\Persistence\Legacy\User\Gateway;
use Ibexa\Core\Repository\Values\Content\Content;
use Ibexa\Core\Repository\Values\Content\VersionInfo;
use Ibexa\Core\Repository\Values\User\UserGroup;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * Test case for operations in the UserService using in memory storage.
 *
 * @covers \Ibexa\Contracts\Core\Repository\UserService
 *
 * @group integration
 * @group user
 */
class UserServiceTest extends BaseTestCase
{
    // Example password matching default rules
    private const EXAMPLE_PASSWORD = 'P@ssword123!';

    private const EXAMPLE_PASSWORD_TTL = 30;
    private const EXAMPLE_PASSWORD_TTL_WARNING = 14;

    /**
     * Test for the loadUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup()
     */
    public function testLoadUserGroup()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        $userGroup = $userService->loadUserGroup($mainGroupId);
        /* END: Use Case */

        self::assertInstanceOf(UserGroup::class, $userGroup);

        // User group happens to also be a Content; isUserGroup() should be true and isUser() should be false
        self::assertTrue($userService->isUserGroup($userGroup), 'isUserGroup() => false on a user group');
        self::assertFalse($userService->isUser($userGroup), 'isUser() => true on a user group');
        self::assertSame(0, $userGroup->parentId, 'parentId should be equal `0` because it is top level node');
    }

    /**
     * Test for the loadUserGroupByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroupByRemoteId()
     */
    public function testLoadUserGroupByRemoteId(): void
    {
        $existingRemoteId = 'f5c88a2209584891056f987fd965b0ba';

        $userService = $this->getRepository()->getUserService();
        $userGroup = $userService->loadUserGroupByRemoteId($existingRemoteId);

        self::assertInstanceOf(UserGroup::class, $userGroup);
        self::assertEquals($existingRemoteId, $userGroup->contentInfo->remoteId);
        // User group happens to also be a Content; isUserGroup() should be true and isUser() should be false
        self::assertTrue($userService->isUserGroup($userGroup), 'isUserGroup() => false on a user group');
        self::assertFalse($userService->isUser($userGroup), 'isUser() => true on a user group');
    }

    /**
     * Test for the loadUserGroup() method to ensure that DomainUserGroupObject is created properly even if a user
     * has no access to parent of UserGroup.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup()
     */
    public function testLoadUserGroupWithNoAccessToParent()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        $user = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'read'],
            ],
            new SubtreeLimitation(['limitationValues' => ['/1/5']])
        );
        $repository->getPermissionResolver()->setCurrentUserReference($user);

        $userGroup = $userService->loadUserGroup($mainGroupId);
        /* END: Use Case */

        self::assertInstanceOf(UserGroup::class, $userGroup);

        // User group happens to also be a Content; isUserGroup() should be true and isUser() should be false
        self::assertTrue($userService->isUserGroup($userGroup), 'isUserGroup() => false on a user group');
        self::assertFalse($userService->isUser($userGroup), 'isUser() => true on a user group');
        self::assertSame(0, $userGroup->parentId, 'parentId should be equal `0` because it is top level node');
    }

    /**
     * Test for the loadUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup()
     *
     * @depends testLoadUserGroup
     */
    public function testLoadUserGroupThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistingGroupId = $this->generateId('group', self::DB_INT_MAX);
        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        // This call will fail with a NotFoundException
        $userService->loadUserGroup($nonExistingGroupId);
        /* END: Use Case */
    }

    /**
     * Test for the loadUserGroupByRemoteId() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroupByRemoteId()
     *
     * @depends Ibexa\Tests\Integration\Core\Repository\UserServiceTest::testLoadUserGroupByRemoteId
     */
    public function testLoadUserGroupByRemoteIdThrowsNotFoundException(): void
    {
        $this->expectException(NotFoundException::class);

        $nonExistingGroupRemoteId = 'non-existing';

        $userService = $this->getRepository()->getUserService();
        $userService->loadUserGroupByRemoteId($nonExistingGroupRemoteId);
    }

    /**
     * Test for the loadSubUserGroups() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadSubUserGroups()
     *
     * @depends testLoadUserGroup
     */
    public function testLoadSubUserGroups()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        $userGroup = $userService->loadUserGroup($mainGroupId);

        $subUserGroups = $userService->loadSubUserGroups($userGroup);
        foreach ($subUserGroups as $subUserGroup) {
            // Do something with the $subUserGroup
            self::assertInstanceOf(UserGroup::class, $subUserGroup);
        }
        /* END: Use Case */
    }

    /**
     * Test loading sub groups throwing NotFoundException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadSubUserGroups
     */
    public function testLoadSubUserGroupsThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $parentGroup = new UserGroup(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            [
                                'contentInfo' => new ContentInfo(
                                    ['id' => 123456]
                                ),
                            ]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
        $userService->loadSubUserGroups($parentGroup);
    }

    /**
     * Test for the newUserGroupCreateStruct() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserGroupCreateStruct()
     */
    public function testNewUserGroupCreateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        $groupCreate = $userService->newUserGroupCreateStruct('eng-US');
        /* END: Use Case */

        self::assertInstanceOf(
            UserGroupCreateStruct::class,
            $groupCreate
        );

        return $groupCreate;
    }

    /**
     * Test for the newUserGroupCreateStruct() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct $groupCreate
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserGroupCreateStruct()
     *
     * @depends testNewUserGroupCreateStruct
     */
    public function testNewUserGroupCreateStructSetsMainLanguageCode($groupCreate)
    {
        self::assertEquals('eng-US', $groupCreate->mainLanguageCode);
    }

    /**
     * Test for the newUserGroupCreateStruct() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct $groupCreate
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserGroupCreateStruct()
     *
     * @depends testNewUserGroupCreateStruct
     */
    public function testNewUserGroupCreateStructSetsContentType($groupCreate)
    {
        self::assertInstanceOf(
            ContentType::class,
            $groupCreate->contentType
        );
    }

    /**
     * Test for the newUserGroupCreateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserGroupCreateStruct($mainLanguageCode, $contentType)
     *
     * @depends testNewUserGroupCreateStruct
     */
    public function testNewUserGroupCreateStructWithSecondParameter()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $contentTypeService = $repository->getContentTypeService();
        $userService = $repository->getUserService();

        // Load the default ContentType for user groups
        $groupType = $contentTypeService->loadContentTypeByIdentifier('user_group');

        // Instantiate a new group create struct
        $groupCreate = $userService->newUserGroupCreateStruct(
            'eng-US',
            $groupType
        );
        /* END: Use Case */

        self::assertSame($groupType, $groupCreate->contentType);
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup()
     *
     * @depends testNewUserGroupCreateStruct
     * @depends testLoadUserGroup
     */
    public function testCreateUserGroup()
    {
        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();
        /* END: Use Case */

        self::assertInstanceOf(
            UserGroup::class,
            $userGroup
        );

        $versionInfo = $userGroup->getVersionInfo();

        self::assertEquals(APIVersionInfo::STATUS_PUBLISHED, $versionInfo->status);
        self::assertEquals(1, $versionInfo->versionNo);

        return $userGroup;
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup()
     *
     * @depends testCreateUserGroup
     */
    public function testCreateUserGroupSetsExpectedProperties($userGroup)
    {
        self::assertEquals(
            [
                'parentId' => $this->generateId('group', 4),
            ],
            [
                'parentId' => $userGroup->parentId,
            ]
        );
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup()
     *
     * @depends testCreateUserGroup
     */
    public function testCreateUserGroupThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreate->setField('name', 'Example Group');
        $userGroupCreate->remoteId = '5f7f0bdb3381d6a461d8c29ff53d908f';

        // This call will fail with an "InvalidArgumentException", because the
        // specified remoteId is already used for the "Members" user group.
        $userService->createUserGroup(
            $userGroupCreate,
            $parentUserGroup
        );
        /* END: Use Case */
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup()
     *
     * @depends testCreateUserGroup
     */
    public function testCreateUserGroupThrowsInvalidArgumentExceptionFieldTypeNotAccept()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreate->setField('name', new \stdClass());

        // This call will fail with an "InvalidArgumentException", because the
        // specified remoteId is already used for the "Members" user group.
        $userService->createUserGroup(
            $userGroupCreate,
            $parentUserGroup
        );
        /* END: Use Case */
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup()
     *
     * @depends testCreateUserGroup
     */
    public function testCreateUserGroupWhenMissingField()
    {
        $this->expectException(ContentFieldValidationException::class);

        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');

        // This call will fail with a "ContentFieldValidationException", because the
        // only mandatory field "name" is not set.
        $userService->createUserGroup($userGroupCreate, $parentUserGroup);
        /* END: Use Case */
    }

    /**
     * Test for the createUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUserGroup
     *
     * @depends testNewUserGroupCreateStruct
     * @depends testLoadUserGroup
     */
    public function testCreateUserGroupInTransactionWithRollback(): void
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Use Case */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        $repository->beginTransaction();

        try {
            // Load main group
            $parentUserGroup = $userService->loadUserGroup($mainGroupId);

            // Instantiate a new create struct
            $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
            $userGroupCreate->setField('name', 'Example Group');

            // Create the new user group
            $createdUserGroupId = $userService->createUserGroup(
                $userGroupCreate,
                $parentUserGroup
            )->id;
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $repository->rollback();

        try {
            // Throws exception since creation of user group was rolled back
            $loadedGroup = $userService->loadUserGroup($createdUserGroupId);
        } catch (NotFoundException $e) {
            return;
        }
        /* END: Use Case */

        self::fail('User group object still exists after rollback.');
    }

    /**
     * Test for the deleteUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::deleteUserGroup()
     *
     * @depends testCreateUserGroup
     */
    public function testDeleteUserGroup()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Delete the currently created user group again
        $userService->deleteUserGroup($userGroup);
        /* END: Use Case */

        // We use the NotFoundException here for verification
        $userService->loadUserGroup($userGroup->id);
    }

    /**
     * Test deleting user group throwing NotFoundException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::deleteUserGroup
     */
    public function testDeleteUserGroupThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $userGroup = new UserGroup(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            ['contentInfo' => new ContentInfo(['id' => 123456])]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
        $userService->deleteUserGroup($userGroup);
    }

    /**
     * Test for the moveUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::moveUserGroup()
     *
     * @depends testCreateUserGroup
     * @depends testLoadSubUserGroups
     */
    public function testMoveUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $membersGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $membersGroupId is the ID of the "Members" user group in an Ibexa
        // Publish demo installation

        $userGroup = $this->createUserGroupVersion1();

        // Load the new parent group
        $membersUserGroup = $userService->loadUserGroup($membersGroupId);

        // Move user group from "Users" to "Members"
        $userService->moveUserGroup($userGroup, $membersUserGroup);

        // Reload the user group to get an updated $parentId
        $userGroup = $userService->loadUserGroup($userGroup->id);

        $this->refreshSearch($repository);

        // The returned array will no contain $userGroup
        $subUserGroups = $userService->loadSubUserGroups(
            $membersUserGroup
        );
        /* END: Use Case */

        $subUserGroupIds = array_map(
            static function ($content) {
                return $content->id;
            },
            $subUserGroups
        );

        self::assertEquals($membersGroupId, $userGroup->parentId);
        self::assertEquals([$userGroup->id], $subUserGroupIds);
    }

    /**
     * Test moving a user group below another group throws NotFoundException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::moveUserGroup
     */
    public function testMoveUserGroupThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $userGroupToMove = new UserGroup(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            ['contentInfo' => new ContentInfo(['id' => 123456])]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
        $parentUserGroup = new UserGroup(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            ['contentInfo' => new ContentInfo(['id' => 123455])]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
        $userService->moveUserGroup($userGroupToMove, $parentUserGroup);
    }

    /**
     * Test for the newUserGroupUpdateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserGroupUpdateStruct
     */
    public function testNewUserGroupUpdateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        $groupUpdate = $userService->newUserGroupUpdateStruct();
        /* END: Use Case */

        self::assertInstanceOf(
            UserGroupUpdateStruct::class,
            $groupUpdate
        );

        self::assertNull($groupUpdate->contentUpdateStruct);
        self::assertNull($groupUpdate->contentMetadataUpdateStruct);
    }

    /**
     * Test for the updateUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserGroup()
     *
     * @depends testCreateUserGroup
     * @depends testNewUserGroupUpdateStruct
     */
    public function testUpdateUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Create a group update struct and change nothing
        $groupUpdate = $userService->newUserGroupUpdateStruct();

        // This update will do nothing
        $userGroup = $userService->updateUserGroup(
            $userGroup,
            $groupUpdate
        );
        /* END: Use Case */

        self::assertInstanceOf(
            UserGroup::class,
            $userGroup
        );

        self::assertEquals(1, $userGroup->getVersionInfo()->versionNo);
    }

    /**
     * Test for the updateUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserGroup()
     *
     * @depends testUpdateUserGroup
     */
    public function testUpdateUserGroupWithSubContentUpdateStruct()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the content service
        $contentService = $repository->getContentService();

        // Create a content update struct and update the group name
        $contentUpdate = $contentService->newContentUpdateStruct();
        $contentUpdate->setField('name', 'Sindelfingen', 'eng-US');

        // Create a group update struct and set content update struct
        $groupUpdate = $userService->newUserGroupUpdateStruct();
        $groupUpdate->contentUpdateStruct = $contentUpdate;

        // This will update the name and the increment the group version number
        $userGroup = $userService->updateUserGroup(
            $userGroup,
            $groupUpdate
        );
        /* END: Use Case */

        self::assertEquals('Sindelfingen', $userGroup->getFieldValue('name', 'eng-US'));

        $versionInfo = $userGroup->getVersionInfo();

        self::assertEquals(APIVersionInfo::STATUS_PUBLISHED, $versionInfo->status);
        self::assertEquals(2, $versionInfo->versionNo);
    }

    /**
     * Test for the updateUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserGroup()
     *
     * @depends testUpdateUserGroup
     */
    public function testUpdateUserGroupWithSubContentMetadataUpdateStruct()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the content service
        $contentService = $repository->getContentService();

        // Create a metadata update struct and change the remoteId
        $metadataUpdate = $contentService->newContentMetadataUpdateStruct();
        $metadataUpdate->remoteId = '3c61299780663bafa3af2101e52125da';

        // Create a group update struct and set content update struct
        $groupUpdate = $userService->newUserGroupUpdateStruct();
        $groupUpdate->contentMetadataUpdateStruct = $metadataUpdate;

        // This will update the name and the increment the group version number
        $userGroup = $userService->updateUserGroup(
            $userGroup,
            $groupUpdate
        );
        /* END: Use Case */

        self::assertEquals(
            '3c61299780663bafa3af2101e52125da',
            $userGroup->contentInfo->remoteId
        );

        $versionInfo = $userGroup->getVersionInfo();

        self::assertEquals(APIVersionInfo::STATUS_PUBLISHED, $versionInfo->status);
        self::assertEquals(1, $versionInfo->versionNo);
    }

    /**
     * Test for the updateUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserGroup()
     *
     * @depends testUpdateUserGroup
     */
    public function testUpdateUserGroupThrowsInvalidArgumentExceptionOnFieldTypeNotAccept()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $userGroup = $this->createUserGroupVersion1();

        // Load the content service
        $contentService = $repository->getContentService();

        // Create a content update struct and update the group name
        $contentUpdate = $contentService->newContentUpdateStruct();
        // An object of stdClass is not accepted as a value by the field "name"
        $contentUpdate->setField('name', new \stdClass(), 'eng-US');

        // Create a group update struct and set content update struct
        $groupUpdate = $userService->newUserGroupUpdateStruct();
        $groupUpdate->contentUpdateStruct = $contentUpdate;

        // This call will fail with an InvalidArgumentException, because the
        // field "name" does not accept the given value
        $userService->updateUserGroup($userGroup, $groupUpdate);
        /* END: Use Case */
    }

    /**
     * Test for the newUserCreateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserCreateStruct()
     */
    public function testNewUserCreateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        $userCreate = $userService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US'
        );
        /* END: Use Case */

        self::assertInstanceOf(
            UserCreateStruct::class,
            $userCreate
        );

        return $userCreate;
    }

    /**
     * Test updating a user group throws ContentFieldValidationException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserGroup
     */
    public function testUpdateUserGroupThrowsContentFieldValidationExceptionOnRequiredFieldEmpty()
    {
        $this->expectException(ContentFieldValidationException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();

        $userGroup = $userService->loadUserGroup(42);
        $userGroupUpdateStruct = $userService->newUserGroupUpdateStruct();
        $userGroupUpdateStruct->contentUpdateStruct = $contentService->newContentUpdateStruct();
        $userGroupUpdateStruct->contentUpdateStruct->setField('name', '', 'eng-US');

        $userService->updateUserGroup($userGroup, $userGroupUpdateStruct);
    }

    /**
     * Test for the newUserCreateStruct() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct $userCreate
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserCreateStruct()
     *
     * @depends testNewUserCreateStruct
     */
    public function testNewUserCreateStructSetsExpectedProperties($userCreate)
    {
        self::assertEquals(
            [
                'login' => 'user',
                'email' => 'user@example.com',
                'password' => 'secret',
                'mainLanguageCode' => 'eng-US',
            ],
            [
                'login' => $userCreate->login,
                'email' => $userCreate->email,
                'password' => $userCreate->password,
                'mainLanguageCode' => $userCreate->mainLanguageCode,
            ]
        );
    }

    /**
     * Test for the newUserCreateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserCreateStruct($login, $email, $password, $mainLanguageCode, $contentType)
     *
     * @depends testNewUserCreateStruct
     */
    public function testNewUserCreateStructWithFifthParameter()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $contentTypeService = $repository->getContentTypeService();
        $userService = $repository->getUserService();

        $userType = $contentTypeService->loadContentTypeByIdentifier('user');

        $userCreate = $userService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US',
            $userType
        );
        /* END: Use Case */

        self::assertSame($userType, $userCreate->contentType);
    }

    public function testNewUserWithDomainName(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $createdUser = $this->createUserVersion1(
            'ibexa-user-Domain\username-by-login',
            'username-by-login@ibexa-user-Domain.com'
        );
        $loadedUser = $userService->loadUserByLogin('ibexa-user-Domain\username-by-login', Language::ALL);

        $this->assertIsSameUser($createdUser, $loadedUser);
    }

    /**
     * Test for the createUser() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser()
     *
     * @depends testLoadUserGroup
     * @depends testNewUserCreateStruct
     */
    public function testCreateUser()
    {
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        /* END: Use Case */

        self::assertInstanceOf(
            User::class,
            $user
        );

        return $user;
    }

    /**
     * Test for the createUser() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser()
     *
     * @depends testCreateUser
     */
    public function testCreateUserSetsExpectedProperties(User $user)
    {
        self::assertEquals(
            [
                'login' => 'user',
                'email' => 'user@example.com',
                'mainLanguageCode' => 'eng-US',
            ],
            [
                'login' => $user->login,
                'email' => $user->email,
                'mainLanguageCode' => $user->contentInfo->mainLanguageCode,
            ]
        );
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser()
     *
     * @depends testCreateUser
     */
    public function testCreateUserWhenMissingField()
    {
        $this->expectException(ContentFieldValidationException::class);

        $repository = $this->getRepository();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US'
        );

        // Do not set the mandatory fields "first_name" and "last_name"
        //$userCreate->setField( 'first_name', 'Example' );
        //$userCreate->setField( 'last_name', 'User' );

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        // This call will fail with a "ContentFieldValidationException", because the
        // mandatory fields "first_name" and "last_name" are not set.
        $userService->createUser($userCreate, [$group]);
        /* END: Use Case */
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser()
     *
     * @depends testCreateUser
     */
    public function testCreateUserThrowsInvalidArgumentExceptionOnFieldTypeNotAccept()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US'
        );

        // An object of stdClass is not a valid value for the field first_name
        $userCreate->setField('first_name', new \stdClass());
        $userCreate->setField('last_name', 'User');

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        // This call will fail with an "InvalidArgumentException", because the
        // value for the firled "first_name" is not accepted by the field type.
        $userService->createUser($userCreate, [$group]);
        /* END: Use Case */
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     *
     * @depends testCreateUser
     */
    public function testCreateUserThrowsInvalidArgumentException()
    {
        $repository = $this->getRepository();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            // admin is an existing login
            'admin',
            'user@example.com',
            'secret',
            'eng-US'
        );

        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', 'User');

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        try {
            // This call will fail with a "InvalidArgumentException", because the
            // user with "admin" login already exists.
            $userService->createUser($userCreate, [$group]);
            /* END: Use Case */
        } catch (ContentFieldValidationException $e) {
            // Exception is caught, as there is no other way to check exception properties.
            $this->assertValidationErrorOccurs($e, 'The user login \'admin\' is used by another user. You must enter a unique login.');

            /* END: Use Case */
            return;
        }

        self::fail('Expected ValidationError messages did not occur.');
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     *
     * @depends testCreateUser
     */
    public function testCreateUserWithEmailAlreadyTaken(): void
    {
        $repository = $this->getRepository();

        $userContentType = $this->createUserContentTypeWithAccountSettings('user_email_unique', [
            Type::REQUIRE_UNIQUE_EMAIL => true,
        ]);

        $existingUser = $this->createUserVersion1(
            'existing_user',
            'unique@email.com',
            $userContentType,
        );

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            'another_user',
            // email is already taken
            'unique@email.com',
            'VerySecure@Password.1234',
            'eng-US',
            $userContentType
        );

        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', 'User');

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        try {
            // This call will fail with a "ContentFieldValidationException", because the
            // user with "unique@email.com" email already exists in database.
            $userService->createUser($userCreate, [$group]);
        } catch (ContentFieldValidationException $e) {
            // Exception is caught, as there is no other way to check exception properties.
            $this->assertValidationErrorOccurs($e, 'Email \'unique@email.com\' is used by another user. You must enter a unique email.');

            return;
        }

        self::fail('Expected ValidationError messages did not occur.');
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     *
     * @depends testCreateUser
     */
    public function testCreateInvalidFormatUsername(): void
    {
        $repository = $this->getRepository();

        $userContentType = $this->createUserContentTypeWithAccountSettings('username_format', [
            Type::USERNAME_PATTERN => '^[^@]$',
        ]);

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        // $editorsGroupId is the ID of the "Editors" user group in an Ibexa
        // Publish demo installation

        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            // login contains @
            'invalid@user',
            'unique@email.com',
            'VerySecure@Password.1234',
            'eng-US',
            $userContentType
        );

        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', 'User');

        // Load parent group for the user
        $group = $userService->loadUserGroup($editorsGroupId);

        try {
            // This call will fail with a "ContentFieldValidationException", because the
            // user with "invalid@user" login does not match "^[^@]$" pattern.
            $userService->createUser($userCreate, [$group]);
        } catch (ContentFieldValidationException $e) {
            // Exception is caught, as there is no other way to check exception properties.
            $this->assertValidationErrorOccurs($e, 'Invalid login format');

            return;
        }

        self::fail('Expected ValidationError messages did not occur.');
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     *
     * @depends testLoadUserGroup
     * @depends testNewUserCreateStruct
     */
    public function testCreateUserInTransactionWithRollback(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $repository->beginTransaction();

        try {
            $user = $this->createUserVersion1();
        } catch (Exception $e) {
            // Cleanup hanging transaction on error
            $repository->rollback();
            throw $e;
        }

        $repository->rollback();

        try {
            // Throws exception since creation of user was rolled back
            $loadedUser = $userService->loadUser($user->id);
        } catch (NotFoundException $e) {
            return;
        }
        /* END: Use Case */

        self::fail('User object still exists after rollback.');
    }

    /**
     * Test creating a user throwing NotFoundException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     */
    public function testCreateUserThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $userCreateStruct = $userService->newUserCreateStruct('new_user', 'new_user@ibexa.co', 'password', 'eng-GB');
        $userCreateStruct->setField('first_name', 'New');
        $userCreateStruct->setField('last_name', 'User');

        $parentGroup = new UserGroup(
            [
                'content' => new Content(
                    [
                        'versionInfo' => new VersionInfo(
                            [
                                'contentInfo' => new ContentInfo(['id' => 123456]),
                            ]
                        ),
                        'internalFields' => [],
                    ]
                ),
            ]
        );
        $userService->createUser($userCreateStruct, [$parentGroup]);
    }

    /**
     * Test creating a user throwing UserPasswordValidationException when password doesn't follow specific rules.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     */
    public function testCreateUserWithWeakPasswordThrowsUserPasswordValidationException()
    {
        $userContentType = $this->createUserContentTypeWithStrongPassword();

        try {
            // This call will fail with a "UserPasswordValidationException" because the
            // the password does not follow specified rules.
            $this->createTestUserWithPassword('pass', $userContentType);
        } catch (ContentFieldValidationException $e) {
            // Exception is caught, as there is no other way to check exception properties.
            $this->assertAllValidationErrorsOccur(
                $e,
                [
                    'User password must include at least one special character',
                    'User password must be at least 8 characters long',
                    'User password must include at least one upper case letter',
                    'User password must include at least one number',
                ]
            );

            return;
        }

        self::fail('Expected ValidationError messages did not occur.');
    }

    /**
     * Opposite test case for testCreateUserWithWeakPasswordThrowsUserPasswordValidationException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser
     */
    public function testCreateUserWithStrongPassword()
    {
        $userContentType = $this->createUserContentTypeWithStrongPassword();

        /* BEGIN: Use Case */
        $user = $this->createTestUserWithPassword('H@xxi0r!', $userContentType);
        /* END: Use Case */

        self::assertInstanceOf(User::class, $user);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUser()
     *
     * @depends testCreateUser
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     */
    public function testLoadUser(): void
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the newly created user
        $userReloaded = $userService->loadUser($user->id, Language::ALL);
        /* END: Use Case */

        $this->assertIsSameUser($user, $userReloaded);

        // User happens to also be a Content; isUser() should be true and isUserGroup() should be false
        self::assertTrue($userService->isUser($user), 'isUser() => false on a user');
        self::assertFalse($userService->isUserGroup($user), 'isUserGroup() => true on a user group');
    }

    /**
     * Test for the loadUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUser()
     *
     * @depends testLoadUser
     */
    public function testLoadUserThrowsNotFoundException()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $nonExistingUserId = $this->generateId('user', self::DB_INT_MAX);
        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        // This call will fail with a "NotFoundException", because no user with
        // an id equal to self::DB_INT_MAX should exist.
        $userService->loadUser($nonExistingUserId);
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::checkUserCredentials()
     *
     * @depends testCreateUser
     */
    public function testCheckUserCredentialsValid(): void
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the newly created user credentials
        $credentialsValid = $userService->checkUserCredentials($user, 'VerySecret@Password.1234');
        /* END: Use Case */

        self::assertTrue($credentialsValid);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::checkUserCredentials()
     *
     * @depends testCreateUser
     */
    public function testCheckUserCredentialsInvalid(): void
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Load the newly created user credentials
        $credentialsValid = $userService->checkUserCredentials($user, 'NotSoSecretPassword');
        /* END: Use Case */

        self::assertFalse($credentialsValid);
    }

    /**
     * Test for the loadUserByLogin() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByLogin()
     *
     * @depends testCreateUser
     */
    public function testLoadUserByLogin()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1('User');

        // Load the newly created user
        $userReloaded = $userService->loadUserByLogin('User');
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'login' => $user->login,
                'email' => $user->email,
                'passwordHash' => $user->passwordHash,
                'hashAlgorithm' => $user->hashAlgorithm,
                'enabled' => $user->enabled,
                'maxLogin' => $user->maxLogin,
                'id' => $user->id,
                'contentInfo' => $user->contentInfo,
                'versionInfo' => $user->versionInfo,
                'fields' => $user->fields,
            ],
            $userReloaded
        );
    }

    /**
     * Test for the loadUserByLogin() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByLogin()
     *
     * @depends testLoadUserByLogin
     */
    public function testLoadUserByLoginThrowsNotFoundExceptionForUnknownLogin()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $this->createUserVersion1();

        // This call will fail with a "NotFoundException", because the given
        // login/password combination does not exist.
        $userService->loadUserByLogin('user42');
        /* END: Use Case */
    }

    /**
     * Test for the loadUserByLogin() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByLogin()
     *
     * @depends testLoadUserByLogin
     */
    public function testLoadUserByLoginWorksForLoginWithWrongCase()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Lookup by user login should ignore casing
        $userReloaded = $userService->loadUserByLogin('USER');
        /* END: Use Case */

        $this->assertPropertiesCorrect(
            [
                'login' => $user->login,
                'email' => $user->email,
                'passwordHash' => $user->passwordHash,
                'hashAlgorithm' => $user->hashAlgorithm,
                'enabled' => $user->enabled,
                'maxLogin' => $user->maxLogin,
                'id' => $user->id,
                'contentInfo' => $user->contentInfo,
                'versionInfo' => $user->versionInfo,
                'fields' => $user->fields,
            ],
            $userReloaded
        );
    }

    /**
     * Test for the loadUserByLogin() method.
     *
     * In some cases people use email as login name, make sure system works as exepcted when asking for user by email.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByLogin()
     *
     * @depends testLoadUserByLogin
     */
    public function testLoadUserByLoginThrowsNotFoundExceptionForUnknownLoginByEmail()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Lookup by user login by email should behave as normal
        $userService->loadUserByLogin('user@example.com');
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUsersByEmail()
     *
     * @depends testCreateUser
     */
    public function testLoadUserByEmail(): void
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        $user = $this->createUserVersion1();

        // Load the newly created user
        $usersReloaded = iterator_to_array($userService->loadUsersByEmail('user@example.com', Language::ALL));

        self::assertCount(1, $usersReloaded);
        $this->assertIsSameUser($user, $usersReloaded[0]);
    }

    /**
     * Test for the loadUsersByEmail() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUsersByEmail()
     *
     * @depends testLoadUserByEmail
     */
    public function testLoadUserByEmailReturnsEmptyInUnknownEmail()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $this->createUserVersion1();

        // This call will return empty array, because the given
        // login/password combination does not exist.
        $emptyUserList = $userService->loadUsersByEmail('user42@example.com');
        /* END: Use Case */

        self::assertEquals([], $emptyUserList);
    }

    /**
     * Test for the deleteUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::deleteUser()
     *
     * @depends testCreateUser
     * @depends testLoadUser
     */
    public function testDeleteUser()
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Delete the currently created user
        $userService->deleteUser($user);
        /* END: Use Case */

        // We use the NotFoundException here to verify that the user not exists
        $userService->loadUser($user->id);
    }

    /**
     * Test for the deleteUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::deleteUser()
     *
     * @depends testCreateUser
     * @depends testLoadUser
     */
    public function testDeleteUserDeletesRelatedBookmarks()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();
        $locationService = $repository->getLocationService();
        $bookmarkService = $repository->getBookmarkService();
        /* BEGIN: Use Case */
        $admin = $repository->getPermissionResolver()->getCurrentUserReference();

        $user = $this->createUserVersion1();

        $repository->getPermissionResolver()->setCurrentUserReference($user);

        $bookmarkService->createBookmark(
            $locationService->loadLocation($this->generateId('location', 43))
        );

        $repository->getPermissionResolver()->setCurrentUserReference($admin);
        // Delete the currently created user
        $userService->deleteUser($user);

        $repository->getPermissionResolver()->setCurrentUserReference($user);
        /* END: Use Case */

        self::assertEquals(0, $bookmarkService->loadBookmarks(0, 9999)->totalCount);
    }

    /**
     * Test for the newUserUpdateStruct() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::newUserUpdateStruct()
     */
    public function testNewUserUpdateStruct()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        $userService = $repository->getUserService();

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();
        /* END: Use Case */

        self::assertInstanceOf(
            UserUpdateStruct::class,
            $userUpdate
        );

        self::assertNull($userUpdate->contentUpdateStruct);
        self::assertNull($userUpdate->contentMetadataUpdateStruct);

        $this->assertPropertiesCorrect(
            [
                'email' => null,
                'password' => null,
                'enabled' => null,
                'maxLogin' => null,
            ],
            $userUpdate
        );
    }

    /**
     * Test for the updateUser() method.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testCreateUser
     * @depends testNewUserUpdateStruct
     */
    public function testUpdateUser()
    {
        // As \Ibexa\Tests\Integration\Core\Repository\UserServiceTest::testUpdateUserUpdatesExpectedProperties belongs on this test,
        // and it is the only test that tracks real time passing with delta
        // but actual password change is done here, therefore for _reasons_ we need to disable ClockMock here.
        ClockMock::withClockMock(false);
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set new values for password and maxLogin
        $userUpdate->password = 'my-new-password';
        $userUpdate->maxLogin = 42;
        $userUpdate->enabled = false;

        // Updated the user record.
        $userVersion2 = $userService->updateUser($user, $userUpdate);
        /* END: Use Case */

        self::assertInstanceOf(User::class, $userVersion2);

        return $userVersion2;
    }

    /**
     * Test for the updateUser() and loadUsersByEmail() method on change to email.
     */
    public function testUpdateUserEmail(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        // Create a user
        $user = $this->createUserVersion1();

        // Check we get what we expect (and implicit warmup any kind of cache)
        $users = $userService->loadUsersByEmail('user2@example.com');
        self::assertCount(0, $users);

        // Update user with the given email address
        $userUpdate = $userService->newUserUpdateStruct();
        $userUpdate->email = 'user2@example.com';
        $updatedUser = $userService->updateUser($user, $userUpdate);
        self::assertInstanceOf(User::class, $updatedUser);

        // Check that we can load user by email
        $users = iterator_to_array($userService->loadUsersByEmail('user2@example.com'));
        self::assertCount(1, $users);
        self::assertInstanceOf(User::class, $users[0]);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser
     *
     * @depends testCreateUser
     * @depends testNewUserUpdateStruct
     */
    public function testUpdateUserNoPassword(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set new values for maxLogin, don't change password
        $userUpdate->maxLogin = 43;
        $userUpdate->enabled = false;

        // Updated the user record.
        $userVersion2 = $userService->updateUser($user, $userUpdate);
        /* END: Use Case */

        self::assertInstanceOf(User::class, $user);

        self::assertEquals(
            [
                'login' => $user->login,
                'email' => $user->email,
                'passwordHash' => $user->passwordHash,
                'hashAlgorithm' => $user->hashAlgorithm,
                'maxLogin' => 43,
                'enabled' => false,
            ],
            [
                'login' => $userVersion2->login,
                'email' => $userVersion2->email,
                'passwordHash' => $userVersion2->passwordHash,
                'hashAlgorithm' => $userVersion2->hashAlgorithm,
                'maxLogin' => $userVersion2->maxLogin,
                'enabled' => $userVersion2->enabled,
            ]
        );
    }

    /**
     * Test for the updateUser() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserUpdatesExpectedProperties(User $user)
    {
        self::assertEquals(
            [
                'login' => 'user',
                'email' => 'user@example.com',
                'maxLogin' => 42,
                'enabled' => false,
            ],
            [
                'login' => $user->login,
                'email' => $user->email,
                'maxLogin' => $user->maxLogin,
                'enabled' => $user->enabled,
            ]
        );

        // Make sure passwordUpdatedAt field has been updated together with password
        self::assertNotNull($user->passwordUpdatedAt);
        self::assertEqualsWithDelta(
            $user->getVersionInfo()->modificationDate->getTimestamp(),
            $user->passwordUpdatedAt->getTimestamp(),
            2.0
        );
    }

    /**
     * Test for the updateUser() method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserReturnsPublishedVersion(User $user)
    {
        self::assertEquals(
            APIVersionInfo::STATUS_PUBLISHED,
            $user->getVersionInfo()->status
        );
    }

    /**
     * Test for the updateUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserWithContentMetadataUpdateStruct()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Get the ContentService implementation
        $contentService = $repository->getContentService();

        // Create a metadata update struct and change the remote id.
        $metadataUpdate = $contentService->newContentMetadataUpdateStruct();
        $metadataUpdate->remoteId = '85e10037d1ac0a00aa75443ced483e08';

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set the metadata update struct.
        $userUpdate->contentMetadataUpdateStruct = $metadataUpdate;

        // Updated the user record.
        $userVersion2 = $userService->updateUser($user, $userUpdate);

        // The contentInfo->remoteId will be changed now.
        $remoteId = $userVersion2->contentInfo->remoteId;
        /* END: Use Case */

        self::assertEquals('85e10037d1ac0a00aa75443ced483e08', $remoteId);
    }

    /**
     * Test for the updateUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserWithContentUpdateStruct()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Get the ContentService implementation
        $contentService = $repository->getContentService();

        // Create a content update struct and change the remote id.
        $contentUpdate = $contentService->newContentUpdateStruct();
        $contentUpdate->setField('first_name', 'Hello', 'eng-US');
        $contentUpdate->setField('last_name', 'World', 'eng-US');

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set the content update struct.
        $userUpdate->contentUpdateStruct = $contentUpdate;

        // Updated the user record.
        $userVersion2 = $userService->updateUser($user, $userUpdate);

        $name = sprintf(
            '%s %s',
            $userVersion2->getFieldValue('first_name'),
            $userVersion2->getFieldValue('last_name')
        );
        /* END: Use Case */

        self::assertEquals('Hello World', $name);
    }

    /**
     * Test for the updateUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserWhenMissingField()
    {
        $this->expectException(ContentFieldValidationException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Get the ContentService implementation
        $contentService = $repository->getContentService();

        // Create a content update struct and change the remote id.
        $contentUpdate = $contentService->newContentUpdateStruct();
        $contentUpdate->setField('first_name', null, 'eng-US');

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set the content update struct.
        $userUpdate->contentUpdateStruct = $contentUpdate;

        // This call will fail with a "ContentFieldValidationException" because the
        // mandatory field "first_name" is set to an empty value.
        $userService->updateUser($user, $userUpdate);

        /* END: Use Case */
    }

    /**
     * Test for the updateUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser()
     *
     * @depends testUpdateUser
     */
    public function testUpdateUserThrowsInvalidArgumentExceptionOnFieldTypeNotAccept()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // Get the ContentService implementation
        $contentService = $repository->getContentService();

        $contentUpdate = $contentService->newContentUpdateStruct();
        // An object of stdClass is not valid for the field first_name
        $contentUpdate->setField('first_name', new \stdClass(), 'eng-US');

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();

        // Set the content update struct.
        $userUpdate->contentUpdateStruct = $contentUpdate;

        // This call will fail with a "InvalidArgumentException" because the
        // the field "first_name" does not accept the given value.
        $userService->updateUser($user, $userUpdate);

        /* END: Use Case */
    }

    /**
     * Test updating a user throwing UserPasswordValidationException when password doesn't follow specified rules.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser
     */
    public function testUpdateUserWithWeakPasswordThrowsUserPasswordValidationException()
    {
        $userService = $this->getRepository()->getUserService();

        $user = $this->createTestUserWithPassword('H@xxxiR!_1', $this->createUserContentTypeWithStrongPassword());

        /* BEGIN: Use Case */
        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();
        $userUpdate->password = 'pass';

        try {
            // This call will fail with a "UserPasswordValidationException" because the
            // the password does not follow specified rules
            $userService->updateUser($user, $userUpdate);
            /* END: Use Case */
        } catch (ContentFieldValidationException $e) {
            // Exception is caught, as there is no other way to check exception properties.
            $this->assertValidationErrorOccurs($e, 'User password must include at least one special character');
            $this->assertValidationErrorOccurs($e, 'User password must be at least 8 characters long');
            $this->assertValidationErrorOccurs($e, 'User password must include at least one upper case letter');
            $this->assertValidationErrorOccurs($e, 'User password must include at least one number');

            /* END: Use Case */
            return;
        }

        self::fail('Expected ValidationError messages did not occur.');
    }

    /**
     * Opposite test case for testUpdateUserWithWeakPasswordThrowsUserPasswordValidationException.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser
     */
    public function testUpdateUserWithStrongPassword()
    {
        $userService = $this->getRepository()->getUserService();

        $user = $this->createTestUserWithPassword('H@xxxiR!_1', $this->createUserContentTypeWithStrongPassword());

        /* BEGIN: Use Case */
        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();
        $userUpdate->password = 'H@xxxiR!_2';

        $user = $userService->updateUser($user, $userUpdate);
        /* END: Use Case */

        self::assertInstanceOf(User::class, $user);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUser
     */
    public function testUpdateUserByUserWithLimitations(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $user = $this->createTestUserWithPassword('H@xxxiR!_1', $this->createUserContentTypeWithStrongPassword());

        $currentUser = $this->createUserWithPolicies(
            'user',
            [
                ['module' => 'content', 'function' => 'edit'],
                ['module' => 'content', 'function' => 'read'],
                ['module' => 'content', 'function' => 'versionread'],
                ['module' => 'content', 'function' => 'publish'],
                ['module' => 'user', 'function' => 'password'],
            ],
            new SubtreeLimitation(['limitationValues' => ['/1/5']])
        );
        $repository->getPermissionResolver()->setCurrentUserReference($currentUser);

        // Create a new update struct instance
        $userUpdate = $userService->newUserUpdateStruct();
        $userUpdate->password = 'H@xxxiR!_2';

        $user = $userService->updateUser($user, $userUpdate);

        self::assertInstanceOf(User::class, $user);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserPassword
     */
    public function testUpdateUserPasswordWorksWithUserPasswordRole(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $permissionResolver = $repository->getPermissionResolver();

        $this->createRoleWithPolicies('CanChangePassword', [
            ['module' => 'user', 'function' => 'password'],
        ]);

        $user = $this->createCustomUserWithLogin(
            'with_role_password',
            'with_role_password@example.com',
            'Anons',
            'CanChangePassword'
        );
        $previousHash = $user->passwordHash;

        $permissionResolver->setCurrentUserReference($user);

        $userService->updateUserPassword($user, 'new password');

        $user = $userService->loadUserByLogin('with_role_password');
        self::assertNotEquals($previousHash, $user->passwordHash);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \ErrorException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function testUpdateUserPasswordWithUnsupportedHashType(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $user = $this->createUser('john.doe', 'John', 'Doe');
        $oldPasswordHash = $user->passwordHash;

        $wrongHashType = 1;
        $this->updateRawPasswordHash($user->getUserId(), $wrongHashType);
        $newPassword = 'new_secret123';
        // no need to invalidate cache since there was no load between create & raw database update
        $user = $userService->updateUserPassword($user, $newPassword);

        self::assertTrue($userService->checkUserCredentials($user, $newPassword));
        self::assertNotEquals($oldPasswordHash, $user->passwordHash);
    }

    /**
     * Test for the loadUserGroupsOfUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroupsOfUser
     *
     * @depends testCreateUser
     */
    public function testLoadUserGroupsOfUser()
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();

        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // This array will contain the "Editors" user group name
        $userGroupNames = [];
        foreach ($userService->loadUserGroupsOfUser($user) as $userGroup) {
            self::assertInstanceOf(UserGroup::class, $userGroup);
            $userGroupNames[] = $userGroup->getFieldValue('name');
        }
        /* END: Use Case */

        self::assertEquals(['Editors'], $userGroupNames);
    }

    /**
     * Test for the loadUsersOfUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUsersOfUserGroup
     *
     * @depends testCreateUser
     */
    public function testLoadUsersOfUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $group = $userService->loadUserGroup($this->generateId('group', 13));

        /* BEGIN: Use Case */
        $this->createUserVersion1();

        $this->refreshSearch($repository);

        // This array will contain the email of the newly created "Editor" user
        $email = [];
        foreach ($userService->loadUsersOfUserGroup($group) as $user) {
            self::assertInstanceOf(User::class, $user);
            $email[] = $user->email;
        }
        /* END: Use Case */
        self::assertEquals(['user@example.com'], $email);
    }

    /**
     * Test for the assignUserToUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::assignUserToUserGroup()
     *
     * @depends testLoadUserGroupsOfUser
     */
    public function testAssignUserToUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $administratorGroupId = $this->generateId('group', 12);
        /* BEGIN: Use Case */
        // $administratorGroupId is the ID of the "Administrator" group in an
        // Ibexa demo installation

        $user = $this->createUserVersion1();

        // Assign group to newly created user
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($administratorGroupId)
        );

        // This array will contain "Editors" and "Administrator users"
        $userGroupNames = [];
        foreach ($userService->loadUserGroupsOfUser($user) as $userGroup) {
            $userGroupNames[] = $userGroup->getFieldValue('name');
        }
        /* END: Use Case */

        sort($userGroupNames, SORT_STRING);

        self::assertEquals(
            [
                'Administrator users',
                'Editors',
            ],
            $userGroupNames
        );
    }

    /**
     * Test for the assignUserToUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::assignUserToUserGroup
     *
     * @depends testAssignUserToUserGroup
     */
    public function testAssignUserToUserGroupThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Argument \'user\' is invalid: User is already in the given User Group');

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        // $editorsGroupId is the ID of the "Editors" group in an
        // Ibexa demo installation

        // This call will fail with an "InvalidArgumentException", because the
        // user is already assigned to the "Editors" group
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($editorsGroupId)
        );
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::assignUserToUserGroup
     */
    public function testAssignUserToGroupWithLocationsValidation(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $locationService = $repository->getLocationService();

        $administratorGroupId = $this->generateId('group', 12);

        $user = $this->createUserVersion1();

        $group = $userService->loadUserGroup($administratorGroupId);
        $groupLocation = $locationService->loadLocation($group->contentInfo->mainLocationId);

        // Count number of child locations before assigning user to group
        $count = $locationService->getLocationChildCount($groupLocation);
        $expectedCount = $count + 1;

        $userService->assignUserToUserGroup(
            $user,
            $group
        );

        $this->refreshSearch($repository);

        // Count number of child locations after assigning the user to a group
        $actualCount = $locationService->getLocationChildCount($groupLocation);

        self::assertEquals($expectedCount, $actualCount);
    }

    /**
     * Test for the unAssignUssrFromUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::unAssignUssrFromUserGroup()
     *
     * @depends testLoadUserGroupsOfUser
     */
    public function testUnAssignUserFromUserGroup()
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $editorsGroupId = $this->generateId('group', 13);
        $anonymousGroupId = $this->generateId('group', 42);

        /* BEGIN: Use Case */
        // $anonymousGroupId is the ID of the "Anonymous users" group in an Ibexa
        // Publish demo installation

        $user = $this->createUserVersion1();

        // Assign group to newly created user
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($anonymousGroupId)
        );

        // Unassign user from "Editors" group
        $userService->unAssignUserFromUserGroup(
            $user,
            $userService->loadUserGroup($editorsGroupId)
        );

        // This array will contain "Anonymous users"
        $userGroupNames = [];
        foreach ($userService->loadUserGroupsOfUser($user) as $userGroup) {
            $userGroupNames[] = $userGroup->getFieldValue('name');
        }
        /* END: Use Case */

        self::assertEquals(['Anonymous users'], $userGroupNames);
    }

    /**
     * Test for the unAssignUserFromUserGroup() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::unAssignUserFromUserGroup()
     *
     * @depends testUnAssignUserFromUserGroup
     */
    public function testUnAssignUserFromUserGroupThrowsInvalidArgumentException()
    {
        $this->expectException(InvalidArgumentException::class);

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $administratorGroupId = $this->generateId('group', 12);
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();
        // $administratorGroupId is the ID of the "Administrator" group in an
        // Ibexa demo installation

        // This call will fail with an "InvalidArgumentException", because the
        // user is not assigned to the "Administrator" group
        $userService->unAssignUserFromUserGroup(
            $user,
            $userService->loadUserGroup($administratorGroupId)
        );
        /* END: Use Case */
    }

    /**
     * Test for the unAssignUserFromUserGroup() method removing user from the last group.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::unAssignUserFromUserGroup
     *
     * @depends testUnAssignUserFromUserGroup
     */
    public function testUnAssignUserFromUserGroupThrowsBadStateArgumentException()
    {
        $this->expectException(BadStateException::class);
        $this->expectExceptionMessage('Argument \'user\' has a bad state: User only has one User Group, cannot unassign from last group');

        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $editorsGroupId = $this->generateId('group', 13);
        /* BEGIN: Use Case */
        $user = $this->createUserVersion1();

        // This call will fail with an "BadStateException", because the
        // user has to be assigned to at least one group
        $userService->unAssignUserFromUserGroup(
            $user,
            $userService->loadUserGroup($editorsGroupId)
        );
        /* END: Use Case */
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::unAssignUserFromUserGroup
     */
    public function testUnAssignUserToGroupWithLocationValidation(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $locationService = $repository->getLocationService();

        $editorsGroupId = $this->generateId('group', 13);
        $anonymousGroupId = $this->generateId('group', 42);

        $user = $this->createUserVersion1();

        $this->refreshSearch($repository);

        $group = $userService->loadUserGroup($editorsGroupId);
        $groupLocation = $locationService->loadLocation($group->contentInfo->mainLocationId);

        // Count number of child locations before unassigning the user from a group
        $count = $locationService->getLocationChildCount($groupLocation);
        $expectedCount = $count - 1;

        // Assigning user to a different group to avoid removing all groups from the user
        $userService->assignUserToUserGroup(
            $user,
            $userService->loadUserGroup($anonymousGroupId)
        );

        $userService->unAssignUserFromUserGroup(
            $user,
            $userService->loadUserGroup($editorsGroupId)
        );

        $this->refreshSearch($repository);

        // Count number of child locations after unassigning the user from a group
        $actualCount = $locationService->getLocationChildCount($groupLocation);

        self::assertEquals($expectedCount, $actualCount);
    }

    /**
     * Test that multi-language logic for the loadUserGroup method respects prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserGroupWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $userGroup = $this->createMultiLanguageUserGroup();
        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = $userGroup->contentInfo->mainLanguageCode;
        }

        $loadedUserGroup = $userService->loadUserGroup($userGroup->id, $prioritizedLanguages);

        self::assertEquals(
            $loadedUserGroup->getName($expectedLanguageCode),
            $loadedUserGroup->getName()
        );
        self::assertEquals(
            $loadedUserGroup->getFieldValue('description', $expectedLanguageCode),
            $loadedUserGroup->getFieldValue('description')
        );
    }

    /**
     * Test that multi-language logic works correctly after updating user group main language.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserGroupWithPrioritizedLanguagesListAfterMainLanguageUpdate(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();

        $userGroup = $this->createMultiLanguageUserGroup();

        $userGroupUpdateStruct = $userService->newUserGroupUpdateStruct();
        $userGroupUpdateStruct->contentMetadataUpdateStruct = $contentService->newContentMetadataUpdateStruct();
        $userGroupUpdateStruct->contentMetadataUpdateStruct->mainLanguageCode = 'eng-GB';
        $userService->updateUserGroup($userGroup, $userGroupUpdateStruct);

        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = 'eng-GB';
        }

        $loadedUserGroup = $userService->loadUserGroup($userGroup->id, $prioritizedLanguages);

        self::assertEquals(
            $loadedUserGroup->getName($expectedLanguageCode),
            $loadedUserGroup->getName()
        );
        self::assertEquals(
            $loadedUserGroup->getFieldValue('description', $expectedLanguageCode),
            $loadedUserGroup->getFieldValue('description')
        );
    }

    /**
     * Test that multi-language logic for the loadSubUserGroups method respects prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadSubUserGroups
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadSubUserGroupsWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        // create main group for subgroups
        $userGroup = $this->createMultiLanguageUserGroup(4);
        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = $userGroup->contentInfo->mainLanguageCode;
        }

        // create subgroups
        $this->createMultiLanguageUserGroup($userGroup->id);
        $this->createMultiLanguageUserGroup($userGroup->id);

        $userGroup = $userService->loadUserGroup($userGroup->id, $prioritizedLanguages);

        $subUserGroups = $userService->loadSubUserGroups($userGroup, 0, 2, $prioritizedLanguages);
        foreach ($subUserGroups as $subUserGroup) {
            self::assertEquals(
                $subUserGroup->getName($expectedLanguageCode),
                $subUserGroup->getName()
            );
            self::assertEquals(
                $subUserGroup->getFieldValue('description', $expectedLanguageCode),
                $subUserGroup->getFieldValue('description')
            );
        }
    }

    /**
     * Test that multi-language logic for the loadUser method respects prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUser
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $user = $this->createMultiLanguageUser();
        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = $user->contentInfo->mainLanguageCode;
        }

        $loadedUser = $userService->loadUser($user->id, $prioritizedLanguages);

        self::assertEquals(
            $loadedUser->getName($expectedLanguageCode),
            $loadedUser->getName()
        );

        foreach (['fist_name', 'last_name', 'signature'] as $fieldIdentifier) {
            self::assertEquals(
                $loadedUser->getFieldValue($fieldIdentifier, $expectedLanguageCode),
                $loadedUser->getFieldValue($fieldIdentifier)
            );
        }
    }

    /**
     * Test that multi-language logic for the loadUser method works correctly after updating
     * user content main language.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroup
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserWithPrioritizedLanguagesListAfterMainLanguageUpdate(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();

        $user = $this->createMultiLanguageUser();
        // sanity check
        self::assertEquals($user->contentInfo->mainLanguageCode, 'eng-US');

        $userUpdateStruct = $userService->newUserUpdateStruct();
        $userUpdateStruct->contentMetadataUpdateStruct = $contentService->newContentMetadataUpdateStruct();
        $userUpdateStruct->contentMetadataUpdateStruct->mainLanguageCode = 'eng-GB';
        $userService->updateUser($user, $userUpdateStruct);
        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = 'eng-GB';
        }

        $loadedUser = $userService->loadUser($user->id, $prioritizedLanguages);

        self::assertEquals(
            $loadedUser->getName($expectedLanguageCode),
            $loadedUser->getName()
        );

        foreach (['fist_name', 'last_name', 'signature'] as $fieldIdentifier) {
            self::assertEquals(
                $loadedUser->getFieldValue($fieldIdentifier, $expectedLanguageCode),
                $loadedUser->getFieldValue($fieldIdentifier)
            );
        }
    }

    /**
     * Test that multi-language logic for the loadUserByLogin method respects prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByLogin
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserByLoginWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $user = $this->createMultiLanguageUser();

        // load, with prioritized languages, the newly created user
        $loadedUser = $userService->loadUserByLogin($user->login, $prioritizedLanguages);
        if ($expectedLanguageCode === null) {
            $expectedLanguageCode = $loadedUser->contentInfo->mainLanguageCode;
        }

        self::assertEquals(
            $loadedUser->getName($expectedLanguageCode),
            $loadedUser->getName()
        );

        foreach (['first_name', 'last_name', 'signature'] as $fieldIdentifier) {
            self::assertEquals(
                $loadedUser->getFieldValue($fieldIdentifier, $expectedLanguageCode),
                $loadedUser->getFieldValue($fieldIdentifier)
            );
        }
    }

    /**
     * Test that multi-language logic for the loadUsersByEmail method respects
     * prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUsersByEmail
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUsersByEmailWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $user = $this->createMultiLanguageUser();

        // load, with prioritized languages, users by email
        $loadedUsers = $userService->loadUsersByEmail($user->email, $prioritizedLanguages);

        foreach ($loadedUsers as $loadedUser) {
            if ($expectedLanguageCode === null) {
                $expectedLanguageCode = $loadedUser->contentInfo->mainLanguageCode;
            }
            self::assertEquals(
                $loadedUser->getName($expectedLanguageCode),
                $loadedUser->getName()
            );

            foreach (['first_name', 'last_name', 'signature'] as $fieldIdentifier) {
                self::assertEquals(
                    $loadedUser->getFieldValue($fieldIdentifier, $expectedLanguageCode),
                    $loadedUser->getFieldValue($fieldIdentifier)
                );
            }
        }
    }

    /**
     * Test that multi-language logic for the loadUserGroupsOfUser method respects
     * prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserGroupsOfUser
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUserGroupsOfUserWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        $userGroup = $this->createMultiLanguageUserGroup();
        $user = $this->createMultiLanguageUser($userGroup->id);

        $userGroups = $userService->loadUserGroupsOfUser($user, 0, 25, $prioritizedLanguages);
        foreach ($userGroups as $userGroup) {
            self::assertEquals(
                $userGroup->getName($expectedLanguageCode),
                $userGroup->getName()
            );
            self::assertEquals(
                $userGroup->getFieldValue('description', $expectedLanguageCode),
                $userGroup->getFieldValue('description')
            );
        }
    }

    /**
     * Test that multi-language logic for the loadUsersOfUserGroup method respects
     * prioritized language list.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUsersOfUserGroup
     *
     * @dataProvider getPrioritizedLanguageList
     *
     * @param string[] $prioritizedLanguages
     * @param string|null $expectedLanguageCode language code of expected translation
     */
    public function testLoadUsersOfUserGroupWithPrioritizedLanguagesList(
        array $prioritizedLanguages,
        $expectedLanguageCode
    ) {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        // create parent user group
        $userGroup = $this->createMultiLanguageUserGroup();
        // add two users to the created parent user group
        $this->createMultiLanguageUser($userGroup->id);
        $this->createMultiLanguageUser($userGroup->id);

        // test loading of users via user group with prioritized languages list
        $users = $userService->loadUsersOfUserGroup($userGroup, 0, 25, $prioritizedLanguages);
        foreach ($users as $user) {
            if ($expectedLanguageCode === null) {
                $expectedLanguageCode = $user->contentInfo->mainLanguageCode;
            }
            self::assertEquals(
                $user->getName($expectedLanguageCode),
                $user->getName()
            );

            foreach (['first_name', 'last_name', 'signature'] as $fieldIdentifier) {
                self::assertEquals(
                    $user->getFieldValue($fieldIdentifier, $expectedLanguageCode),
                    $user->getFieldValue($fieldIdentifier)
                );
            }
        }
    }

    /**
     * Get prioritized languages list data.
     *
     * Test cases using this data provider should expect the following arguments:
     * <code>
     *   array $prioritizedLanguagesList
     *   string $expectedLanguage (if null - use main language)
     * </code>
     *
     * @return array
     */
    public function getPrioritizedLanguageList()
    {
        return [
            [[], null],
            [['eng-US'], 'eng-US'],
            [['eng-GB'], 'eng-GB'],
            [['eng-US', 'eng-GB'], 'eng-US'],
            [['eng-GB', 'eng-US'], 'eng-GB'],
            // use non-existent group as the first one
            [['ger-DE'], null],
            [['ger-DE', 'eng-GB'], 'eng-GB'],
        ];
    }

    /**
     * @param int $parentGroupId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    private function createMultiLanguageUserGroup($parentGroupId = 4)
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        // create user group with multiple translations
        $parentGroupId = $this->generateId('group', $parentGroupId);
        $parentGroup = $userService->loadUserGroup($parentGroupId);

        $userGroupCreateStruct = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreateStruct->setField('name', 'US user group', 'eng-US');
        $userGroupCreateStruct->setField('name', 'GB user group', 'eng-GB');
        $userGroupCreateStruct->setField('description', 'US user group description', 'eng-US');
        $userGroupCreateStruct->setField('description', 'GB user group description', 'eng-GB');
        $userGroupCreateStruct->alwaysAvailable = true;

        return $userService->createUserGroup($userGroupCreateStruct, $parentGroup);
    }

    /**
     * Create a user group fixture in a variable named <b>$userGroup</b>,.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    private function createUserGroupVersion1()
    {
        $repository = $this->getRepository();

        $mainGroupId = $this->generateId('group', 4);
        /* BEGIN: Inline */
        // $mainGroupId is the ID of the main "Users" group

        $userService = $repository->getUserService();

        // Load main group
        $parentUserGroup = $userService->loadUserGroup($mainGroupId);

        // Instantiate a new create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreate->setField('name', 'Example Group');

        // Create the new user group
        $userGroup = $userService->createUserGroup(
            $userGroupCreate,
            $parentUserGroup
        );
        /* END: Inline */

        return $userGroup;
    }

    /**
     * Create user with multiple translations of User Content fields.
     *
     * @param int $userGroupId User group ID (default 13 - Editors)
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    private function createMultiLanguageUser($userGroupId = 13)
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $randomLogin = md5(mt_rand() . time());
        $userCreateStruct = $userService->newUserCreateStruct(
            $randomLogin,
            "{$randomLogin}@example.com",
            'secret',
            'eng-US'
        );
        $userCreateStruct->enabled = true;
        $userCreateStruct->alwaysAvailable = true;

        // set field for each language
        foreach (['eng-US', 'eng-GB'] as $languageCode) {
            $userCreateStruct->setField('first_name', "{$languageCode} Example", $languageCode);
            $userCreateStruct->setField('last_name', "{$languageCode} User", $languageCode);
            $userCreateStruct->setField('signature', "{$languageCode} signature", $languageCode);
        }

        // Load parent group for the user
        $group = $userService->loadUserGroup($userGroupId);

        // Create a new user
        return $userService->createUser($userCreateStruct, [$group]);
    }

    /**
     * Test for the createUser() method.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::createUser()
     */
    public function testCreateUserWithDefaultPasswordHashTypeWhenHashTypeIsUnsupported(): void
    {
        $repository = $this->getRepository();
        $eventUserService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties.
        $createStruct = $eventUserService->newUserCreateStruct(
            'user',
            'user@example.com',
            'secret',
            'eng-US'
        );

        // Set some fields required by the user ContentType.
        $createStruct->setField('first_name', 'Example');
        $createStruct->setField('last_name', 'User');

        // Get User fieldType.
        $userFieldDef = null;
        foreach ($createStruct->fields as $field) {
            if ($field->fieldTypeIdentifier === 'ibexa_user') {
                $userFieldDef = $field;
                break;
            }
        }

        if (!$userFieldDef) {
            self::fail('User FieldType not found in userCreateStruct!');
        }

        /** @var \Ibexa\Core\FieldType\User\Value $userValue */
        $userValue = $userFieldDef->value;

        // Set not supported hash type.
        $userValue->passwordHashType = 42424242;

        // Create a new user instance.
        // 13 is ID of the "Editors" user group in an Ibexa demo installation.
        $createdUser = $eventUserService->createUser($createStruct, [$eventUserService->loadUserGroup(13)]);

        self::assertEquals(User::DEFAULT_PASSWORD_HASH, $createdUser->hashAlgorithm);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByToken
     */
    public function testLoadUserByToken(): string
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $user = $this->createUserVersion1();

        $userTokenUpdateStruct = new UserTokenUpdateStruct();
        $userTokenUpdateStruct->hashKey = md5('hash');
        $userTokenUpdateStruct->time = (new DateTime())->add(new DateInterval('PT1H'));

        $userService->updateUserToken($user, $userTokenUpdateStruct);

        $loadedUser = $userService->loadUserByToken($userTokenUpdateStruct->hashKey, Language::ALL);
        $this->assertIsSameUser($user, $loadedUser);

        return $userTokenUpdateStruct->hashKey;
    }

    /**
     * Test updating User Token.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::updateUserToken()
     *
     * @depends testLoadUserByToken
     *
     * @param string $originalUserToken
     */
    public function testUpdateUserToken($originalUserToken)
    {
        $repository = $this->getRepository(false);
        $userService = $repository->getUserService();

        $user = $userService->loadUserByToken($originalUserToken);

        $userTokenUpdateStruct = new UserTokenUpdateStruct();
        $userTokenUpdateStruct->hashKey = md5('my_updated_hash');
        $userTokenUpdateStruct->time = (new DateTime())->add(new DateInterval('PT1H'));

        $userService->updateUserToken($user, $userTokenUpdateStruct);

        $loadedUser = $userService->loadUserByToken($userTokenUpdateStruct->hashKey);
        self::assertEquals($user, $loadedUser);
    }

    /**
     * Test invalidating (expiring) User Token.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::expireUserToken()
     *
     * @depends testLoadUserByToken
     *
     * @param string $userToken
     */
    public function testExpireUserToken($userToken)
    {
        $this->expectException(NotFoundException::class);

        $repository = $this->getRepository(false);
        $userService = $repository->getUserService();

        // sanity check
        $userService->loadUserByToken($userToken);

        $userService->expireUserToken($userToken);

        // should throw NotFoundException now
        $userService->loadUserByToken($userToken);
    }

    /**
     * Test trying to load User by invalid Token.
     *
     * @covers \Ibexa\Contracts\Core\Repository\UserService::loadUserByToken
     */
    public function testLoadUserByTokenThrowsNotFoundException(): void
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        $this->expectException(NotFoundException::class);
        $userService->loadUserByToken('not_existing_token');
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::validatePassword()
     */
    public function testValidatePasswordWithDefaultContext()
    {
        $userService = $this->getRepository()->getUserService();

        /* BEGIN: Use Case */
        $errors = $userService->validatePassword('pass');
        /* END: Use Case */

        self::assertEmpty($errors);
    }

    /**
     * @covers \Ibexa\Contracts\Core\Repository\UserService::validatePassword()
     *
     * @dataProvider dataProviderForValidatePassword
     */
    public function testValidatePassword(string $password, array $expectedErrors)
    {
        $userService = $this->getRepository()->getUserService();
        $contentType = $this->createUserContentTypeWithStrongPassword();

        /* BEGIN: Use Case */
        $context = new PasswordValidationContext([
            'contentType' => $contentType,
        ]);

        $actualErrors = $userService->validatePassword($password, $context);
        /* END: Use Case */

        self::assertEquals($expectedErrors, $actualErrors);
    }

    public function testValidatePasswordReturnsErrorWhenOldPasswordIsReused(): void
    {
        $password = 'P@blish123!';

        $userService = $this->getRepository()->getUserService();
        // Password expiration needs to be enabled
        $contentType = $this->createUserContentTypeWithPasswordExpirationDate();

        $user = $this->createTestUserWithPassword($password, $contentType);

        $context = new PasswordValidationContext([
            'contentType' => $contentType,
            'user' => $user,
        ]);

        $actualErrors = $userService->validatePassword($password, $context);

        self::assertEquals(
            [new ValidationError('New password cannot be the same as old password', null, [], 'password')],
            $actualErrors
        );
    }

    public function getDataForTestPasswordUpdateRespectsAllValidationSettings(): iterable
    {
        $oldPassword = 'P@blish123!';

        yield 'require at least one upper case character' => [
            $oldPassword,
            'p@blish123!',
            'User password must include at least one upper case letter',
        ];

        yield 'require at least one lower case character' => [
            $oldPassword,
            'P@BLISH123!',
            'User password must include at least one lower case letter',
        ];

        yield 'require at least one numeric character' => [
            $oldPassword,
            'P@blishONETWOTHREE!',
            'User password must include at least one number',
        ];

        yield 'require at least one non-alphanumeric character' => [
            $oldPassword,
            'Publish123',
            'User password must include at least one special character',
        ];

        yield 'require min. length >= 8 chars' => [
            $oldPassword,
            'P@b123!',
            'User password must be at least 8 characters long',
        ];

        yield 'require new password' => [
            $oldPassword,
            $oldPassword,
            'New password cannot be the same as old password',
        ];
    }

    /**
     * @dataProvider getDataForTestPasswordUpdateRespectsAllValidationSettings
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\Exception
     * @throws \Exception
     */
    public function testUpdateUserPasswordPerformsValidation(
        string $oldPassword,
        string $newPassword,
        string $expectedExceptionMessage
    ): void {
        $userService = $this->getRepository()->getUserService();

        $contentType = $this->createUserContentTypeWithStrongPassword();
        $user = $this->createTestUserWithPassword($oldPassword, $contentType);

        try {
            $userService->updateUserPassword($user, $newPassword);

            self::fail(
                sprintf(
                    'Failed to get validation exception with message "%s"',
                    $expectedExceptionMessage
                )
            );
        } catch (ContentFieldValidationException $e) {
            $this->assertValidationErrorOccurs($e, $expectedExceptionMessage);
        }
    }

    /**
     * Data provider for testValidatePassword.
     *
     * @return array
     */
    public function dataProviderForValidatePassword(): array
    {
        return [
            [
                'pass',
                [
                    new ValidationError('User password must be at least %length% characters long', null, [
                        '%length%' => 8,
                    ], 'password'),
                    new ValidationError('User password must include at least one upper case letter', null, [], 'password'),
                    new ValidationError('User password must include at least one number', null, [], 'password'),
                    new ValidationError('User password must include at least one special character', null, [], 'password'),
                ],
            ],
            [
                'H@xxxi0R!!!',
                [],
            ],
        ];
    }

    public function testGetPasswordInfo(): void
    {
        $userService = $this->getRepository()->getUserService();
        $contentType = $this->createUserContentTypeWithPasswordExpirationDate(
            self::EXAMPLE_PASSWORD_TTL,
            self::EXAMPLE_PASSWORD_TTL_WARNING
        );

        $user = $this->createTestUser($contentType);

        /* BEGIN: Use Case */
        $passwordInfo = $userService->getPasswordInfo($user);
        /* END: Use Case */

        $passwordUpdatedAt = $user->passwordUpdatedAt;
        if ($passwordUpdatedAt instanceof DateTime) {
            $passwordUpdatedAt = DateTimeImmutable::createFromFormat(DateTime::ATOM, $passwordUpdatedAt->format(DateTime::ATOM));
        }

        $expectedPasswordExpirationDate = $passwordUpdatedAt->add(
            new DateInterval(sprintf('P%dD', self::EXAMPLE_PASSWORD_TTL))
        );

        $expectedPasswordExpirationWarningDate = $passwordUpdatedAt->add(
            new DateInterval(sprintf('P%dD', self::EXAMPLE_PASSWORD_TTL - self::EXAMPLE_PASSWORD_TTL_WARNING))
        );

        self::assertEquals(new PasswordInfo(
            $expectedPasswordExpirationDate,
            $expectedPasswordExpirationWarningDate
        ), $passwordInfo);
    }

    public function testGetPasswordInfoIfExpirationIsDisabled(): void
    {
        $userService = $this->getRepository()->getUserService();
        $contentType = $this->createUserContentTypeWithPasswordExpirationDate(null, null);

        $user = $this->createTestUser($contentType);

        /* BEGIN: Use Case */
        $passwordInfo = $userService->getPasswordInfo($user);
        /* END: Use Case */

        self::assertEquals(new PasswordInfo(), $passwordInfo);
    }

    public function testGetPasswordInfoIfExpirationWarningIsDisabled(): void
    {
        $userService = $this->getRepository()->getUserService();
        $contentType = $this->createUserContentTypeWithPasswordExpirationDate(self::EXAMPLE_PASSWORD_TTL, null);

        $user = $this->createTestUser($contentType);

        /* BEGIN: Use Case */
        $passwordInfo = $userService->getPasswordInfo($user);
        /* END: Use Case */

        $passwordUpdatedAt = $user->passwordUpdatedAt;
        if ($passwordUpdatedAt instanceof DateTime) {
            $passwordUpdatedAt = DateTimeImmutable::createFromFormat(DateTime::ATOM, $passwordUpdatedAt->format(DateTime::ATOM));
        }

        $expectedPasswordExpirationDate = $passwordUpdatedAt->add(
            new DateInterval(sprintf('P%dD', self::EXAMPLE_PASSWORD_TTL))
        );

        self::assertEquals(new PasswordInfo($expectedPasswordExpirationDate, null), $passwordInfo);
    }

    public function createTestUser(ContentType $contentType): User
    {
        return $this->createTestUserWithPassword(self::EXAMPLE_PASSWORD, $contentType);
    }

    /**
     * Creates a user with given password.
     *
     * @param string $password
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType $contentType
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    private function createTestUserWithPassword(string $password, ContentType $contentType): User
    {
        $userService = $this->getRepository()->getUserService();
        // ID of the "Editors" user group in an Ibexa demo installation
        $editorsGroupId = 13;

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            'johndoe',
            'johndoe@example.com',
            $password,
            'eng-US',
            $contentType
        );
        $userCreate->enabled = true;
        $userCreate->setField('first_name', 'John');
        $userCreate->setField('last_name', 'Doe');

        return $userService->createUser($userCreate, [
            $userService->loadUserGroup($editorsGroupId),
        ]);
    }

    /**
     * Creates the User content type with password constraints.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     */
    private function createUserContentTypeWithStrongPassword(): ContentType
    {
        return $this->createUserContentTypeWithAccountSettings('user-with-strong-password', null, [
            'PasswordValueValidator' => [
                'requireAtLeastOneUpperCaseCharacter' => 1,
                'requireAtLeastOneLowerCaseCharacter' => 1,
                'requireAtLeastOneNumericCharacter' => 1,
                'requireAtLeastOneNonAlphanumericCharacter' => 1,
                'requireNewPassword' => 1,
                'minLength' => 8,
            ],
        ]);
    }

    private function createUserContentTypeWithPasswordExpirationDate(
        ?int $passwordTTL = self::EXAMPLE_PASSWORD_TTL,
        ?int $passwordTTLWarning = self::EXAMPLE_PASSWORD_TTL_WARNING
    ): ContentType {
        return $this->createUserContentTypeWithAccountSettings('password-expiration', [
            'PasswordTTL' => $passwordTTL,
            'PasswordTTLWarning' => $passwordTTLWarning,
        ]);
    }

    private function createUserContentTypeWithAccountSettings(
        string $identifier,
        ?array $fieldSetting = null,
        ?array $validatorConfiguration = null
    ): ContentType {
        $repository = $this->getRepository();

        $contentTypeService = $repository->getContentTypeService();
        $permissionResolver = $repository->getPermissionResolver();

        $typeCreate = $contentTypeService->newContentTypeCreateStruct($identifier);
        $typeCreate->mainLanguageCode = 'eng-GB';
        $typeCreate->urlAliasSchema = 'url|scheme';
        $typeCreate->nameSchema = 'name|scheme';
        $typeCreate->names = [
            'eng-GB' => 'User: ' . $identifier,
        ];
        $typeCreate->descriptions = [
            'eng-GB' => '',
        ];
        $typeCreate->creatorId = $this->generateId('user', $permissionResolver->getCurrentUserReference()->getUserId());
        $typeCreate->creationDate = $this->createDateTime();

        $firstNameFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('first_name', 'ibexa_string');
        $firstNameFieldCreate->names = [
            'eng-GB' => 'First name',
        ];
        $firstNameFieldCreate->descriptions = [
            'eng-GB' => '',
        ];
        $firstNameFieldCreate->fieldGroup = 'default';
        $firstNameFieldCreate->position = 1;
        $firstNameFieldCreate->isTranslatable = false;
        $firstNameFieldCreate->isRequired = true;
        $firstNameFieldCreate->isInfoCollector = false;
        $firstNameFieldCreate->validatorConfiguration = [
            'StringLengthValidator' => [
                'minStringLength' => 0,
                'maxStringLength' => 0,
            ],
        ];
        $firstNameFieldCreate->fieldSettings = [];
        $firstNameFieldCreate->isSearchable = true;
        $firstNameFieldCreate->defaultValue = '';

        $typeCreate->addFieldDefinition($firstNameFieldCreate);

        $lastNameFieldCreate = $contentTypeService->newFieldDefinitionCreateStruct('last_name', 'ibexa_string');
        $lastNameFieldCreate->names = [
            'eng-GB' => 'Last name',
        ];
        $lastNameFieldCreate->descriptions = [
            'eng-GB' => '',
        ];
        $lastNameFieldCreate->fieldGroup = 'default';
        $lastNameFieldCreate->position = 2;
        $lastNameFieldCreate->isTranslatable = false;
        $lastNameFieldCreate->isRequired = true;
        $lastNameFieldCreate->isInfoCollector = false;
        $lastNameFieldCreate->validatorConfiguration = [
            'StringLengthValidator' => [
                'minStringLength' => 0,
                'maxStringLength' => 0,
            ],
        ];
        $lastNameFieldCreate->fieldSettings = [];
        $lastNameFieldCreate->isSearchable = true;
        $lastNameFieldCreate->defaultValue = '';

        $typeCreate->addFieldDefinition($lastNameFieldCreate);

        $accountFieldCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct('user_account', 'ibexa_user');
        $accountFieldCreateStruct->names = [
            'eng-GB' => 'User account',
        ];
        $accountFieldCreateStruct->descriptions = [
            'eng-GB' => '',
        ];
        $accountFieldCreateStruct->fieldGroup = 'default';
        $accountFieldCreateStruct->position = 3;
        $accountFieldCreateStruct->isTranslatable = false;
        $accountFieldCreateStruct->isRequired = true;
        $accountFieldCreateStruct->isInfoCollector = false;
        $accountFieldCreateStruct->validatorConfiguration = $validatorConfiguration;
        $accountFieldCreateStruct->fieldSettings = $fieldSetting;
        $accountFieldCreateStruct->isSearchable = false;
        $accountFieldCreateStruct->defaultValue = null;

        $typeCreate->addFieldDefinition($accountFieldCreateStruct);

        $contentTypeDraft = $contentTypeService->createContentType($typeCreate, [
            $contentTypeService->loadContentTypeGroupByIdentifier('Users'),
        ]);
        $contentTypeService->publishContentTypeDraft($contentTypeDraft);

        return $contentTypeService->loadContentTypeByIdentifier($identifier);
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     * @throws \ErrorException
     */
    protected function updateRawPasswordHash(int $userId, int $newHashType): void
    {
        $connection = $this->getRawDatabaseConnection();
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder
            ->update(Gateway::USER_TABLE)
            ->set('password_hash_type', ':wrong_hash_type')
            ->where('contentobject_id = :user_id')
            ->setParameter('wrong_hash_type', $newHashType, ParameterType::INTEGER)
            ->setParameter('user_id', $userId);

        $queryBuilder->executeStatement();
    }

    private function assertIsSameUser(User $expectedUser, User $actualUser): void
    {
        self::assertSame($expectedUser->getUserId(), $actualUser->getUserId());
        self::assertSame($expectedUser->getName(), $actualUser->getName());
        self::assertSame($expectedUser->login, $actualUser->login);
        self::assertSame($expectedUser->email, $actualUser->email);
    }
}
