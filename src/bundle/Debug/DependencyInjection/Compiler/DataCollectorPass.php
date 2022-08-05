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
        foreach ($container->findTaggedServiceIds('ibexa.debug.data_collector') as $id => $attributes) {
            foreach ($attributes as $attribute) {
                $dataCollectorDef->addMethodCall(
                    'addCollector',
                    [
                        new Reference($id),
                        isset($attribute['panelTemplate']) ? $attribute['panelTemplate'] : null,
                        isset($attribute['toolbarTemplate']) ? $attribute['toolbarTemplate'] : null,
                    ]
                );
            }
        }
    }
}

class_alias(DataCollectorPass::class, 'eZ\Bundle\EzPublishDebugBundle\DependencyInjection\Compiler\DataCollectorPass');
