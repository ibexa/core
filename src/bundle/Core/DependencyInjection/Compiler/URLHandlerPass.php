<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\URLChecker\URLHandlerRegistry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class URLHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(URLHandlerRegistry::class)) {
            return;
        }

        $definition = $container->findDefinition(URLHandlerRegistry::class);
        foreach ($container->findTaggedServiceIds('ibexa.url_checker.handler') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['scheme'])) {
                    throw new LogicException(sprintf(
                        '%s service tag needs a "scheme" attribute to identify which scheme is supported by the handler.',
                        'ibexa.url_checker.handler'
                    ));
                }

                $definition->addMethodCall('addHandler', [
                    $attribute['scheme'],
                    new Reference($id),
                ]);
            }
        }
    }
}
