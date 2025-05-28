<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Persistence;

use Ibexa\Core\Base\Container\Compiler\AbstractFieldTypeBasedPass;
use Ibexa\Core\Persistence\FieldTypeRegistry;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeRegistryPass extends AbstractFieldTypeBasedPass
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FieldTypeRegistry::class)) {
            return;
        }

        $fieldTypeRegistryDefinition = $container->getDefinition(FieldTypeRegistry::class);

        foreach ($this->getFieldTypeServiceIds($container) as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $fieldTypeRegistryDefinition->addMethodCall(
                    'register',
                    [
                        $attribute['alias'],
                        new Reference($id),
                    ]
                );
            }
        }
    }
}
