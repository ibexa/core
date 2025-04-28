<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Ibexa\Core\IO\IOMetadataHandler\LegacyDFSCluster as LegacyDFSClusterHandler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition as ServiceDefinition;
use Symfony\Component\DependencyInjection\Reference;

class LegacyDFSCluster implements ConfigurationFactory
{
    public function getParentServiceId(): string
    {
        return LegacyDFSClusterHandler::class;
    }

    public function configureHandler(ContainerBuilder $container, ServiceDefinition $serviceDefinition, array $config): void
    {
        $serviceDefinition->replaceArgument(0, new Reference($config['connection']));
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->info(
                'A MySQL based handler, compatible with the legacy DFS one, that stores metadata in the ezdfsfile table'
            )
            ->children()
                ->scalarNode('connection')
                    ->info('Doctrine connection service')
                    ->example('doctrine.dbal.cluster_connection')
                ->end()
            ->end();
    }
}
