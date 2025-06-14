<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Contracts\Core\Repository\Decorator;

use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordInfo;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordValidationContext;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;

abstract class UserServiceDecorator implements UserService
{
    protected UserService $innerService;

    public function __construct(UserService $innerService)
    {
        $this->innerService = $innerService;
    }

    public function createUserGroup(
        UserGroupCreateStruct $userGroupCreateStruct,
        UserGroup $parentGroup
    ): UserGroup {
        return $this->innerService->createUserGroup($userGroupCreateStruct, $parentGroup);
    }

    public function loadUserGroup(
        int $id,
        array $prioritizedLanguages = []
    ): UserGroup {
        return $this->innerService->loadUserGroup($id, $prioritizedLanguages);
    }

    public function loadUserGroupByRemoteId(
        string $remoteId,
        array $prioritizedLanguages = []
    ): UserGroup {
        return $this->innerService->loadUserGroupByRemoteId($remoteId, $prioritizedLanguages);
    }

    public function loadSubUserGroups(
        UserGroup $userGroup,
        int $offset = 0,
        int $limit = 25,
        array $prioritizedLanguages = []
    ): iterable {
        return $this->innerService->loadSubUserGroups($userGroup, $offset, $limit, $prioritizedLanguages);
    }

    public function deleteUserGroup(UserGroup $userGroup): array
    {
        return $this->innerService->deleteUserGroup($userGroup);
    }

    public function moveUserGroup(
        UserGroup $userGroup,
        UserGroup $newParent
    ): void {
        $this->innerService->moveUserGroup($userGroup, $newParent);
    }

    public function updateUserGroup(
        UserGroup $userGroup,
        UserGroupUpdateStruct $userGroupUpdateStruct
    ): UserGroup {
        return $this->innerService->updateUserGroup($userGroup, $userGroupUpdateStruct);
    }

    public function createUser(
        UserCreateStruct $userCreateStruct,
        array $parentGroups
    ): User {
        return $this->innerService->createUser($userCreateStruct, $parentGroups);
    }

    public function loadUser(
        int $userId,
        array $prioritizedLanguages = []
    ): User {
        return $this->innerService->loadUser($userId, $prioritizedLanguages);
    }

    public function checkUserCredentials(
        User $user,
        #[\SensitiveParameter]
        string $credentials
    ): bool {
        return $this->innerService->checkUserCredentials($user, $credentials);
    }

    public function loadUserByLogin(
        string $login,
        array $prioritizedLanguages = []
    ): User {
        return $this->innerService->loadUserByLogin($login, $prioritizedLanguages);
    }

    public function loadUserByEmail(
        string $email,
        array $prioritizedLanguages = []
    ): User {
        return $this->innerService->loadUserByEmail($email, $prioritizedLanguages);
    }

    public function loadUsersByEmail(
        string $email,
        array $prioritizedLanguages = []
    ): iterable {
        return $this->innerService->loadUsersByEmail($email, $prioritizedLanguages);
    }

    public function loadUserByToken(
        string $hash,
        array $prioritizedLanguages = []
    ): User {
        return $this->innerService->loadUserByToken($hash, $prioritizedLanguages);
    }

    public function deleteUser(User $user): array
    {
        return $this->innerService->deleteUser($user);
    }

    public function updateUser(
        User $user,
        UserUpdateStruct $userUpdateStruct
    ): User {
        return $this->innerService->updateUser($user, $userUpdateStruct);
    }

    public function updateUserPassword(
        User $user,
        string $newPassword
    ): User {
        return $this->innerService->updateUserPassword($user, $newPassword);
    }

    public function updateUserToken(
        User $user,
        UserTokenUpdateStruct $userTokenUpdateStruct
    ): User {
        return $this->innerService->updateUserToken($user, $userTokenUpdateStruct);
    }

    public function expireUserToken(string $hash): void
    {
        $this->innerService->expireUserToken($hash);
    }

    public function assignUserToUserGroup(
        User $user,
        UserGroup $userGroup
    ): void {
        $this->innerService->assignUserToUserGroup($user, $userGroup);
    }

    public function unAssignUserFromUserGroup(
        User $user,
        UserGroup $userGroup
    ): void {
        $this->innerService->unAssignUserFromUserGroup($user, $userGroup);
    }

    public function loadUserGroupsOfUser(
        User $user,
        int $offset = 0,
        int $limit = 25,
        array $prioritizedLanguages = []
    ): iterable {
        return $this->innerService->loadUserGroupsOfUser($user, $offset, $limit, $prioritizedLanguages);
    }

    public function loadUsersOfUserGroup(
        UserGroup $userGroup,
        int $offset = 0,
        int $limit = 25,
        array $prioritizedLanguages = []
    ): iterable {
        return $this->innerService->loadUsersOfUserGroup($userGroup, $offset, $limit, $prioritizedLanguages);
    }

    public function isUser(Content $content): bool
    {
        return $this->innerService->isUser($content);
    }

    public function isUserGroup(Content $content): bool
    {
        return $this->innerService->isUserGroup($content);
    }

    public function newUserCreateStruct(
        string $login,
        string $email,
        string $password,
        string $mainLanguageCode,
        ?ContentType $contentType = null
    ): UserCreateStruct {
        return $this->innerService->newUserCreateStruct($login, $email, $password, $mainLanguageCode, $contentType);
    }

    public function newUserGroupCreateStruct(
        string $mainLanguageCode,
        ?ContentType $contentType = null
    ): UserGroupCreateStruct {
        return $this->innerService->newUserGroupCreateStruct($mainLanguageCode, $contentType);
    }

    public function newUserUpdateStruct(): UserUpdateStruct
    {
        return $this->innerService->newUserUpdateStruct();
    }

    public function newUserGroupUpdateStruct(): UserGroupUpdateStruct
    {
        return $this->innerService->newUserGroupUpdateStruct();
    }

    public function validatePassword(
        string $password,
        PasswordValidationContext $context = null
    ): array {
        return $this->innerService->validatePassword($password, $context);
    }

    public function getPasswordInfo(User $user): PasswordInfo
    {
        return $this->innerService->getPasswordInfo($user);
    }
}
