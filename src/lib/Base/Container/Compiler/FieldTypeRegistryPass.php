<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler;

use Ibexa\Core\FieldType\FieldTypeAliasRegistry;
use Ibexa\Core\FieldType\FieldTypeRegistry;
use Ibexa\Core\FieldType\Null\Type;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeRegistryPass extends AbstractFieldTypeBasedPass
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FieldTypeRegistry::class)) {
            return;
        }

        $aliasRegistryDefinition = new Definition(FieldTypeAliasRegistry::class);
        $container->setDefinition(FieldTypeAliasRegistry::class, $aliasRegistryDefinition);

        $fieldTypeRegistryDefinition = $container->getDefinition(FieldTypeRegistry::class);
        $fieldTypeRegistryDefinition->setArgument(0, $aliasRegistryDefinition);

        foreach ($this->getFieldTypeServiceIds($container) as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $fieldTypeRegistryDefinition->addMethodCall(
                    'registerFieldType',
                    [
                        $attribute['alias'],
                        new Reference($id),
                    ]
                );

                if (isset($attribute['old_alias'])) {
                    $aliasRegistryDefinition->addMethodCall(
                        'register',
                        [
                            $attribute['old_alias'],
                            $attribute['alias'],
                        ],
                    );
                }

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
