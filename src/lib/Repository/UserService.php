<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\Repository;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use Ibexa\Contracts\Core\Persistence\Content\Location\Handler as LocationHandler;
use Ibexa\Contracts\Core\Persistence\User as SPIUser;
use Ibexa\Contracts\Core\Persistence\User\Handler;
use Ibexa\Contracts\Core\Persistence\User\UserTokenUpdateStruct as SPIUserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\PasswordHashService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\Repository as RepositoryInterface;
use Ibexa\Contracts\Core\Repository\UserService as UserServiceInterface;
use Ibexa\Contracts\Core\Repository\Values\Content\Content as APIContent;
use Ibexa\Contracts\Core\Repository\Values\Content\Field;
use Ibexa\Contracts\Core\Repository\Values\Content\Location;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeId as CriterionContentTypeId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier as CriterionContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LocationId as CriterionLocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\LogicalAnd as CriterionLogicalAnd;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ParentLocationId as CriterionParentLocationId;
use Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\ContentType\FieldDefinition;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordInfo;
use Ibexa\Contracts\Core\Repository\Values\User\PasswordValidationContext;
use Ibexa\Contracts\Core\Repository\Values\User\User as APIUser;
use Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct as APIUserCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup as APIUserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct as APIUserGroupCreateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct;
use Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\BadStateException;
use Ibexa\Core\Base\Exceptions\ContentFieldValidationException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\InvalidArgumentValue;
use Ibexa\Core\Base\Exceptions\MissingUserFieldTypeException;
use Ibexa\Core\Base\Exceptions\UnauthorizedException;
use Ibexa\Core\FieldType\User\Value as UserValue;
use Ibexa\Core\Repository\User\Exception\UnsupportedPasswordHashType;
use Ibexa\Core\Repository\User\PasswordValidatorInterface;
use Ibexa\Core\Repository\Values\User\User;
use Ibexa\Core\Repository\Values\User\UserCreateStruct;
use Ibexa\Core\Repository\Values\User\UserGroup;
use Ibexa\Core\Repository\Values\User\UserGroupCreateStruct;
use Psr\Log\LoggerInterface;

/**
 * This service provides methods for managing users and user groups.
 */
class UserService implements UserServiceInterface
{
    private const USER_FIELD_TYPE_NAME = 'ibexa_user';

    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    protected $repository;

    /** @var \Ibexa\Contracts\Core\Persistence\User\Handler */
    protected $userHandler;

    /** @var \Ibexa\Contracts\Core\Persistence\Content\Location\Handler */
    private $locationHandler;

    /** @var array */
    protected $settings;

    /** @var \Psr\Log\LoggerInterface|null */
    protected $logger;

    /** @var \Ibexa\Contracts\Core\Repository\PermissionResolver */
    private $permissionResolver;

    /** @var \Ibexa\Contracts\Core\Repository\PasswordHashService */
    private $passwordHashService;

    /** @var \Ibexa\Core\Repository\User\PasswordValidatorInterface */
    private $passwordValidator;

    private ConfigResolverInterface $configResolver;

    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Setups service with reference to repository object that created it & corresponding handler.
     */
    public function __construct(
        RepositoryInterface $repository,
        PermissionResolver $permissionResolver,
        Handler $userHandler,
        LocationHandler $locationHandler,
        PasswordHashService $passwordHashGenerator,
        PasswordValidatorInterface $passwordValidator,
        ConfigResolverInterface $configResolver,
        array $settings = []
    ) {
        $this->repository = $repository;
        $this->permissionResolver = $permissionResolver;
        $this->userHandler = $userHandler;
        $this->locationHandler = $locationHandler;
        // Union makes sure default settings are ignored if provided in argument
        $this->settings = $settings + [
            'defaultUserPlacement' => 12,
            'userClassID' => 4, // @deprecated, use `user_content_type_identifier` configuration instead
            'userGroupClassID' => 3,
            'hashType' => $passwordHashGenerator->getDefaultHashType(),
            'siteName' => 'ibexa.co',
        ];
        $this->passwordHashService = $passwordHashGenerator;
        $this->passwordValidator = $passwordValidator;
        $this->configResolver = $configResolver;
    }

