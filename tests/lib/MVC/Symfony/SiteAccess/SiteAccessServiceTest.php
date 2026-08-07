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
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SiteAccessServiceTest extends TestCase
{
    private const EXISTING_SA_NAME = 'existing_sa';
    private const UNDEFINED_SA_NAME = 'undefined_sa';
    private const SA_GROUP = 'group';

    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var \Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configResolver;

    /** @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

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
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->siteAccess = new SiteAccess('current');
        $this->availableSiteAccesses = $this->getAvailableSitAccesses(['current', 'first_sa', 'second_sa', 'default']);
        $this->configResolverParameters = $this->getConfigResolverParameters();
    }

    public function testGetCurrentSiteAccessIsNullWhenStackIsEmpty(): void
    {
        $service = $this->createSiteAccessService();

        self::assertNull($service->getCurrent());
    }

    /**
     * A SiteAccess with an "uninitialized" matcher (see SiteAccess::MATCHING_TYPE_UNINITIALIZED)
     * is not a real current SiteAccess — getCurrent() must surface it as null.
     */
    public function testGetCurrentSiteAccessIsNullWhenTopOfStackIsUninitialized(): void
    {
        $service = $this->createSiteAccessService();

        $service->changeSiteAccess(new SiteAccess('default', SiteAccess::MATCHING_TYPE_UNINITIALIZED));

        self::assertNull($service->getCurrent());
    }

    public function testGetCurrentSiteAccessAfterMainRequestMatch(): void
    {
        $service = $this->createSiteAccessService();

        $siteAccess = new SiteAccess('default');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($siteAccess, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        self::assertSame($siteAccess, $service->getCurrent());
    }

    public function testGetSiteAccess(): void
    {
        $staticSiteAccessProvider = new StaticSiteAccessProvider(
            [self::EXISTING_SA_NAME],
            [self::EXISTING_SA_NAME => [self::SA_GROUP]],
        );
        $service = new SiteAccessService(
            $staticSiteAccessProvider,
            $this->createMock(ConfigResolverInterface::class),
            $this->eventDispatcher
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
            $this->createMock(ConfigResolverInterface::class),
            $this->eventDispatcher
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

    /**
     * changeSiteAccess()/restoreSiteAccess() must behave as a real LIFO stack, and each call
     * must dispatch a ScopeChangeEvent carrying the relevant SiteAccess under the right event name.
     */
    public function testChangeSiteAccessAndRestoreSiteAccessBehaveAsLifoStack(): void
    {
        $service = $this->createSiteAccessService();

        $original = new SiteAccess('original');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($original, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        $first = new SiteAccess('first_change');
        $second = new SiteAccess('second_change');

        $this->eventDispatcher
            ->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [self::equalTo(new ScopeChangeEvent($first)), MVCEvents::CONFIG_SCOPE_CHANGE],
                [self::equalTo(new ScopeChangeEvent($second)), MVCEvents::CONFIG_SCOPE_CHANGE],
                [self::equalTo(new ScopeChangeEvent($first)), MVCEvents::CONFIG_SCOPE_RESTORE],
                [self::equalTo(new ScopeChangeEvent($original)), MVCEvents::CONFIG_SCOPE_RESTORE]
            );

        self::assertSame($first, $service->changeSiteAccess($first));
        self::assertSame($first, $service->getCurrent());

        self::assertSame($second, $service->changeSiteAccess($second));
        self::assertSame($second, $service->getCurrent());

        self::assertSame($first, $service->restoreSiteAccess());
        self::assertSame($first, $service->getCurrent());

        self::assertSame($original, $service->restoreSiteAccess());
        self::assertSame($original, $service->getCurrent());
    }

    public function testOnSiteAccessMatchWithMainRequestResetsTheStack(): void
    {
        $service = $this->createSiteAccessService();

        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent(new SiteAccess('first'), new Request(), HttpKernelInterface::MAIN_REQUEST)
        );
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent(new SiteAccess('sub'), new Request(), HttpKernelInterface::SUB_REQUEST)
        );

        $second = new SiteAccess('second');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($second, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        self::assertSame($second, $service->getCurrent());

        // The stack was fully reset by the second MAIN_REQUEST match: an unbalanced
        // finish-request must not be able to reveal any of the previous entries.
        $service->onKernelFinishRequest($this->createFinishRequestEvent());
        self::assertSame($second, $service->getCurrent());
    }

    public function testOnSiteAccessMatchWithSubRequestPushesWithoutResetting(): void
    {
        $service = $this->createSiteAccessService();

        $main = new SiteAccess('main');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($main, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        $sub = new SiteAccess('sub');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($sub, new Request(), HttpKernelInterface::SUB_REQUEST)
        );

        self::assertSame($sub, $service->getCurrent());

        $service->onKernelFinishRequest($this->createFinishRequestEvent());
        self::assertSame($main, $service->getCurrent());
    }

    public function testOnKernelFinishRequestNeverPopsBelowOneRemainingEntry(): void
    {
        $service = $this->createSiteAccessService();

        $original = new SiteAccess('original');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($original, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        // Simulate an extra, unbalanced finish-request event.
        $service->onKernelFinishRequest($this->createFinishRequestEvent());

        self::assertSame($original, $service->getCurrent());
    }

    /**
     * Sub-request nesting scenario: a preview scope change followed by a sub-request (e.g. a
     * fragment/ESI render) must unwind back to the preview scope once the sub-request finishes,
     * and restoreSiteAccess() must then bring back the original SiteAccess.
     */
    public function testSubRequestNestingScenario(): void
    {
        $service = $this->createSiteAccessService();

        $original = new SiteAccess('original');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($original, new Request(), HttpKernelInterface::MAIN_REQUEST)
        );

        $preview = new SiteAccess('preview');
        $service->changeSiteAccess($preview);
        self::assertSame($preview, $service->getCurrent());

        $subSiteAccess = new SiteAccess('sub');
        $service->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($subSiteAccess, new Request(), HttpKernelInterface::SUB_REQUEST)
        );
        self::assertSame($subSiteAccess, $service->getCurrent());

        $service->onKernelFinishRequest($this->createFinishRequestEvent());
        self::assertSame($preview, $service->getCurrent());

        self::assertSame($original, $service->restoreSiteAccess());
        self::assertSame($original, $service->getCurrent());
    }

    private function createFinishRequestEvent(): FinishRequestEvent
    {
        return new FinishRequestEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST
        );
    }

    private function createSiteAccessService(): SiteAccessService
    {
        return new SiteAccessService(
            $this->provider,
            $this->configResolver,
            $this->eventDispatcher
        );
    }

    private function getSiteAccessService(): SiteAccessService
    {
        $siteAccessService = $this->createSiteAccessService();
        $siteAccessService->onSiteAccessMatch(
            new PostSiteAccessMatchEvent($this->siteAccess, new Request(), HttpKernelInterface::MAIN_REQUEST)
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
