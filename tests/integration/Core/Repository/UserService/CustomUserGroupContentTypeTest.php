<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\UserService;

use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\Filter\Filter;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Repository\UserService;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use Ibexa\Tests\Integration\Core\TestKernel;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * User groups based on a content type listed in `user_group_content_type_identifier`
 * (configured in {@see TestKernel}) are treated as user groups.
 */
#[CoversClass(UserService::class)]
final class CustomUserGroupContentTypeTest extends RepositoryTestCase
{
    private const string CUSTOM_USER_GROUP_IDENTIFIER = 'custom_user_group';
    private const string MAIN_USER_GROUP_REMOTE_ID = 'f5c88a2209584891056f987fd965b0ba';

    private UserGroup $parentGroup;

    private UserGroup $customGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $userService = $this->getIbexaTestCore()->getUserService();
        $this->parentGroup = $userService->loadUserGroupByRemoteId(self::MAIN_USER_GROUP_REMOTE_ID);

        $createStruct = $userService->newUserGroupCreateStruct('eng-GB', $this->createCustomUserGroupContentType());
        $createStruct->setField('name', 'Custom group');
        $this->customGroup = $userService->createUserGroup($createStruct, $this->parentGroup);
    }

    public function testIsUserGroup(): void
    {
        $userService = $this->getIbexaTestCore()->getUserService();

        self::assertTrue($userService->isUserGroup($this->customGroup));
        self::assertFalse($userService->isUserGroup($this->createUser('custom_member', 'Custom', 'Member')));
    }

    public function testLoadSubUserGroups(): void
    {
        $subGroups = $this->getIbexaTestCore()->getUserService()->loadSubUserGroups($this->parentGroup, 0, -1);

        self::assertContains($this->customGroup->id, array_map(static fn (UserGroup $group): int => $group->id, $subGroups));
    }

    public function testLoadUserGroupsOfUser(): void
    {
        $user = $this->createUser('custom_member', 'Custom', 'Member', $this->customGroup);

        $userGroups = $this->getIbexaTestCore()->getUserService()->loadUserGroupsOfUser($user);

        self::assertSame([$this->customGroup->id], array_map(static fn (UserGroup $group): int => $group->id, $userGroups));
    }

    public function testFindUserGroups(): void
    {
        $userGroupList = $this->getIbexaTestCore()->getUserService()->findUserGroups(new Filter());

        $ids = [];
        foreach ($userGroupList as $userGroup) {
            $ids[] = $userGroup->id;
        }

        self::assertContains($this->customGroup->id, $ids);
    }

    private function createCustomUserGroupContentType(): ContentType
    {
        $contentTypeService = $this->getIbexaTestCore()->getContentTypeService();

        $createStruct = $contentTypeService->newContentTypeCreateStruct(self::CUSTOM_USER_GROUP_IDENTIFIER);
        $createStruct->mainLanguageCode = 'eng-GB';
        $createStruct->names = ['eng-GB' => 'Custom user group'];
        $createStruct->isContainer = true;
        $createStruct->addFieldDefinition(
            $contentTypeService->newFieldDefinitionCreateStruct('name', 'ibexa_string')
        );

        $contentTypeService->publishContentTypeDraft(
            $contentTypeService->createContentType(
                $createStruct,
                [$contentTypeService->loadContentTypeGroupByIdentifier('Users')]
            )
        );

        return $contentTypeService->loadContentTypeByIdentifier(self::CUSTOM_USER_GROUP_IDENTIFIER);
    }
}
