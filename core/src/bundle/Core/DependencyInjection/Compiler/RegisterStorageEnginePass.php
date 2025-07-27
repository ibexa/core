<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\ApiLoader\StorageEngineFactory;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterStorageEnginePass implements CompilerPassInterface
{
    public const string STORAGE_ENGINE_TAG = 'ibexa.storage';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(StorageEngineFactory::class)) {
            return;
        }

        $storageEngineFactoryDef = $container->getDefinition(StorageEngineFactory::class);
        foreach ($container->findTaggedServiceIds(self::STORAGE_ENGINE_TAG) as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" service tag needs an "alias" ' .
                            'attribute to identify the storage engine.',
                            $serviceId,
                            self::STORAGE_ENGINE_TAG
                        )
                    );
                }

                $storageEngineFactoryDef->addMethodCall(
                    'registerStorageEngine',
                    [
                        new Reference($serviceId),
                        $attribute['alias'],
                    ]
                );
            }
        }
    }
}
