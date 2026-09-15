<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;

/**
 * Test case for operations in the URLWildcardService.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\Ibexa\Contracts\Core\Repository\URLWildcardService::class)]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\URLWildcardService::class, 'create')]
#[\PHPUnit\Framework\Attributes\CoversMethod(\Ibexa\Contracts\Core\Repository\URLWildcardService::class, 'remove()')]
#[\PHPUnit\Framework\Attributes\Group('integration')]
#[\PHPUnit\Framework\Attributes\Group('authorization')]
class URLWildcardServiceAuthorizationTest extends BaseTestCase
{
    #[\PHPUnit\Framework\Attributes\DependsExternal(\Ibexa\Tests\Integration\Core\Repository\URLWildcardServiceTest::class, 'testCreate')]
    public function testCreateThrowsUnauthorizedException(): void
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $urlWildcardService = $repository->getURLWildcardService();

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $this->expectException(UnauthorizedException::class);
        $urlWildcardService->create('/articles/*', '/content/{1}');
        /* END: Use Case */
    }

    /**
     * Test for the remove() method.
     */
    #[\PHPUnit\Framework\Attributes\DependsExternal(\Ibexa\Tests\Integration\Core\Repository\URLWildcardServiceTest::class, 'testRemove')]
    public function testRemoveThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.
        $userService = $repository->getUserService();
        $urlWildcardService = $repository->getURLWildcardService();

        // Create a new url wildcard
        $urlWildcardId = $urlWildcardService->create('/articles/*', '/content/{1}')->id;

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // Load newly created url wildcard
        $urlWildcard = $urlWildcardService->load($urlWildcardId);

        $this->expectException(UnauthorizedException::class);
        $urlWildcardService->remove($urlWildcard);
        /* END: Use Case */
    }
}