    /**
     * Creates a new user group using the data provided in the ContentCreateStruct parameter.
     *
     * In 4.x in the content type parameter in the profile is ignored
     * - the content type is determined via configuration and can be set to null.
     * The returned version is published.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct $userGroupCreateStruct a structure for setting all necessary data to create this user group
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $parentGroup
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a user group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the input structure has invalid data
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $userGroupCreateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is missing or set to an empty value
     */
    public function createUserGroup(APIUserGroupCreateStruct $userGroupCreateStruct, APIUserGroup $parentGroup): APIUserGroup
    {
        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();

        $loadedParentGroup = $this->loadUserGroup($parentGroup->id);

        if ($loadedParentGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            throw new InvalidArgumentException('parentGroup', 'parent User Group has no main Location');
        }

        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $loadedParentGroup->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $this->repository->beginTransaction();
        try {
            $contentDraft = $contentService->createContent($userGroupCreateStruct, [$locationCreateStruct]);
            $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildDomainUserGroupObject($publishedContent);
    }

    /**
     * Loads a user group for the given id.
     *
     * @param mixed $id
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a user group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if the user group with the given id was not found
     */
    public function loadUserGroup(int $id, array $prioritizedLanguages = []): APIUserGroup
    {
        $content = $this->repository->getContentService()->loadContent($id, $prioritizedLanguages);

        return $this->buildDomainUserGroupObject($content);
    }

    public function loadUserGroupByRemoteId(string $remoteId, array $prioritizedLanguages = []): APIUserGroup
    {
        $content = $this->repository->getContentService()->loadContentByRemoteId($remoteId, $prioritizedLanguages);

        return $this->buildDomainUserGroupObject($content);
    }

    /**
     * Loads the sub groups of a user group.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     * @param int $offset the start offset for paging
     * @param int $limit the number of user groups returned
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read the user group
     */
    public function loadSubUserGroups(APIUserGroup $userGroup, int $offset = 0, int $limit = 25, array $prioritizedLanguages = []): iterable
    {
        $locationService = $this->repository->getLocationService();

        $loadedUserGroup = $this->loadUserGroup($userGroup->id);
        if (!$this->permissionResolver->canUser('content', 'read', $loadedUserGroup)) {
            throw new UnauthorizedException('content', 'read');
        }

        if ($loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            return [];
        }

        $mainGroupLocation = $locationService->loadLocation(
            $loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $searchResult = $this->searchSubGroups($mainGroupLocation, $offset, $limit);
        if ($searchResult->totalCount == 0) {
            return [];
        }

        $subUserGroups = [];
        foreach ($searchResult->searchHits as $searchHit) {
            $subUserGroups[] = $this->buildDomainUserGroupObject(
                $this->repository->getContentService()->internalLoadContentById(
                    $searchHit->valueObject->contentInfo->id,
                    $prioritizedLanguages
                )
            );
        }

        return $subUserGroups;
    }

    /**
     * Returns (searches) subgroups of a user group described by its main location.
     *
     * @phpstan-return \Ibexa\Contracts\Core\Repository\Values\Content\Search\SearchResult<\Ibexa\Contracts\Core\Repository\Values\Content\Location>
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidCriterionArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotImplementedException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    protected function searchSubGroups(Location $location, int $offset = 0, int $limit = 25): SearchResult
    {
        $searchQuery = new LocationQuery();

        $searchQuery->offset = $offset;
        $searchQuery->limit = $limit;

        $searchQuery->filter = new CriterionLogicalAnd([
            new CriterionContentTypeId($this->settings['userGroupClassID']),
            new CriterionParentLocationId($location->id),
        ]);

        $searchQuery->sortClauses = $location->getSortClauses();

        return $this->repository->getSearchService()->findLocations($searchQuery, [], false);
    }

    /**
     * Removes a user group.
     *
     * the users which are not assigned to other groups will be deleted.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to create a user group
     */
    public function deleteUserGroup(APIUserGroup $userGroup): array
    {
        $loadedUserGroup = $this->loadUserGroup($userGroup->id);

        $this->repository->beginTransaction();
        try {
            foreach ($this->userHandler->loadRoleAssignmentsByGroupId($userGroup->id) as $roleAssignment) {
                $this->userHandler->removeRoleAssignment($roleAssignment->id);
            }
            //@todo: what happens to sub user groups and users below sub user groups
            $affectedLocationIds = $this->repository->getContentService()->deleteContent(
                $loadedUserGroup->getVersionInfo()->getContentInfo()
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $affectedLocationIds;
    }

    /**
     * Moves the user group to another parent.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $newParent
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to move the user group
     */
    public function moveUserGroup(APIUserGroup $userGroup, APIUserGroup $newParent): void
    {
        $loadedUserGroup = $this->loadUserGroup($userGroup->id);
        $loadedNewParent = $this->loadUserGroup($newParent->id);

        $locationService = $this->repository->getLocationService();

        if ($loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            throw new BadStateException('userGroup', 'existing User Group is not stored and/or does not have any Location yet');
        }

        if ($loadedNewParent->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            throw new BadStateException('newParent', 'new User Group is not stored and/or does not have any Location yet');
        }

        $userGroupMainLocation = $locationService->loadLocation(
            $loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId
        );
        $newParentMainLocation = $locationService->loadLocation(
            $loadedNewParent->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $this->repository->beginTransaction();
        try {
            $locationService->moveSubtree($userGroupMainLocation, $newParentMainLocation);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Updates the group profile with fields and meta data.
     *
     * 4.x: If the versionUpdateStruct is set in $userGroupUpdateStruct, this method internally creates a content draft, updates ts with the provided data
     * and publishes the draft. If a draft is explicitly required, the user group can be updated via the content service methods.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct $userGroupUpdateStruct
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to update the user group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $userGroupUpdateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is set empty
     */
    public function updateUserGroup(APIUserGroup $userGroup, UserGroupUpdateStruct $userGroupUpdateStruct): APIUserGroup
    {
        if ($userGroupUpdateStruct->contentUpdateStruct === null &&
            $userGroupUpdateStruct->contentMetadataUpdateStruct === null) {
            // both update structs are empty, nothing to do
            return $userGroup;
        }

        $contentService = $this->repository->getContentService();

        $loadedUserGroup = $this->loadUserGroup($userGroup->id);

        $this->repository->beginTransaction();
        try {
            $publishedContent = $loadedUserGroup;
            if ($userGroupUpdateStruct->contentUpdateStruct !== null) {
                $contentDraft = $contentService->createContentDraft($loadedUserGroup->getVersionInfo()->getContentInfo());

                $contentDraft = $contentService->updateContent(
                    $contentDraft->getVersionInfo(),
                    $userGroupUpdateStruct->contentUpdateStruct
                );

                $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());
            }

            if ($userGroupUpdateStruct->contentMetadataUpdateStruct !== null) {
                $publishedContent = $contentService->updateContentMetadata(
                    $publishedContent->getVersionInfo()->getContentInfo(),
                    $userGroupUpdateStruct->contentMetadataUpdateStruct
                );
            }

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildDomainUserGroupObject($publishedContent);
    }

    /**
     * Create a new user. The created user is published by this method.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct $userCreateStruct the data used for creating the user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[] $parentGroups the groups which are assigned to the user after creation
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to move the user group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $userCreateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is missing or set to an empty value
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if a user with provided login already exists
     */
    public function createUser(APIUserCreateStruct $userCreateStruct, array $parentGroups): APIUser
    {
        $contentService = $this->repository->getContentService();
        $locationService = $this->repository->getLocationService();

        $locationCreateStructs = [];
        foreach ($parentGroups as $parentGroup) {
            $parentGroup = $this->loadUserGroup($parentGroup->id);
            if ($parentGroup->getVersionInfo()->getContentInfo()->mainLocationId !== null) {
                $locationCreateStructs[] = $locationService->newLocationCreateStruct(
                    $parentGroup->getVersionInfo()->getContentInfo()->mainLocationId
                );
            }
        }

        // Search for the first ibexa_user field type in content type
        $userFieldDefinition = $this->getUserFieldDefinition($userCreateStruct->contentType);
        if ($userFieldDefinition === null) {
            throw new MissingUserFieldTypeException($userCreateStruct->contentType, self::USER_FIELD_TYPE_NAME);
        }

        $this->repository->beginTransaction();
        try {
            $contentDraft = $contentService->createContent($userCreateStruct, $locationCreateStructs);
            // There is no need to create user separately, just load it from SPI
            $spiUser = $this->userHandler->load($contentDraft->id);
            $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());

            // User\Handler::create call is currently used to clear cache only
            $this->userHandler->create(
                new SPIUser(
                    [
                        'id' => $spiUser->id,
                        'login' => $spiUser->login,
                        'email' => $spiUser->email,
                    ]
                )
            );

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->buildDomainUserObject($spiUser, $publishedContent);
    }

    /**
     * Loads a user.
     *
     * @param int $userId
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if a user with the given id was not found
     */
    public function loadUser(int $userId, array $prioritizedLanguages = []): APIUser
    {
        /** @var \Ibexa\Contracts\Core\Repository\Values\Content\Content $content */
        $content = $this->repository->getContentService()->internalLoadContentById($userId, $prioritizedLanguages);
        // Get spiUser value from Field Value
        foreach ($content->getFields() as $field) {
            $fieldValue = $field->getValue();
            if (!$fieldValue instanceof UserValue) {
                continue;
            }

            $value = $fieldValue;
            $spiUser = new SPIUser();
            $spiUser->id = $value->contentId;
            $spiUser->login = $value->login;
            $spiUser->email = $value->email;
            $spiUser->hashAlgorithm = $value->passwordHashType;
            $spiUser->passwordHash = $value->passwordHash;
            $spiUser->passwordUpdatedAt = $value->passwordUpdatedAt ? $value->passwordUpdatedAt->getTimestamp() : null;
            $spiUser->isEnabled = $value->enabled;
            $spiUser->maxLogin = $value->maxLogin;
            break;
        }

        // If for some reason not found, load it
        if (!isset($spiUser)) {
            $spiUser = $this->userHandler->load($userId);
        }

        return $this->buildDomainUserObject($spiUser, $content);
    }

    /**
     * Checks if credentials are valid for provided User.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param string $credentials
     *
     * @return bool
     */
    public function checkUserCredentials(
        APIUser $user,
        #[\SensitiveParameter]
        string $credentials
    ): bool {
        return $this->comparePasswordHashForAPIUser($user, $credentials);
    }

    /**
     * Loads a user for the given login.
     *
     * {@inheritdoc}
     *
     * @param string $login
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException if a user with the given credentials was not found
     */
    public function loadUserByLogin(string $login, array $prioritizedLanguages = []): APIUser
    {
        if (empty($login)) {
            throw new InvalidArgumentValue('login', $login);
        }

        $spiUser = $this->userHandler->loadByLogin($login);

        return $this->buildDomainUserObject($spiUser, null, $prioritizedLanguages);
    }

    /**
     * Loads a user for the given email.
     *
     * {@inheritdoc}
     *
     * @param string $email
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function loadUserByEmail(string $email, array $prioritizedLanguages = []): APIUser
    {
        if (empty($email)) {
            throw new InvalidArgumentValue('email', $email);
        }

        $spiUser = $this->userHandler->loadByEmail($email);

        return $this->buildDomainUserObject($spiUser, null, $prioritizedLanguages);
    }

    /**
     * Loads a user for the given email.
     *
     * {@inheritdoc}
     *
     * @param string $email
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User[]
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     */
    public function loadUsersByEmail(string $email, array $prioritizedLanguages = []): iterable
    {
        if (empty($email)) {
            throw new InvalidArgumentValue('email', $email);
        }

        $users = [];
        foreach ($this->userHandler->loadUsersByEmail($email) as $spiUser) {
            $users[] = $this->buildDomainUserObject($spiUser, null, $prioritizedLanguages);
        }

        return $users;
    }

    /**
     * Loads a user for the given token.
     *
     * {@inheritdoc}
     *
     * @param string $hash
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentValue
     */
    public function loadUserByToken(string $hash, array $prioritizedLanguages = []): APIUser
    {
        if (empty($hash)) {
            throw new InvalidArgumentValue('hash', $hash);
        }

        $spiUser = $this->userHandler->loadUserByToken($hash);

        return $this->buildDomainUserObject($spiUser, null, $prioritizedLanguages);
    }

    /**
     * This method deletes a user.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to delete the user
     */
    public function deleteUser(APIUser $user): array
    {
        $loadedUser = $this->loadUser($user->id);

        $this->repository->beginTransaction();
        try {
            foreach ($this->userHandler->loadRoleAssignmentsByGroupId($user->id) as $roleAssignment) {
                $this->userHandler->removeRoleAssignment($roleAssignment->id);
            }

            $affectedLocationIds = $this->repository->getContentService()->deleteContent(
                $loadedUser->getVersionInfo()->getContentInfo()
            );

            // User\Handler::delete call is currently used to clear cache only
            $this->userHandler->delete($loadedUser->id);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $affectedLocationIds ?? [];
    }

    /**
     * Updates a user.
     *
     * 4.x: If the versionUpdateStruct is set in the user update structure, this method internally creates a content draft, updates ts with the provided data
     * and publishes the draft. If a draft is explicitly required, the user group can be updated via the content service methods.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct $userUpdateStruct
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException if a field in the $userUpdateStruct is not valid
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException if a required field is set empty
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to update the user
     */
    public function updateUser(APIUser $user, UserUpdateStruct $userUpdateStruct): APIUser
    {
        $loadedUser = $this->loadUser($user->id);

        $contentService = $this->repository->getContentService();

        $canEditContent = $this->permissionResolver->canUser('content', 'edit', $loadedUser);

        if (!$canEditContent && $this->isUserProfileUpdateRequested($userUpdateStruct)) {
            throw new UnauthorizedException('content', 'edit');
        }

        $userFieldDefinition = $this->getUserFieldDefinition($loadedUser->getContentType());
        if ($userFieldDefinition === null) {
            throw new MissingUserFieldTypeException($loadedUser->getContentType(), self::USER_FIELD_TYPE_NAME);
        }

        $userUpdateStruct->contentUpdateStruct = $userUpdateStruct->contentUpdateStruct ?? $contentService->newContentUpdateStruct();

        $providedUserUpdateDataInField = false;
        foreach ($userUpdateStruct->contentUpdateStruct->fields as $field) {
            if ($field->value instanceof UserValue) {
                $providedUserUpdateDataInField = true;
                break;
            }
        }

        if (!$providedUserUpdateDataInField) {
            $userUpdateStruct->contentUpdateStruct->setField(
                $userFieldDefinition->identifier,
                new UserValue([
                    'contentId' => $loadedUser->id,
                    'hasStoredLogin' => true,
                    'login' => $loadedUser->login,
                    'email' => $userUpdateStruct->email ?? $loadedUser->email,
                    'plainPassword' => $userUpdateStruct->password,
                    'enabled' => $userUpdateStruct->enabled ?? $loadedUser->enabled,
                    'maxLogin' => $userUpdateStruct->maxLogin ?? $loadedUser->maxLogin,
                    'passwordHashType' => $user->hashAlgorithm,
                    'passwordHash' => $user->passwordHash,
                ])
            );
        }

        if (!empty($userUpdateStruct->password) &&
            !$canEditContent &&
            !$this->permissionResolver->canUser('user', 'password', $loadedUser, [$loadedUser])
        ) {
            throw new UnauthorizedException('user', 'password');
        }

        $this->repository->beginTransaction();
        try {
            $publishedContent = $loadedUser;
            if ($userUpdateStruct->contentUpdateStruct !== null) {
                $contentDraft = $contentService->createContentDraft($loadedUser->getVersionInfo()->getContentInfo());
                $contentDraft = $contentService->updateContent(
                    $contentDraft->getVersionInfo(),
                    $userUpdateStruct->contentUpdateStruct
                );
                $publishedContent = $contentService->publishVersion($contentDraft->getVersionInfo());
            }

            if ($userUpdateStruct->contentMetadataUpdateStruct !== null) {
                $contentService->updateContentMetadata(
                    $publishedContent->getVersionInfo()->getContentInfo(),
                    $userUpdateStruct->contentMetadataUpdateStruct
                );
            }

            // User\Handler::update call is currently used to clear cache only
            $this->userHandler->update(
                new SPIUser(
                    [
                        'id' => $loadedUser->id,
                        'login' => $loadedUser->login,
                        'email' => $userUpdateStruct->email ?: $loadedUser->email,
                    ]
                )
            );

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadUser($loadedUser->id);
    }

    public function updateUserPassword(
        APIUser $user,
        #[\SensitiveParameter]
        string $newPassword
    ): APIUser {
        $loadedUser = $this->loadUser($user->id);

        if (!$this->permissionResolver->canUser('content', 'edit', $loadedUser)
            && !$this->permissionResolver->canUser('user', 'password', $loadedUser)
        ) {
            throw new UnauthorizedException('user', 'password');
        }

        $userFieldDefinition = $this->getUserFieldDefinition($loadedUser->getContentType());
        if ($userFieldDefinition === null) {
            throw new MissingUserFieldTypeException($loadedUser->getContentType(), self::USER_FIELD_TYPE_NAME);
        }

        $errors = $this->passwordValidator->validatePassword(
            $newPassword,
            $userFieldDefinition,
            $user
        );
        if (!empty($errors)) {
            // Note: @deprecated this should rather throw a list wrapper of `ValidationError`s
            throw ContentFieldValidationException::createNewWithMultiline(
                // build errors array as expected by ContentFieldValidationException
                [$userFieldDefinition->id => [$userFieldDefinition->mainLanguageCode => $errors]],
                $loadedUser->getName()
            );
        }

        $passwordHashAlgorithm = (int) $loadedUser->hashAlgorithm;
        try {
            $passwordHash = $this->passwordHashService->createPasswordHash($newPassword, $passwordHashAlgorithm);
        } catch (UnsupportedPasswordHashType $e) {
            $passwordHashAlgorithm = $this->passwordHashService->getDefaultHashType();
            $passwordHash = $this->passwordHashService->createPasswordHash($newPassword, $passwordHashAlgorithm);
        }

        $this->repository->beginTransaction();
        try {
            $this->userHandler->updatePassword(
                new SPIUser(
                    [
                        'id' => $loadedUser->id,
                        'login' => $loadedUser->login,
                        'email' => $loadedUser->email,
                        'passwordHash' => $passwordHash,
                        'hashAlgorithm' => $passwordHashAlgorithm,
                        'passwordUpdatedAt' => time(),
                    ]
                )
            );

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadUser($loadedUser->id);
    }

    /**
     * Update the user token information specified by the user token struct.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserTokenUpdateStruct $userTokenUpdateStruct
     *
     * @throws \Ibexa\Core\Base\Exceptions\InvalidArgumentValue
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \RuntimeException
     * @throws \Exception
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    public function updateUserToken(APIUser $user, UserTokenUpdateStruct $userTokenUpdateStruct): APIUser
    {
        $loadedUser = $this->loadUser($user->id);

        if ($userTokenUpdateStruct->hashKey !== null && (!is_string($userTokenUpdateStruct->hashKey) || empty($userTokenUpdateStruct->hashKey))) {
            throw new InvalidArgumentValue('hashKey', $userTokenUpdateStruct->hashKey, 'UserTokenUpdateStruct');
        }

        if ($userTokenUpdateStruct->time === null) {
            throw new InvalidArgumentValue('time', $userTokenUpdateStruct->time, 'UserTokenUpdateStruct');
        }

        $this->repository->beginTransaction();
        try {
            $this->userHandler->updateUserToken(
                new SPIUserTokenUpdateStruct(
                    [
                        'userId' => $loadedUser->id,
                        'hashKey' => $userTokenUpdateStruct->hashKey,
                        'time' => $userTokenUpdateStruct->time->getTimestamp(),
                    ]
                )
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $this->loadUser($loadedUser->id);
    }

    /**
     * Expires user token with user hash.
     *
     * @param string $hash
     */
    public function expireUserToken(string $hash): void
    {
        $this->repository->beginTransaction();
        try {
            $this->userHandler->expireUserToken($hash);
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Assigns a new user group to the user.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to assign the user group to the user
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the user is already in the given user group
     */
    public function assignUserToUserGroup(APIUser $user, APIUserGroup $userGroup): void
    {
        $loadedUser = $this->loadUser($user->id);
        $loadedGroup = $this->loadUserGroup($userGroup->id);
        $locationService = $this->repository->getLocationService();

        $existingGroupIds = [];
        $userLocations = $locationService->loadLocations($loadedUser->getVersionInfo()->getContentInfo());
        foreach ($userLocations as $userLocation) {
            $existingGroupIds[] = $userLocation->parentLocationId;
        }

        if ($loadedGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            throw new BadStateException('userGroup', 'User Group has no main Location or no Locations');
        }

        if (in_array($loadedGroup->getVersionInfo()->getContentInfo()->mainLocationId, $existingGroupIds)) {
            // user is already assigned to the user group
            throw new InvalidArgumentException('user', 'User is already in the given User Group');
        }

        $locationCreateStruct = $locationService->newLocationCreateStruct(
            $loadedGroup->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $this->repository->beginTransaction();
        try {
            $locationService->createLocation(
                $loadedUser->getVersionInfo()->getContentInfo(),
                $locationCreateStruct
            );
            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }
    }

    /**
     * Removes a user group from the user.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to remove the user group from the user
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException if the user is not in the given user group
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException If $userGroup is the last assigned user group
     */
    public function unAssignUserFromUserGroup(APIUser $user, APIUserGroup $userGroup): void
    {
        $loadedUser = $this->loadUser($user->id);
        $loadedGroup = $this->loadUserGroup($userGroup->id);
        $locationService = $this->repository->getLocationService();

        $userLocations = $locationService->loadLocations($loadedUser->getVersionInfo()->getContentInfo());
        if (empty($userLocations)) {
            throw new BadStateException('user', 'User has no Locations, cannot unassign from group');
        }

        if ($loadedGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            throw new BadStateException('userGroup', 'User Group has no main Location or no Locations, cannot unassign');
        }

        foreach ($userLocations as $userLocation) {
            if ($userLocation->parentLocationId == $loadedGroup->getVersionInfo()->getContentInfo()->mainLocationId) {
                // Throw this specific BadState when we know argument is valid
                if (count($userLocations) === 1) {
                    throw new BadStateException('user', 'User only has one User Group, cannot unassign from last group');
                }

                $this->repository->beginTransaction();
                try {
                    $locationService->deleteLocation($userLocation);
                    $this->repository->commit();

                    return;
                } catch (Exception $e) {
                    $this->repository->rollback();
                    throw $e;
                }
            }
        }

        throw new InvalidArgumentException('userGroup', 'User is not in the given User Group');
    }

    /**
     * Loads the user groups the user belongs to.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed read the user or user group
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\User $user
     * @param int $offset the start offset for paging
     * @param int $limit the number of user groups returned
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup[]
     */
    public function loadUserGroupsOfUser(APIUser $user, int $offset = 0, int $limit = 25, array $prioritizedLanguages = []): iterable
    {
        $locationService = $this->repository->getLocationService();

        if (!$this->repository->getPermissionResolver()->canUser('content', 'read', $user)) {
            throw new UnauthorizedException('content', 'read');
        }

        $userLocations = $locationService->loadLocations(
            $user->getVersionInfo()->getContentInfo()
        );

        $parentLocationIds = [];
        foreach ($userLocations as $userLocation) {
            if ($userLocation->parentLocationId !== null) {
                $parentLocationIds[] = $userLocation->parentLocationId;
            }
        }

        $searchQuery = new LocationQuery();

        $searchQuery->offset = $offset;
        $searchQuery->limit = $limit;
        $searchQuery->performCount = false;

        $searchQuery->filter = new CriterionLogicalAnd(
            [
                new CriterionContentTypeId($this->settings['userGroupClassID']),
                new CriterionLocationId($parentLocationIds),
            ]
        );

        $searchResult = $this->repository->getSearchService()->findLocations($searchQuery);

        $userGroups = [];
        foreach ($searchResult->searchHits as $resultItem) {
            $userGroups[] = $this->buildDomainUserGroupObject(
                $this->repository->getContentService()->internalLoadContentById(
                    $resultItem->valueObject->contentInfo->id,
                    $prioritizedLanguages
                )
            );
        }

        return $userGroups;
    }

    /**
     * Loads the users of a user group.
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException if the authenticated user is not allowed to read the users or user group
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup $userGroup
     * @param int $offset the start offset for paging
     * @param int $limit the number of users returned
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User[]
     */
    public function loadUsersOfUserGroup(
        APIUserGroup $userGroup,
        int $offset = 0,
        int $limit = 25,
        array $prioritizedLanguages = []
    ): iterable {
        $loadedUserGroup = $this->loadUserGroup($userGroup->id);

        if ($loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId === null) {
            return [];
        }

        $mainGroupLocation = $this->repository->getLocationService()->loadLocation(
            $loadedUserGroup->getVersionInfo()->getContentInfo()->mainLocationId
        );

        $searchQuery = new LocationQuery();

        $searchQuery->filter = new CriterionLogicalAnd(
            [
                new CriterionContentTypeIdentifier($this->getUserContentTypeIdentifiers()),
                new CriterionParentLocationId($mainGroupLocation->id),
            ]
        );

        $searchQuery->offset = $offset;
        $searchQuery->limit = $limit;
        $searchQuery->performCount = false;
        $searchQuery->sortClauses = $mainGroupLocation->getSortClauses();

        $searchResult = $this->repository->getSearchService()->findLocations($searchQuery);

        $users = [];
        foreach ($searchResult->searchHits as $resultItem) {
            $users[] = $this->buildDomainUserObject(
                $this->userHandler->load($resultItem->valueObject->contentInfo->id),
                $this->repository->getContentService()->internalLoadContentById(
                    $resultItem->valueObject->contentInfo->id,
                    $prioritizedLanguages
                )
            );
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function isUser(APIContent $content): bool
    {
        // First check against config for fast check
        if (in_array(
            $content->getVersionInfo()->getContentInfo()->getContentType()->identifier,
            $this->getUserContentTypeIdentifiers(),
            true
        )) {
            return true;
        }

        // For users we ultimately need to look for ibexa_user type as content type id could be several for users.
        // And config might be different from one SA to the next, which we don't care about here.
        foreach ($content->getFields() as $field) {
            if ($field->fieldTypeIdentifier === self::USER_FIELD_TYPE_NAME) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isUserGroup(APIContent $content): bool
    {
        return $this->settings['userGroupClassID'] == $content->getVersionInfo()->getContentInfo()->contentTypeId;
    }

    /**
     * Instantiate a user create class.
     *
     * @param string $login the login of the new user
     * @param string $email the email of the new user
     * @param string $password the plain password of the new user
     * @param string $mainLanguageCode the main language for the underlying content object
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType|null $contentType content type for the underlying content item.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserCreateStruct
     */
    public function newUserCreateStruct(
        string $login,
        string $email,
        #[\SensitiveParameter]
        string $password,
        string $mainLanguageCode,
        ?ContentType $contentType = null
    ): APIUserCreateStruct {
        if ($contentType === null) {
            $userContentTypeIdentifiers = $this->getUserContentTypeIdentifiers();
            $defaultIdentifier = reset($userContentTypeIdentifiers);
            $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($defaultIdentifier);
        }

        $fieldDefIdentifier = '';
        foreach ($contentType->fieldDefinitions as $fieldDefinition) {
            if ($fieldDefinition->fieldTypeIdentifier === self::USER_FIELD_TYPE_NAME) {
                $fieldDefIdentifier = $fieldDefinition->identifier;
                break;
            }
        }

        return new UserCreateStruct(
            [
                'contentType' => $contentType,
                'mainLanguageCode' => $mainLanguageCode,
                'login' => $login,
                'email' => $email,
                'password' => $password,
                'enabled' => true,
                'fields' => [
                    new Field([
                        'fieldDefIdentifier' => $fieldDefIdentifier,
                        'languageCode' => $mainLanguageCode,
                        'fieldTypeIdentifier' => self::USER_FIELD_TYPE_NAME,
                        'value' => new UserValue([
                            'login' => $login,
                            'email' => $email,
                            'plainPassword' => $password,
                            'enabled' => true,
                            'passwordUpdatedAt' => new DateTime(),
                        ]),
                    ]),
                ],
            ]
        );
    }

    /**
     * Instantiate a user group create class.
     *
     * @param string $mainLanguageCode The main language for the underlying content object
     * @param \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType|null $contentType 5.x the content type for the underlying content item. In 4.x it is ignored and taken from the configuration
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroupCreateStruct
     */
    public function newUserGroupCreateStruct(string $mainLanguageCode, ?ContentType $contentType = null): APIUserGroupCreateStruct
    {
        if ($contentType === null) {
            $contentType = $this->repository->getContentTypeService()->loadContentType(
                $this->settings['userGroupClassID']
            );
        }

        return new UserGroupCreateStruct(
            [
                'contentType' => $contentType,
                'mainLanguageCode' => $mainLanguageCode,
                'fields' => [],
            ]
        );
    }

    /**
     * Instantiate a new user update struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct
     */
    public function newUserUpdateStruct(): UserUpdateStruct
    {
        return new UserUpdateStruct();
    }

    /**
     * Instantiate a new user group update struct.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroupUpdateStruct
     */
    public function newUserGroupUpdateStruct(): UserGroupUpdateStruct
    {
        return new UserGroupUpdateStruct();
    }

    /**
     * {@inheritdoc}
     */
    public function validatePassword(
        #[\SensitiveParameter]
        string $password,
        PasswordValidationContext $context = null
    ): array {
        if ($context === null) {
            $userContentTypeIdentifiers = $this->getUserContentTypeIdentifiers();
            $defaultIdentifier = reset($userContentTypeIdentifiers);
            $contentType = $this->repository->getContentTypeService()->loadContentTypeByIdentifier($defaultIdentifier);
            $context = new PasswordValidationContext([
                'contentType' => $contentType,
            ]);
        }

        // Search for the first ibexa_user field type in content type
        $userFieldDefinition = $this->getUserFieldDefinition($context->contentType);
        if ($userFieldDefinition === null) {
            throw new MissingUserFieldTypeException($context->contentType, self::USER_FIELD_TYPE_NAME);
        }

        return $this->passwordValidator->validatePassword(
            $password,
            $userFieldDefinition,
            $context->user
        );
    }

    /**
     * Builds the domain UserGroup object from provided Content object.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\UserGroup
     */
    protected function buildDomainUserGroupObject(APIContent $content): APIUserGroup
    {
        $locationService = $this->repository->getLocationService();

        if ($content->getVersionInfo()->getContentInfo()->mainLocationId !== null) {
            $mainLocation = $locationService->loadLocation(
                $content->getVersionInfo()->getContentInfo()->mainLocationId
            );
            $parentLocation = $this->locationHandler->load($mainLocation->parentLocationId);
        }

        return new UserGroup(
            [
                'content' => $content,
                'parentId' => $parentLocation->contentId ?? null,
            ]
        );
    }

    /**
     * Builds the domain user object from provided persistence user object.
     *
     * @param \Ibexa\Contracts\Core\Persistence\User $spiUser
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content|null $content
     * @param string[] $prioritizedLanguages Used as prioritized language code on translated properties of returned object.
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    protected function buildDomainUserObject(
        SPIUser $spiUser,
        APIContent $content = null,
        array $prioritizedLanguages = []
    ): APIUser {
        if ($content === null) {
            $content = $this->repository->getContentService()->internalLoadContentById(
                $spiUser->id,
                $prioritizedLanguages
            );
        }

        return new User(
            [
                'content' => $content,
                'login' => $spiUser->login,
                'email' => $spiUser->email,
                'passwordHash' => $spiUser->passwordHash,
                'passwordUpdatedAt' => $this->getDateTime($spiUser->passwordUpdatedAt),
                'hashAlgorithm' => (int)$spiUser->hashAlgorithm,
                'enabled' => $spiUser->isEnabled,
                'maxLogin' => (int)$spiUser->maxLogin,
            ]
        );
    }

    public function getPasswordInfo(APIUser $user): PasswordInfo
    {
        $definition = $this->getUserFieldDefinition($user->getContentType());

        return $this->passwordValidator->getPasswordInfo($user, $definition);
    }

    private function getUserFieldDefinition(ContentType $contentType): ?FieldDefinition
    {
        return $contentType->getFirstFieldDefinitionOfType(self::USER_FIELD_TYPE_NAME);
    }

    /**
     * Verifies if the provided login and password are valid for {@see \Ibexa\Contracts\Core\Persistence\User}.
     *
     * @return bool return true if the login and password are successfully validated and false, if not.
     */
    protected function comparePasswordHashForSPIUser(
        SPIUser $user,
        #[\SensitiveParameter]
        string $password
    ): bool {
        return $this->comparePasswordHashes($password, $user->passwordHash, $user->hashAlgorithm);
    }

    /**
     * Verifies if the provided login and password are valid for {@see \Ibexa\Contracts\Core\Repository\Values\User\User}.
     *
     * @return bool return true if the login and password are successfully validated and false, if not.
     */
    protected function comparePasswordHashForAPIUser(
        APIUser $user,
        #[\SensitiveParameter]
        string $password
    ): bool {
        return $this->comparePasswordHashes($password, $user->passwordHash, $user->hashAlgorithm);
    }

    /**
     * Verifies if the provided login and password are valid against given password hash and hash type.
     *
     * @param string $plainPassword User password
     * @param string $passwordHash User password hash
     * @param int $hashAlgorithm Hash type
     *
     * @return bool return true if the login and password are successfully validated and false, if not.
     */
    private function comparePasswordHashes(
        #[\SensitiveParameter]
        string $plainPassword,
        #[\SensitiveParameter]
        string $passwordHash,
        int $hashAlgorithm
    ): bool {
        return $this->passwordHashService->isValidPassword($plainPassword, $passwordHash, $hashAlgorithm);
    }

    /**
     * Return true if any of the UserUpdateStruct properties refers to User Profile (Content) update.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserUpdateStruct $userUpdateStruct
     *
     * @return bool
     */
    private function isUserProfileUpdateRequested(UserUpdateStruct $userUpdateStruct): bool
    {
        return
            !empty($userUpdateStruct->contentUpdateStruct) ||
            !empty($userUpdateStruct->contentMetadataUpdateStruct) ||
            !empty($userUpdateStruct->email) ||
            !empty($userUpdateStruct->enabled) ||
            !empty($userUpdateStruct->maxLogin);
    }

    private function getDateTime(?int $timestamp): ?DateTimeInterface
    {
        if ($timestamp !== null) {
            // Instead of using DateTime(ts) we use setTimeStamp() so timezone does not get set to UTC
            $dateTime = new DateTime();
            $dateTime->setTimestamp($timestamp);

            return DateTimeImmutable::createFromMutable($dateTime);
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function getUserContentTypeIdentifiers(): array
    {
        return $this->configResolver->getParameter('user_content_type_identifier');
    }
}
