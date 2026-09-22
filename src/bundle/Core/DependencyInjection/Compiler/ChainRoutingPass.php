<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Core\MVC\Symfony\Routing\ChainRouter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ChainRoutingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ChainRouter::class)) {
            return;
        }

        $chainRouter = $container->getDefinition(ChainRouter::class);

        // Enforce default router to be part of the routing chain
        // The default router will be given the highest priority so that it will be used by default.
        // The SiteAccess-aware behavior is provided by \Ibexa\Bundle\Core\Routing\DefaultRouter decorating router.default.
        if ($container->hasDefinition('router.default')) {
            $defaultRouter = $container->getDefinition('router.default');
            if (!$defaultRouter->hasTag('router')) {
                $defaultRouter->addTag(
                    'router',
                    ['priority' => 255]
                );
            }
        }

        foreach ($container->findTaggedServiceIds('router') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? (int)$attributes[0]['priority'] : 0;
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
