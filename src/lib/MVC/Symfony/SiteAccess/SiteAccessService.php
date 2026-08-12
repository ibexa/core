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
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use function iterator_to_array;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteAccessService implements SiteAccessServiceInterface, SiteAccessAware, EventSubscriberInterface
{
    /** @var \Ibexa\Core\MVC\Symfony\SiteAccess[] */
    private array $siteAccessStack = [];

    public function __construct(
        private readonly SiteAccessProviderInterface $provider,
        private readonly ConfigResolverInterface $configResolver
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::CONFIG_SCOPE_CHANGE => 'onConfigScopeChange',
            MVCEvents::CONFIG_SCOPE_RESTORE => 'onConfigScopeRestore',
        ];
    }

    public function setSiteAccess(?SiteAccess $siteAccess = null): void
    {
        $this->siteAccessStack = $siteAccess !== null ? [$siteAccess] : [];
    }

    /**
     * Pushes the new SiteAccess onto the stack, so that getCurrent() reflects it until the
     * matching restore happens.
     */
    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $this->siteAccessStack[] = $event->getSiteAccess();
    }

    /**
     * Pops the SiteAccess pushed by the matching onConfigScopeChange(), but never below one
     * remaining entry: the bottom-most entry (the current request's SiteAccess) must survive an
     * unbalanced restore.
     */
    public function onConfigScopeRestore(ScopeChangeEvent $event): void
    {
        if (count($this->siteAccessStack) <= 1) {
            return;
        }

        array_pop($this->siteAccessStack);
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

    public function getSiteAccessesRelation(?SiteAccess $siteAccess = null): array
    {
        $siteAccess = $siteAccess ?? $this->getCurrent();
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
}
