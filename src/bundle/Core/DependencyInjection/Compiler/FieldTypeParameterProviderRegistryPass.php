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
 * This compiler pass will register eZ Publish field type parameter providers.
 */
class FieldTypeParameterProviderRegistryPass implements CompilerPassInterface
{
    public const FIELD_TYPE_PARAMETER_PROVIDER_SERVICE_TAG = 'ezplatform.field_type.parameter_provider';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('ezpublish.fieldType.parameterProviderRegistry')) {
            return;
        }

        $parameterProviderRegistryDef = $container->getDefinition('ezpublish.fieldType.parameterProviderRegistry');

        $serviceTags = $container->findTaggedServiceIds(
            self::FIELD_TYPE_PARAMETER_PROVIDER_SERVICE_TAG
        );
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new \LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" service tag needs an "alias" attribute to identify the Field Type.',
                            $serviceId,
                            self::FIELD_TYPE_PARAMETER_PROVIDER_SERVICE_TAG
                        )
                    );
                }

                $parameterProviderRegistryDef->addMethodCall(
                    'setParameterProvider',
                    [
                        // Only pass the service Id since field types will be lazy loaded via the service container
                        new Reference($serviceId),
                        $attribute['alias'],
                    ]
                );
            }
        }
    }
}

class_alias(FieldTypeParameterProviderRegistryPass::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\FieldTypeParameterProviderRegistryPass');
