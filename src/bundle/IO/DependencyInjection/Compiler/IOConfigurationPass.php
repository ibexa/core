<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection\Compiler;

use ArrayObject;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will create the metadata and binary-data IO handlers depending on the container configuration.
 */
class IOConfigurationPass implements CompilerPassInterface
{
    /** @var \ArrayObject<string , \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> */
    private ArrayObject $metadataHandlerFactories;

    /** @var \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> */
    private ArrayObject $binaryDataHandlerFactories;

    /**
     * @param \ArrayObject<string , \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> $metadataHandlerFactories
     * @param \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> $binaryDataHandlerFactories
     */
    public function __construct(ArrayObject $metadataHandlerFactories, ArrayObject $binaryDataHandlerFactories)
    {
        $this->metadataHandlerFactories = $metadataHandlerFactories;
        $this->binaryDataHandlerFactories = $binaryDataHandlerFactories;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     *
     * @throws \LogicException
     */
    public function process(ContainerBuilder $container): void
    {
        $this->processHandlersOfType(
            $container,
            'ibexa.io.metadata_handlers',
            'ibexa.core.io.metadata_handler.registry',
            'ibexa.core.io.metadata_handler.flysystem.default',
            $this->metadataHandlerFactories
        );

        $this->processHandlersOfType(
            $container,
            'ibexa.io.binarydata_handlers',
            'ibexa.core.io.binarydata_handler.registry',
            'ibexa.core.io.binarydata_handler.flysystem.default',
            $this->binaryDataHandlerFactories
        );
    }

    /**
     * @param \Symfony\Component\DependencyInjection\Definition $factory The factory service that should receive the list of handlers
     * @param array<string, array<string, mixed>> $configuredHandlers Handlers configuration declared via semantic config
     * @param \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> $factories Map of alias => handler service id
     * @param string $defaultHandler default handler id
     */
    protected function processHandlers(
        ContainerBuilder $container,
        Definition $factory,
        array $configuredHandlers,
        ArrayObject $factories,
        string $defaultHandler
    ): void {
        $handlers = ['default' => new Reference($defaultHandler)];

        foreach ($configuredHandlers as $name => $config) {
            $configurationFactory = $this->getFactory($factories, $config['type'], $container);

            $parentHandlerId = $configurationFactory->getParentServiceId();
            $handlerId = sprintf('%s.%s', $parentHandlerId, $name);
            $handlerServiceDefinition = new ChildDefinition($parentHandlerId);
            $definition = $container->setDefinition($handlerId, $handlerServiceDefinition);

            $configurationFactory->configureHandler($container, $definition, $config);

            $handlers[$name] = new Reference($handlerId);
        }

        $factory->addMethodCall('setHandlersMap', [$handlers]);
    }

    /**
     * Returns from $factories the factory for handler $type.
     *
     * @param \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> $factories
     */
    protected function getFactory(ArrayObject $factories, string $type, ContainerBuilder $container): ConfigurationFactory
    {
        if (!isset($factories[$type])) {
            throw new InvalidConfigurationException("Unknown handler type $type");
        }

        return $factories[$type];
    }

    /**
     * @param \ArrayObject<string , \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> $handlerFactories
     */
    private function processHandlersOfType(
        ContainerBuilder $container,
        string $handlerContainerParameterName,
        string $handlerServiceId,
        string $defaultHandlerServiceId,
        ArrayObject $handlerFactories
    ): void {
        /** @var array<string, array<string, mixed>> $ioHandlers */
        $ioHandlers = $container->hasParameter($handlerContainerParameterName) ?
            $container->getParameter($handlerContainerParameterName) :
            [];
        $this->processHandlers(
            $container,
            $container->getDefinition($handlerServiceId),
            $ioHandlers,
            $handlerFactories,
            $defaultHandlerServiceId
        );
    }
}
