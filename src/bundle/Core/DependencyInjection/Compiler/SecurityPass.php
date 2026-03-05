<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authentication\AnonymousUserAccessListener;
use Ibexa\Core\MVC\Symfony\Security\Authentication\DefaultAuthenticationSuccessHandler;
use Ibexa\Core\MVC\Symfony\Security\HttpUtils;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Security related compiler pass.
 * Manipulates Symfony core security services to adapt them to Ibexa security needs.
 */
final class SecurityPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $this->registerHttpUtils($container);
        $this->registerSecurityAuthenticationSuccessHandler($container);
        $this->registerAnonymousUserAccessListener($container);
    }

    private function registerHttpUtils(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('security.http_utils')) {
            return;
        }

        $httpUtilsDef = $container->findDefinition('security.http_utils');
        $httpUtilsDef->setClass(HttpUtils::class);
        $httpUtilsDef->addMethodCall(
            'setSiteAccess',
            [new Reference(SiteAccess::class)]
        );
    }

    private function registerSecurityAuthenticationSuccessHandler(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('security.authentication.success_handler')) {
            return;
        }

        $successHandlerDef = $container->getDefinition('security.authentication.success_handler');
        $successHandlerDef->setClass(DefaultAuthenticationSuccessHandler::class);
        $successHandlerDef->addMethodCall(
            'setConfigResolver',
            [new Reference(ConfigResolverInterface::class)]
        );
        $successHandlerDef->addMethodCall(
            'setEventDispatcher',
            [new Reference('event_dispatcher')]
        );
    }

    private function registerAnonymousUserAccessListener(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(AnonymousUserAccessListener::class)) {
            return;
        }

        $anonymousUserAccessListenerDef = $container->getDefinition(AnonymousUserAccessListener::class);
        $anonymousUserAccessListenerDef->setArgument(
            '$firewallLoginPaths',
            $this->getFirewallLoginPaths($container)
        );
    }

    /**
     * Retrieves the login paths for all form authenticators.
     *
     * @return array<string, string>
     */
    private function getFirewallLoginPaths(ContainerBuilder $container): array
    {
        $firewallLoginPaths = [];

        /** @var string[] $firewalls */
        $firewalls = $container->getParameter('security.firewalls') ?? [];

        foreach ($firewalls as $firewall) {
            if (!$container->hasDefinition('security.authenticator.form_login.' . $firewall)) {
                continue;
            }

            $firewallConfig = $container->getDefinition('security.authenticator.form_login.' . $firewall);

            try {
                $firewallConfigKeys = $firewallConfig->getArgument(4);
                if (!isset($firewallConfigKeys['login_path'])) {
                    continue;
                }

                $firewallLoginPaths[$firewall] = $firewallConfigKeys['login_path'];
            } catch (OutOfBoundsException) {
                continue;
            }
        }

        return $firewallLoginPaths;
    }
}
