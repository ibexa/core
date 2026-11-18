<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\Repository\Permission;

use Ibexa\Contracts\Core\Repository\Values\Content\Query;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Test\Core\IbexaTestCore;
use Ibexa\Tests\Integration\Core\RepositoryTestCase;
use Symfony\Contracts\Service\ResetInterface;

/**
 * A reused kernel (messenger worker, persistent PHP worker) resets services between
 * messages/requests. The next one must not run as the previously authenticated user.
 *
 * @covers \Ibexa\Core\Repository\Permission\PermissionResolver::reset
 * @covers \Ibexa\Core\Repository\Permission\CachedPermissionService::reset
 */
final class PermissionResolverResetTest extends RepositoryTestCase
{
    public function testServicesResetRestoresAnonymousUser(): void
    {
        $core = $this->getIbexaTestCore();
        $permissionResolver = $core->getPermissionResolver();
        $userQuery = new Query(['filter' => new ContentTypeIdentifier('user')]);

        self::assertSame(self::ADMIN_USER_ID, $permissionResolver->getCurrentUserReference()->getUserId());
        self::assertTrue($permissionResolver->hasAccess('setup', 'administrate'));
        // Primes the content/read permission criterion cache with the administrator's criterion
        self::assertGreaterThan(0, $core->getSearchService()->findContent($userQuery)->totalCount);

        $servicesResetter = self::getContainer()->get('services_resetter');
        self::assertInstanceOf(ResetInterface::class, $servicesResetter);
        $servicesResetter->reset();

        self::assertSame(IbexaTestCore::ANONYMOUS_USER_ID, $permissionResolver->getCurrentUserReference()->getUserId());
        self::assertFalse($permissionResolver->hasAccess('setup', 'administrate'));
        self::assertSame(0, $core->getSearchService()->findContent($userQuery)->totalCount);
    }
}
