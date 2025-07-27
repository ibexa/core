<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Search\Legacy;

use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CriteriaConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('ibexa.search.legacy.gateway.criteria_converter.content') &&
            !$container->hasDefinition('ibexa.search.legacy.gateway.criteria_converter.location') &&
            !$container->hasDefinition('ibexa.core.trash.search.legacy.gateway.criteria_converter') &&
            !$container->hasDefinition(CriteriaConverter::class)
        ) {
            return;
        }

        if ($container->hasDefinition('ibexa.search.legacy.gateway.criteria_converter.content')) {
            $criteriaConverterContent = $container->getDefinition('ibexa.search.legacy.gateway.criteria_converter.content');

            $contentHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.gateway.criterion_handler.content');

            $this->addHandlers($criteriaConverterContent, $contentHandlers);
        }

        if ($container->hasDefinition('ibexa.search.legacy.gateway.criteria_converter.location')) {
            $criteriaConverterLocation = $container->getDefinition('ibexa.search.legacy.gateway.criteria_converter.location');

            $locationHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.gateway.criterion_handler.location');

            $this->addHandlers($criteriaConverterLocation, $locationHandlers);
        }

        if ($container->hasDefinition('ibexa.core.trash.search.legacy.gateway.criteria_converter')) {
            $trashCriteriaConverter = $container->getDefinition('ibexa.core.trash.search.legacy.gateway.criteria_converter');
            $trashCriteriaHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.trash.gateway.criterion.handler');

            $this->addHandlers($trashCriteriaConverter, $trashCriteriaHandlers);
        }

        if ($container->hasDefinition(CriteriaConverter::class)) {
            $urlCriteriaConverter = $container->getDefinition(CriteriaConverter::class);
            $urlCriteriaHandlers = $container->findTaggedServiceIds('ibexa.storage.legacy.url.criterion.handler');

            $this->addHandlers($urlCriteriaConverter, $urlCriteriaHandlers);
        }
    }

    /**
     * @param array<string, array<array<string, mixed>>> $handlers
     */
    protected function addHandlers(Definition $definition, array $handlers): void
    {
        foreach ($handlers as $id => $attributes) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
