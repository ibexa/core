<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\MVC\Symfony\SiteAccess;

use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\MVC\Symfony\Routing\SimplifiedRequest;
use Ibexa\Core\MVC\Symfony\SiteAccess\Router;
use Symfony\Contracts\Service\ResetInterface;

/**
 * A persistent PHP worker resets services before every request after the first one.
 * Each request must get its own SiteAccess, not the one matched on the first request.
 *
 * @covers \Ibexa\Core\MVC\Symfony\SiteAccess\Router::reset
 */
final class RouterResetTest extends IbexaKernelTestCase
{
    public function testServicesResetMakesTheRouterMatchEachRequestAgain(): void
    {
        $router = self::getContainer()->get(Router::class);
        self::assertInstanceOf(Router::class, $router);
        $servicesResetter = self::getContainer()->get('services_resetter');
        self::assertInstanceOf(ResetInterface::class, $servicesResetter);

        foreach (['__default_site_access__', '__second_site_access__', '__default_site_access__'] as $siteAccessName) {
            $request = new SimplifiedRequest(['pathinfo' => '/', 'headers' => ['x-siteaccess' => [$siteAccessName]]]);

            self::assertSame($siteAccessName, $router->match($request)->name);

            $servicesResetter->reset();
        }
    }
}
