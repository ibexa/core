<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Core\Repository\URLAliasService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DependsExternal;

#[CoversClass(URLAliasService::class)]
class URLAliasServiceAuthorizationTest extends BaseTestCase
{
    /**
     * Test for the createUrlAlias() method.
     */
    #[DependsExternal(URLAliasServiceTest::class, 'testCreateUrlAlias')]
    public function testCreateUrlAliasThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        $parentLocationId = $this->generateId('location', 2);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        // $locationId is the ID of an existing location
        $userService = $repository->getUserService();
        $urlAliasService = $repository->getURLAliasService();
        $locationService = $repository->getLocationService();

        $content = $this->createFolder(['eng-US' => 'Foo'], $parentLocationId);
        $location = $locationService->loadLocation($content->contentInfo->mainLocationId);

        $anonymousUser = $userService->loadUser($anonymousUserId);
        $repository->getPermissionResolver()->setCurrentUserReference($anonymousUser);

        $this->expectException(UnauthorizedException::class);
        $urlAliasService->createUrlAlias($location, '/Home/My-New-Site', 'eng-GB');
        /* END: Use Case */
    }

    /**
     * Test for the createGlobalUrlAlias() method.
     */
    #[DependsExternal(URLAliasServiceTest::class, 'testCreateGlobalUrlAlias')]
    public function testCreateGlobalUrlAliasThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $urlAliasService = $repository->getURLAliasService();

        $anonymousUser = $userService->loadUser($anonymousUserId);
        $repository->getPermissionResolver()->setCurrentUserReference($anonymousUser);

        // This call will fail with an UnauthorizedException
        $urlAliasService->createGlobalUrlAlias('module:content/search?SearchText=Ibexa', '/Home/My-New-Site', 'eng-GB');
        /* END: Use Case */
    }

    /**
     * Test for the removeAliases() method.
     */
    #[DependsExternal(URLAliasServiceTest::class, 'testRemoveAliases')]
    public function testRemoveAliasesThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();
        $anonymousUserId = $this->generateId('user', 10);

        $locationService = $repository->getLocationService();
        $someLocation = $locationService->loadLocation(
            $this->generateId('location', 12)
        );

        /* BEGIN: Use Case */
        // $someLocation contains a location with automatically generated
        // aliases assigned
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        $urlAliasService = $repository->getURLAliasService();
        $userService = $repository->getUserService();

        $anonymousUser = $userService->loadUser($anonymousUserId);
        $repository->getPermissionResolver()->setCurrentUserReference($anonymousUser);

        $initialAliases = $urlAliasService->listLocationAliases($someLocation);

        // This call will fail with an UnauthorizedException
        $urlAliasService->removeAliases($initialAliases);
        /* END: Use Case */
    }
}
