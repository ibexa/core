<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Integration\Core\MVC\Symfony\SiteAccess;

use Ibexa\Contracts\Core\Test\IbexaKernelTestCase;
use Ibexa\Core\Helper\ContentPreviewHelper;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * A persistent PHP worker resets services before every request after the first one.
 * A scope change left unbalanced by one request must not decide the SiteAccess of the next one.
 *
 * @covers \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService::reset
 * @covers \Ibexa\Core\Helper\ContentPreviewHelper::reset
 */
final class SiteAccessServiceResetTest extends IbexaKernelTestCase
{
    public function testServicesResetDropsUnbalancedScopeChange(): void
    {
        $siteAccessService = self::getContainer()->get(SiteAccessService::class);
        self::assertInstanceOf(SiteAccessService::class, $siteAccessService);

        $this->handleRequest('__default_site_access__');
        $this->getEventDispatcher()->dispatch(
            new ScopeChangeEvent(new SiteAccess('__second_site_access__')),
            MVCEvents::CONFIG_SCOPE_CHANGE
        );
        $current = $siteAccessService->getCurrent();
        self::assertNotNull($current);
        self::assertSame('__second_site_access__', $current->name);

        $this->resetServices();
        $this->handleRequest('__default_site_access__');

        $current = $siteAccessService->getCurrent();
        self::assertNotNull($current);
        self::assertSame('__default_site_access__', $current->name);
    }

    public function testServicesResetEndsPreviewAndGivesPreviewHelperTheSharedSiteAccessBack(): void
    {
        $previewHelper = self::getContainer()->get(ContentPreviewHelper::class);
        self::assertInstanceOf(ContentPreviewHelper::class, $previewHelper);

        $this->handleRequest('__default_site_access__');
        $previewHelper->setPreviewActive(true);

        $this->resetServices();
        $this->handleRequest('__second_site_access__');

        self::assertSame('__second_site_access__', $previewHelper->getOriginalSiteAccess()->name);
        self::assertFalse($previewHelper->isPreviewActive());
    }

    /**
     * Does what SiteAccessMatchListener does for a main request: SiteAccessListener copies the matched
     * SiteAccess into the shared SiteAccess service object.
     */
    private function handleRequest(string $siteAccessName): void
    {
        $this->getEventDispatcher()->dispatch(
            new PostSiteAccessMatchEvent(
                new SiteAccess($siteAccessName, 'header'),
                Request::create('/'),
                HttpKernelInterface::MAIN_REQUEST
            ),
            MVCEvents::SITEACCESS
        );
    }

    private function resetServices(): void
    {
        $servicesResetter = self::getContainer()->get('services_resetter');
        self::assertInstanceOf(ResetInterface::class, $servicesResetter);
        $servicesResetter->reset();
    }

    private function getEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        return $eventDispatcher;
    }
}
