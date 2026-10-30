<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\Flysystem as BaseFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition as ServiceDefinition;
use Symfony\Component\DependencyInjection\Reference;

class Flysystem extends BaseFactory
{
    public function getParentServiceId()
    {
        return 'ibexa.core.io.binarydata_handler.flysystem';
    }

    public function addConfiguration(ArrayNodeDefinition $node)
    {
        parent::addConfiguration($node);

        $node
            ->children()
                ->scalarNode('range_reader')
                    ->info(
                        'Service id of an Ibexa\Core\IO\Flysystem\RangeReader\RangeReaderInterface implementation ' .
                        'serving HTTP Range requests natively from the storage.'
                    )
                    ->example('app.io.aws_s3_range_reader')
                    ->validate()
                        ->ifTrue(static fn ($value): bool => $value !== null && (!is_string($value) || $value === ''))
                        ->thenInvalid('range_reader must be a service id, got %s')
                    ->end()
                ->end()
            ->end();
    }

    /**
     * @param array<string, mixed> $config
     */
    public function configureHandler(
        ServiceDefinition $definition,
        array $config
    ) {
        parent::configureHandler($definition, $config);

        if (isset($config['range_reader'])) {
            $definition->setArgument('$rangeReader', new Reference($config['range_reader']));
        }
    }
}

class_alias(Flysystem::class, 'eZ\Bundle\EzPublishIOBundle\DependencyInjection\ConfigurationFactory\BinarydataHandler\Flysystem');
