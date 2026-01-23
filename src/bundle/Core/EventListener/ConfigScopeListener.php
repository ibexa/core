<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\EventListener;

use Ibexa\Contracts\Core\MVC\EventSubscriber\ConfigScopeChangeSubscriber;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Configuration\VersatileScopeInterface;
use Ibexa\Core\MVC\Symfony\Event\ScopeChangeEvent;
use Ibexa\Core\MVC\Symfony\MVCEvents;
use Ibexa\Core\MVC\Symfony\SiteAccess\SiteAccessAware;
use Ibexa\Core\MVC\Symfony\View\ViewManagerInterface;
use Ibexa\Core\MVC\Symfony\View\ViewProvider;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigScopeListener implements EventSubscriberInterface, ConfigScopeChangeSubscriber
{
    /** @var ConfigResolverInterface[] */
    private $configResolvers;

    /** @var ViewManagerInterface|SiteAccessAware */
    private $viewManager;

    /** @var ViewProvider[]|SiteAccessAware[] */
    private $viewProviders;

    public function __construct(
        iterable $configResolvers,
        ViewManagerInterface $viewManager
    ) {
        $this->configResolvers = $configResolvers;
        $this->viewManager = $viewManager;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            MVCEvents::CONFIG_SCOPE_CHANGE => ['onConfigScopeChange', 100],
            MVCEvents::CONFIG_SCOPE_RESTORE => ['onConfigScopeChange', 100],
        ];
    }

    public function onConfigScopeChange(ScopeChangeEvent $event): void
    {
        $siteAccess = $event->getSiteAccess();

        foreach ($this->configResolvers as $configResolver) {
            if ($configResolver instanceof VersatileScopeInterface) {
                $configResolver->setDefaultScope($siteAccess->name);
            }
        }

        if ($this->viewManager instanceof SiteAccessAware) {
            $this->viewManager->setSiteAccess($siteAccess);
        }

        foreach ($this->viewProviders as $viewProvider) {
            if ($viewProvider instanceof SiteAccessAware) {
                $viewProvider->setSiteAccess($siteAccess);
            }
        }
    }

    /**
     * Sets the complete list of view providers.
     */
    public function setViewProviders(array $viewProviders)
    {
        $this->viewProviders = $viewProviders;
    }
}
