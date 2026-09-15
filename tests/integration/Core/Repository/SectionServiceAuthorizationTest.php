<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\Values\User\Limitation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test case for operations in the SectionService using in memory storage.
 */
#[CoversClass(SectionService::class)]
#[CoversMethod(SectionService::class, 'createSection()')]
#[CoversMethod(SectionService::class, 'loadSection()')]
#[CoversMethod(SectionService::class, 'updateSection()')]
#[CoversMethod(SectionService::class, 'loadSections()')]
#[CoversMethod(SectionService::class, 'loadSectionByIdentifier()')]
#[CoversMethod(SectionService::class, 'assignSection()')]
#[CoversMethod(SectionService::class, 'deleteSection()')]
#[Group('integration')]
#[Group('authorization')]
class SectionServiceAuthorizationTest extends BaseTestCase
{
    /**
     * Test for the createSection() method.
     */
    public function testCreateSectionThrowsUnauthorizedException()
    {
        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        $sectionCreate = $sectionService->newSectionCreateStruct();
        $sectionCreate->name = 'Test Section';
        $sectionCreate->identifier = 'uniqueKey';

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("The User does not have the 'edit' 'section' permission");

        $sectionService->createSection($sectionCreate);
        /* END: Use Case */
    }

    /**
     * Test for the loadSection() method.
     */
    public function testLoadSectionThrowsUnauthorizedException()
    {
        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        $sectionCreate = $sectionService->newSectionCreateStruct();
        $sectionCreate->name = 'Test Section';
        $sectionCreate->identifier = 'uniqueKey';

        $sectionId = $sectionService->createSection($sectionCreate)->id;

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("The User does not have the 'view' 'section' permission");

        $sectionService->loadSection($sectionId);
        /* END: Use Case */
    }

    /**
     * Test for the updateSection() method.
     */
    public function testUpdateSectionThrowsUnauthorizedException()
    {
        $repository = $this->getRepository();

        $standardSectionId = $this->generateId('section', 1);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // $standardSectionId is the ID of the "Standard" section in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        $section = $sectionService->loadSection($standardSectionId);

        $sectionUpdate = $sectionService->newSectionUpdateStruct();
        $sectionUpdate->name = 'New section name';
        $sectionUpdate->identifier = 'newUniqueKey';

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("The User does not have the 'edit' 'section' permission");

        $sectionService->updateSection($section, $sectionUpdate);
        /* END: Use Case */
    }

    /**
     * Test for the loadSections() method.
     */
    public function testLoadSectionsLoadsEmptyListForAnonymousUser()
    {
        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        // Create some sections
        $sectionCreateOne = $sectionService->newSectionCreateStruct();
        $sectionCreateOne->name = 'Test section one';
        $sectionCreateOne->identifier = 'uniqueKeyOne';

        $sectionCreateTwo = $sectionService->newSectionCreateStruct();
        $sectionCreateTwo->name = 'Test section two';
        $sectionCreateTwo->identifier = 'uniqueKeyTwo';

        $sectionService->createSection($sectionCreateOne);
        $sectionService->createSection($sectionCreateTwo);

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $sections = $sectionService->loadSections();
        /* END: Use Case */

        self::assertEquals([], $sections);
    }

    /**
     * Test for the loadSections() method.
     */
    public function testLoadSectionFiltersSections()
    {
        $repository = $this->getRepository();

        /* BEGIN: Use Case */
        // Publish demo installation.
        $sectionService = $repository->getSectionService();
        // Create some sections
        $sectionCreateOne = $sectionService->newSectionCreateStruct();
        $sectionCreateOne->name = 'Test section one';
        $sectionCreateOne->identifier = 'uniqueKeyOne';

        $sectionCreateTwo = $sectionService->newSectionCreateStruct();
        $sectionCreateTwo->name = 'Test section two';
        $sectionCreateTwo->identifier = 'uniqueKeyTwo';

        $expectedSection = $sectionService->createSection($sectionCreateOne);
        $sectionService->createSection($sectionCreateTwo);

        // Set user
        $this->createRoleWithPolicies('MediaUser', [
            ['module' => '*', 'function' => '*'],
        ]);
        $mediaUser = $this->createCustomUserWithLogin(
            'user',
            'user@example.com',
            'MediaUser',
            'MediaUser',
            new Limitation\SectionLimitation(['limitationValues' => [$expectedSection->id]])
        );

        $repository->getPermissionResolver()->setCurrentUserReference($mediaUser);

        $sections = $sectionService->loadSections();
        /* END: Use Case */

        // Only Sections the user has access to should be loaded
        self::assertEquals([$expectedSection], $sections);
    }

    /**
     * Test for the loadSectionByIdentifier() method.
     */
    public function testLoadSectionByIdentifierThrowsUnauthorizedException()
    {
        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        $sectionCreate = $sectionService->newSectionCreateStruct();
        $sectionCreate->name = 'Test Section';
        $sectionCreate->identifier = 'uniqueKey';

        $sectionService->createSection($sectionCreate);

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage("The User does not have the 'view' 'section' permission");

        $sectionService->loadSectionByIdentifier('uniqueKey');
        /* END: Use Case */
    }

    /**
     * Test for the assignSection() method.
     */
    public function testAssignSectionThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $standardSectionId = $this->generateId('section', 1);
        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // $standardSectionId is the ID of the "Standard" section in a Ibexa
        // Publish demo installation.

        // RemoteId of the "Media" page of an Ibexa demo installation
        $mediaRemoteId = 'a6e35cbcb7cd6ae4b691f3eee30cd262';

        $userService = $repository->getUserService();
        $contentService = $repository->getContentService();
        $sectionService = $repository->getSectionService();

        // Load a content info instance
        $contentInfo = $contentService->loadContentInfoByRemoteId(
            $mediaRemoteId
        );

        // Load the "Standard" section
        $section = $sectionService->loadSection($standardSectionId);

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $sectionService->assignSection($contentInfo, $section);
        /* END: Use Case */
    }

    /**
     * Test for the deleteSection() method.
     */
    public function testDeleteSectionThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $sectionService = $repository->getSectionService();

        $sectionCreate = $sectionService->newSectionCreateStruct();
        $sectionCreate->name = 'Test Section';
        $sectionCreate->identifier = 'uniqueKey';

        $section = $sectionService->createSection($sectionCreate);

        // Set anonymous user
        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with a "UnauthorizedException"
        $sectionService->deleteSection($section);
        /* END: Use Case */
    }
}
