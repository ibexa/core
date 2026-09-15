<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Integration\Core\Repository;

use Ibexa\Contracts\Core\Repository\Exceptions\UnauthorizedException;
use Ibexa\Contracts\Core\Repository\URLService;
use Ibexa\Contracts\Core\Repository\Values\URL\Query\Criterion;
use Ibexa\Contracts\Core\Repository\Values\URL\URLQuery;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(URLService::class, 'findUrls')]
#[CoversMethod(URLService::class, 'updateUrl')]
#[CoversMethod(URLService::class, 'loadById')]
class URLServiceAuthorizationTest extends BaseURLServiceTestCase
{
    /**
     * Test for the findUrls() method.
     */
    public function testFindUrlsThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $urlService = $repository->getURLService();

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $query = new URLQuery();
        $query->filter = new Criterion\MatchAll();

        $this->expectException(UnauthorizedException::class);
        $urlService->findUrls($query);
        /* END: Use Case */
    }

    /**
     * Test for the updateUrl() method.
     */
    public function testUpdateUrlThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        $urlId = $this->generateId('url', 23);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $urlService = $repository->getURLService();

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        $url = $urlService->loadById($urlId);
        $updateStruct = $urlService->createUpdateStruct();
        $updateStruct->url = 'https://vimeo.com/';

        // This call will fail with an UnauthorizedException
        $urlService->updateUrl($url, $updateStruct);
        /* END: Use Case */
    }

    /**
     * Test for the loadById() method.
     */
    public function testLoadByIdThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        $urlId = $this->generateId('url', 23);
        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $urlService = $repository->getURLService();

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an UnauthorizedException
        $urlService->loadById($urlId);
        /* END: Use Case */
    }

    /**
     * Test for the loadByUrl() method.
     */
    public function testLoadByUrlThrowsUnauthorizedException()
    {
        $this->expectException(UnauthorizedException::class);

        $repository = $this->getRepository();

        $anonymousUserId = $this->generateId('user', 10);
        $url = '/content/view/sitemap/2';

        /* BEGIN: Use Case */
        // $anonymousUserId is the ID of the "Anonymous" user in a Ibexa
        // Publish demo installation.

        $userService = $repository->getUserService();
        $urlService = $repository->getURLService();

        $repository->getPermissionResolver()->setCurrentUserReference($userService->loadUser($anonymousUserId));

        // This call will fail with an UnauthorizedException
        $urlService->loadByUrl($url);
        /* END: Use Case */
    }
}
