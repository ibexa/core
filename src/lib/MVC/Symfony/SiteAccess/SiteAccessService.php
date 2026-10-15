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
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * changeSiteAccess()/restoreSiteAccess() are the single, supported way to swap the current
 * SiteAccess (content preview, CLI --siteaccess, sub-requests). Manually dispatching
 * ScopeChangeEvent under MVCEvents::CONFIG_SCOPE_CHANGE/CONFIG_SCOPE_RESTORE is deprecated and
 * only kept as a fallback for not-yet-migrated listeners — see onConfigScopeChange().
 */
class SiteAccessService implements SiteAccessServiceInterface, EventSubscriberInterface
{
    /** @var list<\Ibexa\Core\MVC\Symfony\SiteAccess> */
    private array $siteAccessStack = [];

    private bool $isDispatchingInternally = false;

    public function __construct(
        private readonly SiteAccessProviderInterface $provider,
        private readonly ConfigResolverInterface $configResolver,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::SITEACCESS => 'onSiteAccessMatch',
            MVCEvents::CONFIG_SCOPE_CHANGE => 'onConfigScopeChange',
            MVCEvents::CONFIG_SCOPE_RESTORE => 'onConfigScopeRestore',
        ];
    }

    /**
     * Establishes the base of the SiteAccess stack for a new top-level request. Sub-requests are
     * NOT handled here: the listener matching their SiteAccess calls changeSiteAccess() directly,
     * same as any other caller.
     */
    public function onSiteAccessMatch(PostSiteAccessMatchEvent $event): void
    {
        if ($event->getRequestType() !== HttpKernelInterface::MAIN_REQUEST) {
            return;
        }

        $this->siteAccessStack = [$event->getSiteAccess()];
    }

    /**
     * @deprecated 6.0, to be removed in 7.0. Dispatching ScopeChangeEvent manually under
     *             MVCEvents::CONFIG_SCOPE_CHANGE is deprecated; call changeSiteAccess() instead,
     *             which dispatches this event for you. Kept so not-yet-migrated listeners keep
     *             working, but no longer the supported way to change the current SiteAccess.
     */
    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        if ($this->isDispatchingInternally) {
            return;
        }

        trigger_deprecation(
            'ibexa/core',
            '6.0',
            'Dispatching %s manually under MVCEvents::CONFIG_SCOPE_CHANGE is deprecated and will no longer be reflected by %s::getCurrent() in 7.0. Call %s::changeSiteAccess() instead.',
            ScopeChangeEvent::class,
            self::class,
            SiteAccessServiceInterface::class
        );

        $this->siteAccessStack[] = $event->getSiteAccess();
    }

    /**
     * @deprecated 6.0, to be removed in 7.0. See onConfigScopeChange().
     */
    public function onConfigScopeRestore(ScopeChangeEvent $event): void
    {
        if ($this->isDispatchingInternally) {
            return;
        }

        trigger_deprecation(
            'ibexa/core',
            '6.0',
            'Dispatching %s manually under MVCEvents::CONFIG_SCOPE_RESTORE is deprecated and will no longer be reflected by %s::getCurrent() in 7.0. Call %s::restoreSiteAccess() instead.',
            ScopeChangeEvent::class,
            self::class,
            SiteAccessServiceInterface::class
        );

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
        if ($this->siteAccessStack === []) {
            return null;
        }

        // Defensive: a SiteAccess with an "uninitialized" matcher (see
        // SiteAccess::MATCHING_TYPE_UNINITIALIZED) is not a real current SiteAccess even if it
        // somehow ended up on the stack — surface it as null rather than a misleading value.
        $current = end($this->siteAccessStack);

        return $current->matchingType !== SiteAccess::MATCHING_TYPE_UNINITIALIZED ? $current : null;
    }

    public function changeSiteAccess(SiteAccess $siteAccess): SiteAccess
    {
        $this->siteAccessStack[] = $siteAccess;
        $this->dispatchScopeChangeEvent($siteAccess, MVCEvents::CONFIG_SCOPE_CHANGE);

        return $siteAccess;
    }

    public function restoreSiteAccess(): ?SiteAccess
    {
        $this->popSiteAccessStack();
        $siteAccess = $this->getCurrent();
        if ($siteAccess !== null) {
            $this->dispatchScopeChangeEvent($siteAccess, MVCEvents::CONFIG_SCOPE_RESTORE);
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
     * (the current top-level request's SiteAccess) must survive an unbalanced restore.
     */
    private function popSiteAccessStack(): void
    {
        if (count($this->siteAccessStack) > 1) {
            array_pop($this->siteAccessStack);
        }
    }

    /**
     * Dispatches $eventName with isDispatchingInternally set, so onConfigScopeChange()/
     * onConfigScopeRestore() can tell this dispatch apart from a manually-constructed one and
     * skip reacting to it (the stack mutation already happened above).
     */
    private function dispatchScopeChangeEvent(SiteAccess $siteAccess, string $eventName): void
    {
        $this->isDispatchingInternally = true;
        try {
            $this->eventDispatcher->dispatch(new ScopeChangeEvent($siteAccess), $eventName);
        } finally {
            $this->isDispatchingInternally = false;
        }
    }
}
