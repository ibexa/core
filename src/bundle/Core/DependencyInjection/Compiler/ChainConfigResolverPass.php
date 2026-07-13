<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\ChainConfigResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @deprecated 5.0.9 "ChainConfigResolverPass" is deprecated. Use service 'ibexa.site.config.resolver' tag instead.
 */
class ChainConfigResolverPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ChainConfigResolver::class)) {
            return;
        }

        $chainResolver = $container->getDefinition(ChainConfigResolver::class);
        $references = [];

        foreach ($container->findTaggedServiceIds('ibexa.site.config.resolver') as $id => $attributes) {
            $priority = (int)($attributes[0]['priority'] ?? 0);
            if ($priority > 255) {
                $priority = 255;
            }
            if ($priority < -255) {
                $priority = -255;
            }

            $references[$priority][] = new Reference($id);
        }

        $chainResolver->setArgument('$resolvers', $references);
    }
}
