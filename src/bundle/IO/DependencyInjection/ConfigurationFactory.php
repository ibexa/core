<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition as ServiceDefinition;

/**
 * Factory for IO Handlers (metadata or binarydata) configuration.
 *
 * Required to:
 * - register an io handler
 * - add custom semantic configuration below ez_io.xxx_handler.<name>.<type>
 * - customize the custom handler services, and initialize extra services definitions
 */
interface ConfigurationFactory
{
    /**
     * Adds the handler's semantic configuration.
     *
     * Example:
     * ```php
     * $node
     *   ->info('my info')->example('an example')
     *   ->children()
     *     ->scalarNode('an_argument')->info('This is an argument')
     *   ->end();
     * ```
     *
     * @param \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $node The handler's configuration node.
     */
    public function addConfiguration(ArrayNodeDefinition $node): void;

    /**
     * Returns the ID of the base, abstract service used to create the handlers.
     *
     * It will be used as the base name for instances of this handler, and as the parent of the instances' services.
     */
    public function getParentServiceId(): string;

    /**
     * Configure the handler service based on the configuration.
     *
     * Arguments or calls can be added to the $serviceDefinition, extra services or parameters can be added to the
     * container.
     *
     * @param array<string, mixed> $config
     */
    public function configureHandler(ContainerBuilder $container, ServiceDefinition $serviceDefinition, array $config): void;
}
