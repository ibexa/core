<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Core\MVC\Symfony\SiteAccess;

use ArrayIterator;
use Ibexa\Contracts\Core\Repository\Exceptions\NotFoundException;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Ibexa\Core\MVC\Symfony\SiteAccess\Provider\StaticSiteAccessProvider;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessService;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SiteAccessServiceTest extends TestCase
{
    private const EXISTING_SA_NAME = 'existing_sa';
    private const UNDEFINED_SA_NAME = 'undefined_sa';
    private const SA_GROUP = 'group';

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess */
    private $siteAccess;

    /** @var \ArrayIterator */
    private $availableSiteAccesses;

    /** @var array */
    private $configResolverParameters;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = $this->createMock(SiteAccessProviderInterface::class);
        $this->configResolver = $this->createMock(ConfigResolverInterface::class);
        $this->siteAccess = new SiteAccess('current');
        $this->availableSiteAccesses = $this->getAvailableSitAccesses(['current', 'first_sa', 'second_sa', 'default']);
        $this->configResolverParameters = $this->getConfigResolverParameters();
    }

    public function testGetCurrentIsNullInitially(): void
    {
        self::assertNull($this->createService()->getCurrent());
    }

    public function testOnSiteAccessMatchResetsStackOnMainRequest(): void
    {
        $service = $this->createService();

        $siteAccess = new SiteAccess('default');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($siteAccess, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame($siteAccess, $service->getCurrent());
    }

    public function testOnSiteAccessMatchIsNoopOnSubRequest(): void
    {
        $service = $this->createService();

        $mainSiteAccess = new SiteAccess('main');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($mainSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $subRequestSiteAccess = new SiteAccess('sub_request');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($subRequestSiteAccess, HttpKernelInterface::SUB_REQUEST));

        self::assertSame($mainSiteAccess, $service->getCurrent());
    }

    public function testGetSubscribedEvents(): void
    {
        self::assertSame(
            [
                MVCEvents::SITEACCESS => 'onSiteAccessMatch',
                MVCEvents::CONFIG_SCOPE_CHANGE => 'onConfigScopeChange',
                MVCEvents::CONFIG_SCOPE_RESTORE => 'onConfigScopeRestore',
            ],
            SiteAccessService::getSubscribedEvents()
        );
    }

    public function testChangeSiteAccessPushesAndReturnsTheGivenSiteAccess(): void
    {
        $service = $this->createService();

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $previewSiteAccess = new SiteAccess('preview');
        self::assertSame($previewSiteAccess, $service->changeSiteAccess($previewSiteAccess));
        self::assertSame($previewSiteAccess, $service->getCurrent());
    }

    public function testChangeSiteAccessDispatchesEventWithoutTriggeringTheDeprecatedHandler(): void
    {
        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher);

        $dispatchedEvents = [];
        $eventDispatcher->addListener(MVCEvents::CONFIG_SCOPE_CHANGE, static function (ScopeChangeEvent $event) use (&$dispatchedEvents): void {
            $dispatchedEvents[] = $event->getSiteAccess();
        });

        $previewSiteAccess = new SiteAccess('preview');
        $service->changeSiteAccess($previewSiteAccess);

        // The event is still dispatched for other, unrelated listeners to observe...
        self::assertSame([$previewSiteAccess], $dispatchedEvents);
        // ...but the stack was mutated exactly once by changeSiteAccess() itself, proving
        // the service's own onConfigScopeChange() (which would push a 2nd time) did not react.
        self::assertSame($previewSiteAccess, $service->getCurrent());
    }

    public function testRestoreSiteAccessPopsStackAndReturnsThePreviousSiteAccess(): void
    {
        $service = $this->createService();

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $previewSiteAccess = new SiteAccess('preview');
        $service->changeSiteAccess($previewSiteAccess);

        self::assertSame($baseSiteAccess, $service->restoreSiteAccess());
        self::assertSame($baseSiteAccess, $service->getCurrent());
    }

    public function testRestoreSiteAccessNeverDropsBelowTheBaseSiteAccess(): void
    {
        $service = $this->createService();

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        self::assertSame($baseSiteAccess, $service->restoreSiteAccess());
        self::assertSame($baseSiteAccess, $service->getCurrent());
    }

    public function testRestoreSiteAccessReturnsNullWhenStackIsEmpty(): void
    {
        self::assertNull($this->createService()->restoreSiteAccess());
    }

    public function testNestedChangeSiteAccessAndRestoreSiteAccessRoundTripLikeAStack(): void
    {
        $service = $this->createService();

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $firstSiteAccess = new SiteAccess('first');
        $secondSiteAccess = new SiteAccess('second');

        $service->changeSiteAccess($firstSiteAccess);
        self::assertSame($firstSiteAccess, $service->getCurrent());

        $service->changeSiteAccess($secondSiteAccess);
        self::assertSame($secondSiteAccess, $service->getCurrent());

        self::assertSame($firstSiteAccess, $service->restoreSiteAccess());
        self::assertSame($baseSiteAccess, $service->restoreSiteAccess());
    }

    public function testOnConfigScopeChangeReactsToAManuallyDispatchedEventAndTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessage('Since ibexa/core 6.0: Dispatching ' . ScopeChangeEvent::class . ' manually under MVCEvents::CONFIG_SCOPE_CHANGE is deprecated and will no longer be reflected by ' . SiteAccessService::class . '::getCurrent() in 7.0. Call ' . SiteAccessServiceInterface::class . '::changeSiteAccess() instead.');

        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher);

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $previewSiteAccess = new SiteAccess('preview');
        $eventDispatcher->dispatch(new ScopeChangeEvent($previewSiteAccess), MVCEvents::CONFIG_SCOPE_CHANGE);

        self::assertSame($previewSiteAccess, $service->getCurrent());
    }

    public function testOnConfigScopeRestoreReactsToAManuallyDispatchedEventAndTriggersDeprecation(): void
    {
        $this->expectUserDeprecationMessage('Since ibexa/core 6.0: Dispatching ' . ScopeChangeEvent::class . ' manually under MVCEvents::CONFIG_SCOPE_RESTORE is deprecated and will no longer be reflected by ' . SiteAccessService::class . '::getCurrent() in 7.0. Call ' . SiteAccessServiceInterface::class . '::restoreSiteAccess() instead.');

        $eventDispatcher = new EventDispatcher();
        $service = $this->createService($eventDispatcher);

        $baseSiteAccess = new SiteAccess('base');
        $service->onSiteAccessMatch($this->createSiteAccessMatchEvent($baseSiteAccess, HttpKernelInterface::MAIN_REQUEST));

        $previewSiteAccess = new SiteAccess('preview');
        $service->changeSiteAccess($previewSiteAccess);

        $eventDispatcher->dispatch(new ScopeChangeEvent($baseSiteAccess), MVCEvents::CONFIG_SCOPE_RESTORE);

        self::assertSame($baseSiteAccess, $service->getCurrent());
    }

    public function testGetSiteAccess(): void
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $service = new SiteAccessService(
            $staticSiteAccessProvider,
            self::createStub(ConfigResolverInterface::class),
            new EventDispatcher()
        );

        self::assertEquals(
            self::EXISTING_SA_NAME,
            $service->get(self::EXISTING_SA_NAME)->name
        );
    }

    public function testGetSiteAccessThrowsNotFoundException(): void
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $service = new SiteAccessService(
            $staticSiteAccessProvider,
            self::createStub(ConfigResolverInterface::class),
            new EventDispatcher()
        );

        $this->expectException(NotFoundException::class);
        $service->get(self::UNDEFINED_SA_NAME);
    }

    public function testGetCurrentSiteAccessesRelation(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap($this->configResolverParameters);

        $this->provider
            ->method('getSiteAccesses')
            ->willReturn($this->availableSiteAccesses);

        self::assertSame(['current', 'first_sa'], $this->getSiteAccessService()->getSiteAccessesRelation());
    }

    public function testGetFirstSiteAccessesRelation(): void
    {
        $this->configResolver
            ->method('getParameter')
            ->willReturnMap($this->configResolverParameters);

        $this->provider
            ->method('getSiteAccesses')
            ->willReturn($this->availableSiteAccesses);

        self::assertSame(
            ['current', 'first_sa'],
            $this->getSiteAccessService()->getSiteAccessesRelation(new SiteAccess('first_sa'))
        );
    }

    private function createService(?EventDispatcherInterface $eventDispatcher = null): SiteAccessService
    {
        $eventDispatcher ??= new EventDispatcher();
        $service = new SiteAccessService($this->provider, $this->configResolver, $eventDispatcher);
        $eventDispatcher->addSubscriber($service);

        return $service;
    }

    private function createSiteAccessMatchEvent(SiteAccess $siteAccess, int $requestType): PostSiteAccessMatchEvent
    {
        return new PostSiteAccessMatchEvent($siteAccess, new Request(), $requestType);
    }

    private function getSiteAccessService(): SiteAccessService
    {
        $siteAccessService = $this->createService();
        $siteAccessService->onSiteAccessMatch(
            $this->createSiteAccessMatchEvent($this->siteAccess, HttpKernelInterface::MAIN_REQUEST)
        );

        return $siteAccessService;
    }

    /**
     * @param string[] $siteAccessNames
     */
    private function getAvailableSitAccesses(array $siteAccessNames): ArrayIterator
    {
        $availableSitAccesses = [];
        foreach ($siteAccessNames as $siteAccessName) {
            $availableSitAccesses[] = new SiteAccess($siteAccessName);
        }

        return new ArrayIterator($availableSitAccesses);
    }

    private function getConfigResolverParameters(): array
    {
        return [
            ['repository', 'ibexa.site_access.config', 'current', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'current', 1],
            ['repository', 'ibexa.site_access.config', 'first_sa', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'first_sa', 1],
            ['repository', 'ibexa.site_access.config', 'second_sa', 'repository_1'],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'second_sa', 2],
            ['repository', 'ibexa.site_access.config', 'default', ''],
            ['content.tree_root.location_id', 'ibexa.site_access.config', 'default', 3],
        ];
    }
}
