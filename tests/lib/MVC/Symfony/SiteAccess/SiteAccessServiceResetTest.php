<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService::reset
 */
final class SiteAccessServiceResetTest extends TestCase
{
    public function testResetDropsUnrestoredScopeChanges(): void
    {
        $sharedSiteAccess = new SiteAccess('site');
        $service = $this->createService();
        $service->setSiteAccess($sharedSiteAccess);
        $service->onConfigScopeChange(new ScopeChangeEvent(new SiteAccess('admin')));
        $service->onConfigScopeChange(new ScopeChangeEvent(new SiteAccess('site_fr')));

        $service->reset();

        self::assertSame($sharedSiteAccess, $service->getCurrent());
    }

    public function testResetWithoutSiteAccessKeepsNone(): void
    {
        $service = $this->createService();

        $service->reset();

        self::assertNull($service->getCurrent());
    }

    private function createService(): SiteAccessService
    {
        return new SiteAccessService(
            new StaticSiteAccessProvider(['site', 'admin', 'site_fr']),
            $this->createStub(ConfigResolverInterface::class)
        );
    }
}
