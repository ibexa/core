<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Imagine\PlaceholderProviderRegistry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PlaceholderProviderPass implements CompilerPassInterface
{
    public const string TAG_NAME = 'ibexa.media.images.placeholder.provider';
    public const string REGISTRY_DEFINITION_ID = PlaceholderProviderRegistry::class;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::REGISTRY_DEFINITION_ID)) {
            return;
        }

        $definition = $container->getDefinition(self::REGISTRY_DEFINITION_ID);
        foreach ($container->findTaggedServiceIds(self::TAG_NAME) as $id => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['type'])) {
                    throw new LogicException(self::TAG_NAME . ' service tag needs a "type" attribute to identify the placeholder provider type.');
                }

                $definition->addMethodCall(
                    'addProvider',
                    [$attribute['type'], new Reference($id)]
                );
            }
        }
    }
}
