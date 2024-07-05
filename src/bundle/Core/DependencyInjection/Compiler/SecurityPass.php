<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\SiteAccess\ConfigResolverInterface;
use Ibexa\Core\MVC\Symfony\Security\Authentication\DefaultAuthenticationSuccessHandler;
use Ibexa\Core\MVC\Symfony\Security\Authentication\GuardRepositoryAuthenticationProvider;
use Ibexa\Core\MVC\Symfony\Security\Authentication\RememberMeRepositoryAuthenticationProvider;
use Ibexa\Core\MVC\Symfony\Security\HttpUtils;
use Ibexa\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Security related compiler pass.
 * Manipulates Symfony core security services to adapt them to Ibexa security needs.
 */
final class SecurityPass implements CompilerPassInterface
{
    /**
     * @deprecated 4.6.7 CONSTANT_AUTH_TIME_SETTING constant is deprecated, will be removed in 5.0.
     */
    public const string CONSTANT_AUTH_TIME_SETTING = 'ibexa.security.authentication.constant_auth_time';

    public const float CONSTANT_AUTH_TIME_DEFAULT = 1.0;

    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('security.authentication.provider.rememberme') ||
            !$container->hasDefinition('security.authentication.provider.guard')
        ) {
            return;
        }

        $permissionResolverRef = new Reference(PermissionResolver::class);

        $rememberMeAuthenticationProviderDef = $container->findDefinition('security.authentication.provider.rememberme');
        $rememberMeAuthenticationProviderDef->setClass(RememberMeRepositoryAuthenticationProvider::class);
        $rememberMeAuthenticationProviderDef->addMethodCall(
            'setPermissionResolver',
            [$permissionResolverRef]
        );

        $guardAuthenticationProviderDef = $container->findDefinition('security.authentication.provider.guard');
        $guardAuthenticationProviderDef->setClass(GuardRepositoryAuthenticationProvider::class);
        $guardAuthenticationProviderDef->addMethodCall(
            'setPermissionResolver',
            [$permissionResolverRef]
        );

        if (!$container->hasDefinition('security.http_utils')) {
            return;
        }

        $httpUtilsDef = $container->findDefinition('security.http_utils');
        $httpUtilsDef->setClass(HttpUtils::class);
        $httpUtilsDef->addMethodCall(
            'setSiteAccess',
            [new Reference(SiteAccess::class)]
        );

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
}
