<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\Routing\Generator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * This siteaccess listener handles routing related runtime configuration.
 */
class RoutingListener implements EventSubscriberInterface
{
    /** @var ConfigResolverInterface */
    private $configResolver;

    /** @var RouterInterface */
    private $urlAliasRouter;

    /** @var Generator */
    private $urlAliasGenerator;

    public function __construct(
        ConfigResolverInterface $configResolver,
        RouterInterface $urlAliasRouter,
        Generator $urlAliasGenerator
    ) {
        $this->configResolver = $configResolver;
        $this->urlAliasRouter = $urlAliasRouter;
        $this->urlAliasGenerator = $urlAliasGenerator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::SITEACCESS => ['onSiteAccessMatch', 200],
        ];
    }

    public function onSiteAccessMatch(PostSiteAccessMatchEvent $event)
    {
        $rootLocationId = $this->configResolver->getParameter('content.tree_root.location_id');
        $this->urlAliasRouter->setRootLocationId($rootLocationId);
        $this->urlAliasGenerator->setRootLocationId($rootLocationId);
        $this->urlAliasGenerator->setExcludedUriPrefixes($this->configResolver->getParameter('content.tree_root.excluded_uri_prefixes'));
    }
}
