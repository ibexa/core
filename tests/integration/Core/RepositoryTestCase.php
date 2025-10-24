<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core;

use Ibexa\Contracts\Core\Repository\Exceptions\ContentFieldValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\ContentValidationException;
use Ibexa\Contracts\Core\Repository\Exceptions\Exception;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\Values\Content\Content;
use Ibexa\Contracts\Core\Repository\Values\User\User;
use Ibexa\Contracts\Core\Repository\Values\User\UserGroup;
use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use InvalidArgumentException;

abstract class RepositoryTestCase extends IbexaKernelTestCase
{
    public const CONTENT_TREE_ROOT_ID = 2;
    public const ADMIN_USER_ID = 14;

    private const CONTENT_TYPE_FOLDER_IDENTIFIER = 'folder';
    private const MAIN_USER_GROUP_REMOTE_ID = 'f5c88a2209584891056f987fd965b0ba';

    protected function setUp(): void
    {
        parent::setUp();

        self::loadSchema();
        self::loadFixtures();

        self::setAdministratorUser();
    }

    /**
     * @param array<string, string> $names
     *
     * @throws Exception
     */
    public function createFolder(
        array $names,
        int $parentLocationId = self::CONTENT_TREE_ROOT_ID
    ): Content {
        $contentService = self::getContentService();
        $draft = $this->createFolderDraft($names, $parentLocationId);

        return $contentService->publishVersion($draft->getVersionInfo());
    }

    /**
     * @throws \Ibexa\Contracts\Core\Repository\Exceptions\InvalidArgumentException
     * @throws ContentValidationException
     * @throws UnauthorizedException
     * @throws ContentFieldValidationException
     * @throws NotFoundException
     */
    final protected function createUser(
        string $login,
        string $firstName,
        string $lastName,
        ?UserGroup $userGroup = null
    ): User {
        $userService = self::getUserService();

        if (null === $userGroup) {
            $userGroup = $userService->loadUserGroupByRemoteId(self::MAIN_USER_GROUP_REMOTE_ID);
        }

        $userCreateStruct = $userService->newUserCreateStruct(
            $login,
            "$login@mail.invalid",
            'secret',
            'eng-US'
        );
        $userCreateStruct->enabled = true;

        // Set some fields required by the user ContentType
        $userCreateStruct->setField('first_name', $firstName);
        $userCreateStruct->setField('last_name', $lastName);

        // Create a new user instance.
        return $userService->createUser($userCreateStruct, [$userGroup]);
    }

    /**
     * @param array<string, string> $names
     *
     * @throws Exception
     */
    public function createFolderDraft(
        array $names,
        int $parentLocationId = self::CONTENT_TREE_ROOT_ID
    ): Content {
        if (empty($names)) {
            throw new InvalidArgumentException(__METHOD__ . ' requires $names to be not empty');
        }

        $contentService = self::getContentService();
        $contentTypeService = self::getContentTypeService();
        $locationService = self::getLocationService();

        $folderType = $contentTypeService->loadContentTypeByIdentifier(self::CONTENT_TYPE_FOLDER_IDENTIFIER);
        $mainLanguageCode = array_keys($names)[0];
        $contentCreateStruct = $contentService->newContentCreateStruct($folderType, $mainLanguageCode);
        foreach ($names as $languageCode => $name) {
            $contentCreateStruct->setField('name', $name, $languageCode);
        }

        return $contentService->createContent(
            $contentCreateStruct,
            [
                $locationService->newLocationCreateStruct($parentLocationId),
            ]
        );
    }
}
