<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Core\MVC\Symfony\SiteAccess;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Core\Base\Exceptions\NotFoundException;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use function iterator_to_array;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class SiteAccessService implements SiteAccessServiceInterface, EventSubscriberInterface
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess[] */
    private array $siteAccessStack;

    /**
     * @param \Ibexa\Core\MVC\Symfony\SiteAccess $siteAccess the shared, container-wide default
     *        SiteAccess, used as the initial stack entry until a real request (or an explicit
     *        changeSiteAccess() call) establishes the actual current one. This preserves
     *        getCurrent()'s pre-existing guarantee of never being null once the container is built,
     *        which code such as ComplexConfigProcessor relies on outside of an HTTP request cycle
     *        (CLI warm-up, integration tests, etc.).
     */
    public function __construct(
        private readonly SiteAccessProviderInterface $provider,
        private readonly ConfigResolverInterface $configResolver,
        private readonly EventDispatcherInterface $eventDispatcher,
        SiteAccess $siteAccess
    ) {
        $this->siteAccessStack = [$siteAccess];
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::SITEACCESS => 'onSiteAccessMatch',
            KernelEvents::FINISH_REQUEST => 'onKernelFinishRequest',
        ];
    }

    /**
     * Establishes the base of the SiteAccess stack for a top-level request, or pushes an
     * additional entry for a sub-request (e.g. content preview, fragments, ESI).
     */
    public function onSiteAccessMatch(PostSiteAccessMatchEvent $event): void
    {
        if ($event->getRequestType() === HttpKernelInterface::MAIN_REQUEST) {
            $this->siteAccessStack = [$event->getSiteAccess()];
        } else {
            $this->siteAccessStack[] = $event->getSiteAccess();
        }
    }

    /**
     * Undoes the push performed by onSiteAccessMatch() for the request/sub-request that just finished.
     */
    public function onKernelFinishRequest(FinishRequestEvent $event): void
    {
        $this->popSiteAccessStack();
    }

    public function exists(string $name): bool
    {
        return $this->provider->isDefined($name);
    }

    public function get(string $name): SiteAccess
    {
        if ($this->provider->isDefined($name)) {
            return $this->provider->getSiteAccess($name);
        }

        throw new NotFoundException('SiteAccess', $name);
    }

    public function getAll(): iterable
    {
        return $this->provider->getSiteAccesses();
    }

    public function getCurrent(): ?SiteAccess
    {
        return $this->siteAccessStack !== [] ? end($this->siteAccessStack) : null;
    }

    public function changeSiteAccess(SiteAccess $siteAccess): SiteAccess
    {
        $this->siteAccessStack[] = $siteAccess;
        $this->eventDispatcher->dispatch(new ScopeChangeEvent($siteAccess), MVCEvents::CONFIG_SCOPE_CHANGE);

        return $siteAccess;
    }

    public function restoreSiteAccess(): ?SiteAccess
    {
        $this->popSiteAccessStack();
        $siteAccess = $this->getCurrent();
        if ($siteAccess !== null) {
            $this->eventDispatcher->dispatch(new ScopeChangeEvent($siteAccess), MVCEvents::CONFIG_SCOPE_RESTORE);
        }

        return $siteAccess;
    }

    public function getSiteAccessesRelation(?SiteAccess $siteAccess = null): array
    {
        $siteAccess ??= $this->getCurrent();
        if ($siteAccess === null) {
            throw new InvalidArgumentException('siteAccess', 'no SiteAccess given and none currently set');
        }

        $saRelationMap = [];

        /** @var \Ibexa\Core\MVC\Symfony\SiteAccess[] $saList */
        $saList = iterator_to_array($this->provider->getSiteAccesses());
        // First build the SiteAccess relation map, indexed by repository and rootLocationId.
        foreach ($saList as $sa) {
            $siteAccessName = $sa->name;

            $repository = $this->configResolver->getParameter('repository', 'ibexa.site_access.config', $siteAccessName);
            if (!isset($saRelationMap[$repository])) {
                $saRelationMap[$repository] = [];
            }

            $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', 'ibexa.site_access.config', $siteAccessName);
            if (!isset($saRelationMap[$repository][$rootLocationId])) {
                $saRelationMap[$repository][$rootLocationId] = [];
            }

            $saRelationMap[$repository][$rootLocationId][] = $siteAccessName;
        }

        $siteAccessName = $siteAccess->name;
        $repository = $this->configResolver->getParameter('repository', 'ibexa.site_access.config', $siteAccessName);
        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id', 'ibexa.site_access.config', $siteAccessName);

        return $saRelationMap[$repository][$rootLocationId];
    }

    /**
     * Pops the SiteAccess stack, but never below one remaining entry: the bottom-most entry
     * (the current top-level request's SiteAccess, or the CLI SiteAccess established via
     * changeSiteAccess()) must survive an unbalanced restore or an unrelated sub-request finishing.
     */
    private function popSiteAccessStack(): void
    {
        if (count($this->siteAccessStack) > 1) {
            array_pop($this->siteAccessStack);
        }
    }
}
