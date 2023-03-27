<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\MVC\Symfony\Security\Authentication;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler as BaseSuccessHandler;

class DefaultAuthenticationSuccessHandler extends BaseSuccessHandler
{
    private EventDispatcherInterface $eventDispatcher;

    private ConfigResolverInterface $configResolver;

    /**
     * Injects the ConfigResolver to potentially override default_target_path for redirections after authentication success.
     */
    public function setConfigResolver(ConfigResolverInterface $configResolver): void
    {
        $this->configResolver = $configResolver;
    }

    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): void
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    protected function determineTargetUrl(Request $request)
    {
        if (isset($this->configResolver)) {
            $defaultPage = $this->configResolver->getParameter('default_page');
            if ($defaultPage !== null) {
                $this->options['default_target_path'] = $defaultPage;
            }
        }

        if (isset($this->eventDispatcher)) {
            $event = new DetermineTargetUrlEvent($request, $this->options, $this->getFirewallName());
            $this->eventDispatcher->dispatch($event);

            $this->options = $event->getOptions();
        }

        return parent::determineTargetUrl($request);
    }
}

class_alias(DefaultAuthenticationSuccessHandler::class, 'eZ\Publish\Core\MVC\Symfony\Security\Authentication\DefaultAuthenticationSuccessHandler');
