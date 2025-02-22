<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler;

use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\Null\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Ibexa Field Types.
 */
class FieldTypeRegistryPass extends AbstractFieldTypeBasedPass
{
    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FieldTypeRegistry::class)) {
            return;
        }

        $fieldTypeRegistryDefinition = $container->getDefinition(FieldTypeRegistry::class);

        foreach ($this->getFieldTypeServiceIds($container) as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $fieldTypeRegistryDefinition->addMethodCall(
                    'registerFieldType',
                    [
                        $attribute['alias'],
                        new Reference($id),
                    ]
                );

                // Add FieldType to the "concrete" list if it's not a fake.
                $class = $container->findDefinition($id)->getClass();
                if ($class === null || !is_a($class, Type::class, true)) {
                    $fieldTypeRegistryDefinition->addMethodCall(
                        'registerConcreteFieldTypeIdentifier',
                        [$attribute['alias']]
                    );
                }
            }
        }
    }
}
