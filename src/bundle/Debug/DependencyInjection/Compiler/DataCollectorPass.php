<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Debug\DependencyInjection\Compiler;

use Ibexa\Bundle\Debug\Collector\IbexaCoreCollector;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(IbexaCoreCollector::class)) {
            return;
        }

        $dataCollectorDef = $container->getDefinition(IbexaCoreCollector::class);
        $collectors = [];

        foreach ($container->findTaggedServiceIds('ibexa.debug.data_collector') as $id => $tags) {
            foreach ($tags as $attributes) {
                $priority = $attributes['priority'] ?? 0;
                $collectors[] = [
                    'id' => $id,
                    'priority' => $priority,
                    'panelTemplate' => $attributes['panelTemplate'] ?? null,
                    'toolbarTemplate' => $attributes['toolbarTemplate'] ?? null,
                ];
            }
        }

        /** @var array<int, mixed> $collectors */
        usort($collectors, static fn (array $a, array $b): int => $b['priority'] <=> $a['priority']);

        foreach ($collectors as $collector) {
            $dataCollectorDef->addMethodCall('addCollector', [
                new Reference($collector['id']),
                $collector['panelTemplate'],
                $collector['toolbarTemplate'],
            ]);
        }
    }
}

class_alias(DataCollectorPass::class, 'eZ\Bundle\EzPublishDebugBundle\DependencyInjection\Compiler\DataCollectorPass');
