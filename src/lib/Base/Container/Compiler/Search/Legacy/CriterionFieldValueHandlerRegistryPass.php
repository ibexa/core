<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Container\Compiler\Search\Legacy;

use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\HandlerRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Legacy Search Engine criterion field value handlers.
 */
class CriterionFieldValueHandlerRegistryPass implements CompilerPassInterface
{
    private const SEARCH_LEGACY_GATEWAY_CRITERION_HANDLER_FIELD_VALUE_TAG = 'ibexa.search.legacy.gateway.criterion_handler.field_value';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(HandlerRegistry::class)) {
            return;
        }

        $registry = $container->getDefinition(HandlerRegistry::class);

        $taggedServiceIds = $container->findTaggedServiceIds(
            self::SEARCH_LEGACY_GATEWAY_CRITERION_HANDLER_FIELD_VALUE_TAG
        );
        foreach ($taggedServiceIds as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" service tag needs an "alias" attribute to identify the Field Type.',
                            $serviceId,
                            self::SEARCH_LEGACY_GATEWAY_CRITERION_HANDLER_FIELD_VALUE_TAG
                        )
                    );
                }

                $registry->addMethodCall(
                    'register',
                    [
                        $attribute['alias'],
                        new Reference($serviceId),
                    ]
                );
            }
        }
    }
}

class_alias(CriterionFieldValueHandlerRegistryPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass');
