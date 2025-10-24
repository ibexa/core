<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use const PHP_SAPI;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ConsoleCacheWarmupPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (PHP_SAPI !== 'cli' ||
            !$container->hasDefinition('kernel.class_cache.cache_warmer')) {
            return;
        }

        $warmers = [];
        foreach ($container->findTaggedServiceIds('kernel.cache_warmer') as $id => $attributes) {
            if ($id === 'kernel.class_cache.cache_warmer') {
                continue;
            }

            $priority = $attributes[0]['priority'] ?? 0;
            $warmers[$priority][] = new Reference($id);
        }

        if (empty($warmers)) {
            return;
        }

        krsort($warmers);
        $warmers = array_merge(...$warmers);

        $container->getDefinition('cache_warmer')->replaceArgument(0, $warmers);
    }
}
