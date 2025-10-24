<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Search\Legacy;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SortClauseConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !$container->hasDefinition('ibexa.search.legacy.gateway.sort_clause_converter.content') &&
            !$container->hasDefinition('ibexa.search.legacy.gateway.sort_clause_converter.location') &&
            !$container->hasDefinition('ibexa.core.trash.search.legacy.gateway.sort_clause_converter')
        ) {
            return;
        }

        if ($container->hasDefinition('ibexa.search.legacy.gateway.sort_clause_converter.content')) {
            $sortClauseConverterContent = $container->getDefinition('ibexa.search.legacy.gateway.sort_clause_converter.content');

            $contentHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.gateway.sort_clause_handler.content');

            $this->addHandlers($sortClauseConverterContent, $contentHandlers);
        }

        if ($container->hasDefinition('ibexa.search.legacy.gateway.sort_clause_converter.location')) {
            $sortClauseConverterLocation = $container->getDefinition('ibexa.search.legacy.gateway.sort_clause_converter.location');

            $locationHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.gateway.sort_clause_handler.location');

            $this->addHandlers($sortClauseConverterLocation, $locationHandlers);
        }

        if ($container->hasDefinition('ibexa.core.trash.search.legacy.gateway.sort_clause_converter')) {
            $sortClauseConverterTrash = $container->getDefinition('ibexa.core.trash.search.legacy.gateway.sort_clause_converter');

            $trashHandlers = $container->findTaggedServiceIds('ibexa.search.legacy.trash.gateway.sort_clause.handler');

            $this->addHandlers($sortClauseConverterTrash, $trashHandlers);
        }
    }

    /**
     * @param array<string, array<array<string, mixed>>> $handlers
     */
    protected function addHandlers(
        Definition $definition,
        array $handlers
    ): void {
        foreach ($handlers as $id => $attributes) {
            $definition->addMethodCall('addHandler', [new Reference($id)]);
        }
    }
}
