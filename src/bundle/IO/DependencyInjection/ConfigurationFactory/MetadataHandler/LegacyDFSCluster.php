<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition as ServiceDefinition;
use Symfony\Component\DependencyInjection\Reference;

class LegacyDFSCluster implements ConfigurationFactory
{
    public function getParentServiceId(): string
    {
        return \Ibexa\Core\IO\IOMetadataHandler\LegacyDFSCluster::class;
    }

    public function configureHandler(
        ContainerBuilder $container,
        ServiceDefinition $serviceDefinition,
        array $config
    ): void {
        $serviceDefinition->replaceArgument(0, new Reference($config['connection']));
    }

    public function addConfiguration(ArrayNodeDefinition $node): void
    {
        $node
            ->info(
                'A MySQL based handler, compatible with the legacy DFS one, that stores metadata in the ibexa_dfs_file table'
            )
            ->children()
                ->scalarNode('connection')
                    ->info('Doctrine connection service')
                    ->example('doctrine.dbal.cluster_connection')
                ->end()
            ->end();
    }
}
