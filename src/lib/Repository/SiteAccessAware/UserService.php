<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository\SiteAccessAware;

use Ibexa\Contracts\Core\Repository\LanguageResolver;
use Ibexa\Contracts\Core\Repository\UserService as UserServiceInterface;
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

/**
 * UserService for SiteAccessAware layer.
 *
 * Currently does nothing but hand over calls to aggregated service.
 */
class UserService implements UserServiceInterface
{
    /** @var \Ibexa\Contracts\Core\Repository\UserService */
    protected $service;

    /** @var \Ibexa\Contracts\Core\Repository\LanguageResolver */
    protected $languageResolver;

    /**
     * Construct service object from aggregated service.
     *
     * @param \Ibexa\Contracts\Core\Repository\UserService $service
     * @param \Ibexa\Contracts\Core\Repository\LanguageResolver $languageResolver
     */
    public function __construct(
        UserServiceInterface $service,
        LanguageResolver $languageResolver
    ) {
        $this->service = $service;
        $this->languageResolver = $languageResolver;
    }

    public function createUserGroup(UserGroupCreateStruct $userGroupCreateStruct, UserGroup $parentGroup): UserGroup
    {
        return $this->service->createUserGroup($userGroupCreateStruct, $parentGroup);
    }

    public function loadUserGroup(int $id, array $prioritizedLanguages = null): UserGroup
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUserGroup($id, $prioritizedLanguages);
    }

    public function loadUserGroupByRemoteId(string $remoteId, array $prioritizedLanguages = []): UserGroup
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUserGroupByRemoteId($remoteId, $prioritizedLanguages);
    }

    public function loadSubUserGroups(UserGroup $userGroup, int $offset = 0, int $limit = 25, array $prioritizedLanguages = null): iterable
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadSubUserGroups($userGroup, $offset, $limit, $prioritizedLanguages);
    }

    public function deleteUserGroup(UserGroup $userGroup): array
    {
        return $this->service->deleteUserGroup($userGroup);
    }

    public function moveUserGroup(UserGroup $userGroup, UserGroup $newParent): void
    {
        $this->service->moveUserGroup($userGroup, $newParent);
    }

    public function updateUserGroup(UserGroup $userGroup, UserGroupUpdateStruct $userGroupUpdateStruct): UserGroup
    {
        return $this->service->updateUserGroup($userGroup, $userGroupUpdateStruct);
    }

    public function createUser(UserCreateStruct $userCreateStruct, array $parentGroups): User
    {
        return $this->service->createUser($userCreateStruct, $parentGroups);
    }

    public function loadUser(int $userId, array $prioritizedLanguages = null): User
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUser($userId, $prioritizedLanguages);
    }

    public function checkUserCredentials(
        User $user,
        #[\SensitiveParameter]
        string $credentials
    ): bool {
        return $this->service->checkUserCredentials($user, $credentials);
    }

    public function loadUserByLogin(string $login, array $prioritizedLanguages = null): User
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUserByLogin($login, $prioritizedLanguages);
    }

    public function loadUserByEmail(string $email, array $prioritizedLanguages = null): User
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUserByEmail($email, $prioritizedLanguages);
    }

    public function loadUsersByEmail(string $email, array $prioritizedLanguages = null): array
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUsersByEmail($email, $prioritizedLanguages);
    }

    public function deleteUser(User $user): array
    {
        return $this->service->deleteUser($user);
    }

    public function updateUser(User $user, UserUpdateStruct $userUpdateStruct): User
    {
        return $this->service->updateUser($user, $userUpdateStruct);
    }

    public function updateUserPassword(User $user, string $newPassword): User
    {
        return $this->service->updateUserPassword($user, $newPassword);
    }

    public function assignUserToUserGroup(User $user, UserGroup $userGroup): void
    {
        $this->service->assignUserToUserGroup($user, $userGroup);
    }

    public function unAssignUserFromUserGroup(User $user, UserGroup $userGroup): void
    {
        $this->service->unAssignUserFromUserGroup($user, $userGroup);
    }

    public function loadUserGroupsOfUser(User $user, int $offset = 0, int $limit = 25, array $prioritizedLanguages = null): iterable
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUserGroupsOfUser($user, $offset, $limit, $prioritizedLanguages);
    }

    public function loadUsersOfUserGroup(UserGroup $userGroup, int $offset = 0, int $limit = 25, array $prioritizedLanguages = null): iterable
    {
        $prioritizedLanguages = $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages);

        return $this->service->loadUsersOfUserGroup($userGroup, $offset, $limit, $prioritizedLanguages);
    }

    public function loadUserByToken(string $hash, array $prioritizedLanguages = null): User
    {
        return $this->service->loadUserByToken(
            $hash,
            $this->languageResolver->getPrioritizedLanguages($prioritizedLanguages)
        );
    }

    public function updateUserToken(User $user, UserTokenUpdateStruct $userTokenUpdateStruct): User
    {
        return $this->service->updateUserToken($user, $userTokenUpdateStruct);
    }

    public function expireUserToken(string $hash): void
    {
        $this->service->expireUserToken($hash);
    }

    public function isUser(Content $content): bool
    {
        return $this->service->isUser($content);
    }

    public function isUserGroup(Content $content): bool
    {
        return $this->service->isUserGroup($content);
    }

    public function newUserCreateStruct(string $login, string $email, string $password, string $mainLanguageCode, ?ContentType $contentType = null): UserCreateStruct
    {
        return $this->service->newUserCreateStruct($login, $email, $password, $mainLanguageCode, $contentType);
    }

    public function newUserGroupCreateStruct(string $mainLanguageCode, ?ContentType $contentType = null): UserGroupCreateStruct
    {
        return $this->service->newUserGroupCreateStruct($mainLanguageCode, $contentType);
    }

    public function newUserUpdateStruct(): UserUpdateStruct
    {
        return $this->service->newUserUpdateStruct();
    }

    public function newUserGroupUpdateStruct(): UserGroupUpdateStruct
    {
        return $this->service->newUserGroupUpdateStruct();
    }

    public function validatePassword(string $password, PasswordValidationContext $context = null): array
    {
        return $this->service->validatePassword($password, $context);
    }

    public function getPasswordInfo(User $user): PasswordInfo
    {
        return $this->service->getPasswordInfo($user);
    }
}
