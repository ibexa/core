<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\ApiLoader\StorageConnectionFactory;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class StorageConnectionFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(StorageConnectionFactory::class) || !$container->hasParameter('doctrine.connections')) {
            return;
        }

        /** @var array<string, string> $doctrineConnections a map of <code>[ connection_name => connection_service_id ]</code> */
        $doctrineConnections = $container->getParameter('doctrine.connections');
        $doctrineConnectionServices = array_map(
            static fn (string $serviceId): Reference => new Reference($serviceId),
            $doctrineConnections
        );
        $storageConnectionFactory = $container->findDefinition(StorageConnectionFactory::class);
        $storageConnectionFactory->replaceArgument(
            '$serviceLocator',
            ServiceLocatorTagPass::register($container, $doctrineConnectionServices)
        );
    }
}
