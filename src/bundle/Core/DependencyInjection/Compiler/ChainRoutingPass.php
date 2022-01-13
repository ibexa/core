<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * The ChainRoutingPass will register all services tagged as "router" to the chain router.
 */
class ChainRoutingPass implements CompilerPassInterface
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(\Ibexa\Core\MVC\Symfony\Routing\ChainRouter::class)) {
            return;
        }

        $chainRouter = $container->getDefinition(\Ibexa\Core\MVC\Symfony\Routing\ChainRouter::class);

        // Enforce default router to be part of the routing chain
        // The default router will be given the highest priority so that it will be used by default
        if ($container->hasDefinition('router.default')) {
            $defaultRouter = $container->getDefinition('router.default');
            $defaultRouter->addMethodCall('setSiteAccess', [new Reference(\Ibexa\Core\MVC\Symfony\SiteAccess::class)]);
            $defaultRouter->addMethodCall('setConfigResolver', [new Reference('ibexa.config.resolver')]);
            $defaultRouter->addMethodCall(
                'setNonSiteAccessAwareRoutes',
                ['%ezpublish.default_router.non_siteaccess_aware_routes%']
            );
            $defaultRouter->addMethodCall(
                'setSiteAccessRouter',
                [new Reference(\Ibexa\Core\MVC\Symfony\SiteAccess\Router::class)]
            );
            if (!$defaultRouter->hasTag('router')) {
                $defaultRouter->addTag(
                    'router',
                    ['priority' => 255]
                );
            }
        }

        foreach ($container->findTaggedServiceIds('router') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? (int)$attributes[0]['priority'] : 0;
            // Priority range is between -255 (the lowest) and 255 (the highest)
            if ($priority > 255) {
                $priority = 255;
            }
            if ($priority < -255) {
                $priority = -255;
            }

            $chainRouter->addMethodCall(
                'add',
                [
                    new Reference($id),
                    $priority,
                ]
            );
        }
    }
}

class_alias(ChainRoutingPass::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ChainRoutingPass');
