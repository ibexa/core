<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use ArrayObject;
use DateTime;
use Doctrine\DBAL\Connection;
use ErrorException;
use Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Repository;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\Content\Language;
use Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation\SubtreeLimitation;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Repository\Values\User\UserReference;
use Ibexa\Contracts\Core\Repository\Values\ValueObject;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory;
use Ibexa\Contracts\Core\Test\Repository\SetupFactory\Legacy as LegacySetupFactory;
use Ibexa\Tests\Core\Repository\PHPUnitConstraint;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Base class for api specific tests.
 */
abstract class BaseTestCase extends TestCase
{
    /**
     * Maximum integer number accepted by the different backends.
     */
    public const DB_INT_MAX = 2147483647;

    /** @var \Ibexa\Contracts\Core\Test\Repository\SetupFactory */
    private $setupFactory;

    /** @var \Ibexa\Contracts\Core\Repository\Repository */
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();

        try {
            // Use setup factory instance here w/o clearing data in case test don't need to
            $this->getSetupFactory()->getRepository(false);
        } catch (PDOException $e) {
            self::fail(
                'The communication with the database cannot be established. ' .
                "This is required in order to perform the tests.\n\n" .
                'Exception: ' . $e
            );
        } catch (Exception $e) {
            self::fail(
                'Cannot create a repository with predefined user. ' .
                'Check the UserService or RoleService implementation. ' .
                PHP_EOL . PHP_EOL .
                'Exception: ' . $e
            );
        }
    }

    /**
     * Resets the temporary used repository between each test run.
     */
    protected function tearDown(): void
    {
        $this->repository = null;
        parent::tearDown();
    }

    /**
     * Returns the ID generator, fitting to the repository implementation.
     *
     * @return \Ibexa\Tests\Integration\Core\Repository\IdManager
     */
    protected function getIdManager()
    {
        return $this->getSetupFactory()->getIdManager();
    }

    /**
     * Generates a repository specific ID value.
     *
     * @param string $type
     * @param mixed $rawId
     *
     * @return mixed
     */
    protected function generateId($type, $rawId)
    {
        return $this->getIdManager()->generateId($type, $rawId);
    }

    /**
     * Parses a repository specific ID value.
     *
     * @param string $type
     * @param mixed $id
     *
     * @return mixed
     */
    protected function parseId($type, $id)
    {
        return $this->getIdManager()->parseId($type, $id);
    }

    /**
     * Returns a config setting provided by the setup factory.
     *
     * @param string $configKey
     *
     * @return mixed
     */
    protected function getConfigValue($configKey)
    {
        return $this->getSetupFactory()->getConfigValue($configKey);
    }

    /**
     * @param bool $initialInitializeFromScratch Only has an effect if set in first call within a test
     */
    protected function getRepository(bool $initialInitializeFromScratch = true): Repository
    {
        if (null === $this->repository) {
            try {
                $this->repository = $this->getSetupFactory()->getRepository(
                    $initialInitializeFromScratch
                );
            } catch (ErrorException $e) {
                self::fail(
                    sprintf(
                        '%s: %s in %s:%d',
                        __FUNCTION__,
                        $e->getMessage(),
                        $e->getFile(),
                        $e->getLine()
                    )
                );
            }
        }

        return $this->repository;
    }

    /**
     * @throws \ErrorException
     */
    protected function getSetupFactory(): SetupFactory
    {
        if (null === $this->setupFactory) {
            if (false === ($setupClass = getenv('setupFactory'))) {
                $setupClass = LegacySetupFactory::class;
                putenv("setupFactory=$setupClass");
            }

            if (false === getenv('fixtureDir')) {
                putenv('fixtureDir=Legacy');
            }

            if (false === class_exists($setupClass)) {
                throw new ErrorException(
                    sprintf(
                        'Environment variable "setupFactory" does not reference an existing class: %s. Did you forget to install a package dependency?',
                        $setupClass
                    )
                );
            }

            $this->setupFactory = new $setupClass();
        }

        return $this->setupFactory;
    }

    /**
     * Asserts that properties given in $expectedValues are correctly set in
     * $actualObject.
     *
     * @param mixed[] $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $actualObject
     */
    protected function assertPropertiesCorrect(array $expectedValues, ValueObject $actualObject)
    {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } elseif (is_array($propertyValue)) {
                foreach ($propertyValue as $key => $value) {
                    if ($value instanceof ValueObject) {
                        $this->assertStructPropertiesCorrect($value, $actualObject->$propertyName[$key]);
                    } else {
                        $this->assertPropertiesEqual("$propertyName\[$key\]", $value, $actualObject->$propertyName[$key]);
                    }
                }
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName);
            }
        }
    }

    /**
     * Asserts that properties given in $expectedValues are correctly set in
     * $actualObject.
     *
     * If the property type is array, it will be sorted before comparison.
     *
     * @TODO: introduced because of randomly failing tests, ref: https://issues.ibexa.co/browse/EZP-21734
     *
     * @param mixed[] $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $actualObject
     */
    protected function assertPropertiesCorrectUnsorted(array $expectedValues, ValueObject $actualObject)
    {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName, true);
            }
        }
    }

    /**
     * Asserts all properties from $expectedValues are correctly set in
     * $actualObject. Additional (virtual) properties can be asserted using
     * $additionalProperties.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $expectedValues
     * @param \Ibexa\Contracts\Core\Repository\Values\ValueObject $actualObject
     * @param array $propertyNames
     */
    protected function assertStructPropertiesCorrect(ValueObject $expectedValues, ValueObject $actualObject, array $additionalProperties = [])
    {
        foreach ($expectedValues as $propertyName => $propertyValue) {
            if ($propertyValue instanceof ValueObject) {
                $this->assertStructPropertiesCorrect($propertyValue, $actualObject->$propertyName);
            } else {
                $this->assertPropertiesEqual($propertyName, $propertyValue, $actualObject->$propertyName);
            }
        }

        foreach ($additionalProperties as $propertyName) {
            $this->assertPropertiesEqual($propertyName, $expectedValues->$propertyName, $actualObject->$propertyName);
        }
    }

    /**
     * @param array $items An array of scalar values
     *
     * @see \Ibexa\Tests\Integration\Core\Repository\BaseTestCase::assertPropertiesCorrectUnsorted
     */
    private function sortItems(array &$items)
    {
        $sorter = function ($a, $b): int {
            if (!is_scalar($a) || !is_scalar($b)) {
                $this->fail('Wrong usage: method ' . __METHOD__ . ' accepts only an array of scalar values');
            }

            return strcmp($a, $b);
        };
        usort($items, $sorter);
    }

    private function assertPropertiesEqual($propertyName, $expectedValue, $actualValue, $sortArray = false)
    {
        if ($expectedValue instanceof ArrayObject) {
            $expectedValue = $expectedValue->getArrayCopy();
        } elseif ($expectedValue instanceof DateTime) {
            $expectedValue = $expectedValue->format(DateTime::RFC850);
        }
        if ($actualValue instanceof ArrayObject) {
            $actualValue = $actualValue->getArrayCopy();
        } elseif ($actualValue instanceof DateTime) {
            $actualValue = $actualValue->format(DateTime::RFC850);
        }

        if ($sortArray && is_array($actualValue) && is_array($expectedValue)) {
            $this->sortItems($actualValue);
            $this->sortItems($expectedValue);
        }

        self::assertEquals(
            $expectedValue,
            $actualValue,
            sprintf('Object property "%s" incorrect.', $propertyName)
        );
    }

    /**
     * Create a user in editor user group.
     */
    protected function createUserVersion1(
        string $login = 'user',
        ?string $email = null,
        ContentType $contentType = null,
        int $userGroupId = 13
    ): User {
        $repository = $this->getRepository();

        /* BEGIN: Inline */
        $userService = $repository->getUserService();

        // Instantiate a create struct with mandatory properties
        $email = $email ?? "{$login}@example.com";
        $userCreate = $userService->newUserCreateStruct(
            $login,
            $email,
            'VerySecret@Password.1234',
            'eng-US'
        );
        $userCreate->enabled = true;

        // Set some fields required by the user ContentType
        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', 'User');

        if (!empty($contentType)) {
            $userCreate->contentType = $contentType;
        }

        // Load parent group for the user
        $group = $userService->loadUserGroup($userGroupId);

        // Create a new user instance.
        $user = $userService->createUser($userCreate, [$group]);
        /* END: Inline */

        return $user;
    }

    /**
     * Create a user in new user group with editor rights limited to Media Library (/1/48/).
     *
     * @uses ::createCustomUserVersion1()
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    protected function createMediaUserVersion1()
    {
        return $this->createCustomUserVersion1(
            'Media Editor',
            'Editor',
            new SubtreeLimitation(['limitationValues' => ['/1/43/']])
        );
    }

    /**
     * Create a user with new user group and assign a existing role (optionally with RoleLimitation).
     *
     * @param string $userGroupName Name of the new user group to create
     * @param string $roleIdentifier Role identifier to assign to the new group
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation|null $roleLimitation
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    protected function createCustomUserVersion1($userGroupName, $roleIdentifier, RoleLimitation $roleLimitation = null)
    {
        return $this->createCustomUserWithLogin(
            'user',
            'user@example.com',
            $userGroupName,
            $roleIdentifier,
            $roleLimitation
        );
    }

    /**
     * Create a user with new user group and assign a existing role (optionally with RoleLimitation).
     *
     * @param string $login User login
     * @param string $email User e-mail
     * @param string $userGroupName Name of the new user group to create
     * @param string $roleIdentifier Role identifier to assign to the new group
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation|null $roleLimitation
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    protected function createCustomUserWithLogin(
        $login,
        $email,
        $userGroupName,
        $roleIdentifier,
        RoleLimitation $roleLimitation = null
    ) {
        $repository = $this->getRepository();

        /* BEGIN: Inline */
        // ID of the "Users" user group in an Ibexa demo installation
        $rootUsersGroupId = $this->generateId('location', 4);

        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        // Get a group create struct
        $userGroupCreate = $userService->newUserGroupCreateStruct('eng-US');
        $userGroupCreate->setField('name', $userGroupName);

        // Create new group with media editor rights
        $userGroup = $userService->createUserGroup(
            $userGroupCreate,
            $userService->loadUserGroup($rootUsersGroupId)
        );
        $roleService->assignRoleToUserGroup(
            $roleService->loadRoleByIdentifier($roleIdentifier),
            $userGroup,
            $roleLimitation
        );

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            $login,
            $email,
            'secret',
            'eng-US'
        );
        $userCreate->enabled = true;

        // Set some fields required by the user ContentType
        $userCreate->setField('first_name', 'Example');
        $userCreate->setField('last_name', ucfirst($login));

        // Create a new user instance.
        $user = $userService->createUser($userCreate, [$userGroup]);
        /* END: Inline */

        return $user;
    }

    /**
     * Create a user using given data.
     *
     * @param string $login
     * @param string $firstName
     * @param string $lastName
     * @param \Ibexa\Contracts\Core\Repository\Values\User\UserGroup|null $userGroup optional user group, Editor by default
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     */
    protected function createUser($login, $firstName, $lastName, UserGroup $userGroup = null)
    {
        $repository = $this->getRepository();

        $userService = $repository->getUserService();
        if (null === $userGroup) {
            $userGroup = $userService->loadUserGroup(13);
        }

        // Instantiate a create struct with mandatory properties
        $userCreate = $userService->newUserCreateStruct(
            $login,
            "{$login}@example.com",
            'secret',
            'eng-US'
        );
        $userCreate->enabled = true;

        // Set some fields required by the user ContentType
        $userCreate->setField('first_name', $firstName);
        $userCreate->setField('last_name', $lastName);

        // Create a new user instance.
        $user = $userService->createUser($userCreate, [$userGroup]);

        return $user;
    }

    /**
     * Only for internal use.
     *
     * Creates a \DateTime object for $timestamp in the current time zone
     *
     * @param int $timestamp
     *
     * @return \DateTime
     */
    public function createDateTime($timestamp = null)
    {
        $dateTime = new \DateTime();
        if ($timestamp !== null) {
            $dateTime->setTimestamp($timestamp);
        }

        return $dateTime;
    }

    /**
     * Calls given Repository's aggregated SearchHandler::refresh().
     *
     * @param \Ibexa\Contracts\Core\Repository\Repository $repository
     */
    protected function refreshSearch(Repository $repository)
    {
        if ($this->isLegacySearchEngineSetup()) {
            return;
        }

        while (true) {
            $repositoryReflection = new \ReflectionObject($repository);
            // If the repository is decorated, we need to recurse in the "repository" property
            if (!$repositoryReflection->hasProperty('repository')) {
                break;
            }

            $repositoryProperty = $repositoryReflection->getProperty('repository');
            $repositoryProperty->setAccessible(true);
            $repository = $repositoryProperty->getValue($repository);
        }

        $searchHandlerProperty = new \ReflectionProperty($repository, 'searchHandler');
        $searchHandlerProperty->setAccessible(true);

        /** @var \Ibexa\Solr\Handler $searchHandler */
        $searchHandler = $searchHandlerProperty->getValue($repository);

        $searchHandler->commit();
    }

    protected function isLegacySearchEngineSetup(): bool
    {
        return get_class($this->getSetupFactory()) === LegacySetupFactory::class;
    }

    /**
     * Create role of a given name with the given policies described by an array.
     *
     * @param $roleName
     * @param array $policiesData [['module' => 'content', 'function' => 'read', 'limitations' => []]
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\Role
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\LimitationValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function createRoleWithPolicies($roleName, array $policiesData)
    {
        $repository = $this->getRepository(false);
        $roleService = $repository->getRoleService();

        $roleCreateStruct = $roleService->newRoleCreateStruct($roleName);
        foreach ($policiesData as $policyData) {
            $policyCreateStruct = $roleService->newPolicyCreateStruct(
                $policyData['module'],
                $policyData['function']
            );

            if (isset($policyData['limitations'])) {
                foreach ($policyData['limitations'] as $limitation) {
                    $policyCreateStruct->addLimitation($limitation);
                }
            }

            $roleCreateStruct->addPolicy($policyCreateStruct);
        }

        $roleDraft = $roleService->createRole($roleCreateStruct);

        $roleService->publishRoleDraft($roleDraft);

        return $roleService->loadRole($roleDraft->id);
    }

    /**
     * Create user and assign new role with the given policies.
     *
     * @param string $login
     * @param array $policiesData list of policies in the form of <code>[ [ 'module' => 'name', 'function' => 'name'] ]</code>
     * @param \Ibexa\Contracts\Core\Repository\Values\User\Limitation\RoleLimitation|null $roleLimitation
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\User\User
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function createUserWithPolicies($login, array $policiesData, RoleLimitation $roleLimitation = null)
    {
        $repository = $this->getRepository(false);
        $roleService = $repository->getRoleService();
        $userService = $repository->getUserService();

        $repository->beginTransaction();
        try {
            $userCreateStruct = $userService->newUserCreateStruct(
                $login,
                "{$login}@test.local",
                $login,
                'eng-GB'
            );
            $userCreateStruct->setField('first_name', $login);
            $userCreateStruct->setField('last_name', $login);
            $user = $userService->createUser($userCreateStruct, [$userService->loadUserGroup(4)]);

            $role = $this->createRoleWithPolicies(uniqid('role_for_' . $login . '_', true), $policiesData);
            $roleService->assignRoleToUser($role, $user, $roleLimitation);

            $repository->commit();

            return $user;
        } catch (ForbiddenException | NotFoundException | UnauthorizedException $ex) {
            $repository->rollback();
            throw $ex;
        }
    }

    /**
     * @throws \ErrorException
     */
    protected function getRawDatabaseConnection(): Connection
    {
        $connection = $this
            ->getSetupFactory()
            ->getServiceContainer()->get('ibexa.persistence.connection');

        if (!$connection instanceof Connection) {
            throw new \RuntimeException(
                sprintf('Found %s instead of %s', get_class($connection), Connection::class)
            );
        }

        return $connection;
    }

    /**
     * Executes the given callback passing raw Database Connection (\Doctrine\DBAL\Connection).
     * Returns the result returned by the given callback.
     *
     * **Note**: The method clears the entire persistence cache pool.
     *
     * @throws \Exception
     *
     * @param callable $callback
     *
     * @return mixed the return result of the given callback
     */
    public function performRawDatabaseOperation(callable $callback)
    {
        $repository = $this->getRepository(false);
        $repository->beginTransaction();
        try {
            $callback(
                $this->getRawDatabaseConnection()
            );
            $repository->commit();
        } catch (Exception $e) {
            $repository->rollback();
            throw $e;
        }

        /** @var \Symfony\Component\Cache\Adapter\TagAwareAdapterInterface $cachePool */
        $cachePool = $this
            ->getSetupFactory()
            ->getServiceContainer()->get('ibexa.cache_pool');

        $cachePool->clear();
    }

    /**
     * Traverse all errors for all fields in all Translations to find expected one.
     *
     * @param \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException $exception
     * @param string $expectedValidationErrorMessage
     */
    protected function assertValidationErrorOccurs(
        ContentFieldValidationException $exception,
        string $expectedValidationErrorMessage
    ): void {
        $constraint = new PHPUnitConstraint\ValidationErrorOccurs($expectedValidationErrorMessage);

        self::assertThat($exception, $constraint);
    }

    /**
     * Traverse all errors for all fields in all Translations to find if all expected ones occurred.
     *
     * @param \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException $exception
     * @param string[] $expectedValidationErrorMessages
     */
    protected function assertAllValidationErrorsOccur(
        ContentFieldValidationException $exception,
        array $expectedValidationErrorMessages
    ): void {
        $constraint = new PHPUnitConstraint\AllValidationErrorsOccur(
            $expectedValidationErrorMessages
        );

        self::assertThat($exception, $constraint);
    }

    protected function assertContentItemEquals(
        Content $expected,
        Content $actual,
        string $message
    ): void {
        $constraint = new PHPUnitConstraint\ContentItemEquals($expected);

        self::assertThat($actual, $constraint, $message);
    }

    /**
     * Create 'folder' Content.
     *
     * @param array $names Folder names in the form of <code>['&lt;language_code&gt;' => '&lt;name&gt;']</code>
     * @param int|null $parentLocationId
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content published Content
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    public function createFolder(
        array $names,
        ?int $parentLocationId = null,
        ?string $remoteId = null,
        bool $alwaysAvailable = true
    ): Content {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();
        $contentTypeService = $repository->getContentTypeService();
        $locationService = $repository->getLocationService();

        if (empty($names)) {
            throw new \RuntimeException(sprintf('%s expects a non-empty names list', __METHOD__));
        }
        $mainLanguageCode = array_keys($names)[0];

        $struct = $contentService->newContentCreateStruct(
            $contentTypeService->loadContentTypeByIdentifier('folder'),
            $mainLanguageCode
        );
        $struct->remoteId = $remoteId;
        $struct->alwaysAvailable = $alwaysAvailable;
        foreach ($names as $languageCode => $translatedName) {
            $struct->setField('name', $translatedName, $languageCode);
        }

        $locationCreateStructList = [];
        if (null !== $parentLocationId) {
            $locationCreateStructList[] = $locationService->newLocationCreateStruct(
                $parentLocationId
            );
        }

        $contentDraft = $contentService->createContent(
            $struct,
            $locationCreateStructList
        );

        return $contentService->publishVersion($contentDraft->versionInfo);
    }

    /**
     * Update 'folder' Content.
     *
     * @param \Ibexa\Contracts\Core\Repository\Values\Content\Content $content
     * @param array $names Folder names in the form of <code>['&lt;language_code&gt;' => '&lt;name&gt;']</code>
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Content
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\BadStateException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function updateFolder(Content $content, array $names)
    {
        $repository = $this->getRepository(false);
        $contentService = $repository->getContentService();

        $draft = $contentService->createContentDraft($content->contentInfo);
        $struct = $contentService->newContentUpdateStruct();
        $struct->initialLanguageCode = array_keys($names)[0];

        foreach ($names as $languageCode => $translatedName) {
            $struct->setField('name', $translatedName, $languageCode);
        }

        return $contentService->updateContent($draft->versionInfo, $struct);
    }

    /**
     * Add new Language to the Repository.
     *
     * @param string $languageCode
     * @param string $name
     * @param bool $enabled
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\Content\Language
     */
    protected function createLanguage(string $languageCode, string $name, bool $enabled = true): Language
    {
        $repository = $this->getRepository(false);

        $languageService = $repository->getContentLanguageService();
        $languageStruct = $languageService->newLanguageCreateStruct();
        $languageStruct->name = $name;
        $languageStruct->languageCode = $languageCode;
        $languageStruct->enabled = $enabled;

        return $languageService->createLanguage($languageStruct);
    }

    protected function createLanguageIfNotExists(
        string $languageCode,
        string $name,
        bool $enabled = true
    ): Language {
        $repository = $this->getRepository(false);

        try {
            return $repository->getContentLanguageService()->loadLanguage($languageCode);
        } catch (NotFoundException $e) {
            return $this->createLanguage($languageCode, $name, $enabled);
        }
    }

    /**
     * @param string $identifier content type identifier
     * @param string $mainTranslation main translation language code
     * @param array $fieldsToDefine a map of field definition identifiers to their field type identifiers
     * @param bool $alwaysAvailable default always available
     *
     * @return \Ibexa\Contracts\Core\Repository\Values\ContentType\ContentType
     *
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\ForbiddenException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException
     */
    protected function createSimpleContentType(
        string $identifier,
        string $mainTranslation,
        array $fieldsToDefine,
        bool $alwaysAvailable = true
    ): ContentType {
        $contentTypeService = $this->getRepository(false)->getContentTypeService();
        $contentTypeCreateStruct = $contentTypeService->newContentTypeCreateStruct($identifier);
        $contentTypeCreateStruct->mainLanguageCode = $mainTranslation;
        $contentTypeCreateStruct->names = [$mainTranslation => $identifier];
        $contentTypeCreateStruct->defaultAlwaysAvailable = $alwaysAvailable;
        foreach ($fieldsToDefine as $fieldDefinitionIdentifier => $fieldTypeIdentifier) {
            $fieldDefinitionCreateStruct = $contentTypeService->newFieldDefinitionCreateStruct(
                $fieldDefinitionIdentifier,
                $fieldTypeIdentifier
            );
            $fieldDefinitionCreateStruct->isTranslatable = true;
            $contentTypeCreateStruct->addFieldDefinition($fieldDefinitionCreateStruct);
        }
        $contentTypeService->publishContentTypeDraft(
            $contentTypeService->createContentType(
                $contentTypeCreateStruct,
                [$contentTypeService->loadContentTypeGroupByIdentifier('Content')]
            )
        );

        return $contentTypeService->loadContentTypeByIdentifier($identifier);
    }

    protected function loginAsUser(UserReference $user): void
    {
        $this->getRepository(false)->getPermissionResolver()->setCurrentUserReference($user);
    }
}
