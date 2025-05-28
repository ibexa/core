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
 * @todo Refactor into two passes, since they're very very close.
 */
class IOConfigurationPass implements CompilerPassInterface
{
    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject */
    private $metadataHandlerFactories;

    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject */
    private $binarydataHandlerFactories;

    public function __construct(
        ArrayObject $metadataHandlerFactories = null,
        ArrayObject $binarydataHandlerFactories = null
    ) {
        $this->metadataHandlerFactories = $metadataHandlerFactories;
        $this->binarydataHandlerFactories = $binarydataHandlerFactories;
    }

    public function process(ContainerBuilder $container): void
    {
        $ioMetadataHandlers = $container->hasParameter('ibexa.io.metadata_handlers') ?
            $container->getParameter('ibexa.io.metadata_handlers') :
            [];
        $this->processHandlers(
            $container,
            $container->getDefinition('ibexa.core.io.metadata_handler.registry'),
            $ioMetadataHandlers,
            $this->metadataHandlerFactories,
            'ibexa.core.io.metadata_handler.flysystem.default'
        );

        $ioBinarydataHandlers = $container->hasParameter('ibexa.io.binarydata_handlers') ?
            $container->getParameter('ibexa.io.binarydata_handlers') :
            [];
        $this->processHandlers(
            $container,
            $container->getDefinition('ibexa.core.io.binarydata_handler.registry'),
            $ioBinarydataHandlers,
            $this->binarydataHandlerFactories,
            'ibexa.core.io.binarydata_handler.flysystem.default'
        );

        // Unset parameters that are no longer required ?
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param \Symfony\Component\DependencyInjection\Definition $factory The factory service that should receive the list of handlers
     * @param array $configuredHandlers Handlers configuration declared via semantic config
     * @param \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject $factories Map of alias => handler service id
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
            $configurationFactory = $this->getFactory($factories, $config['type']);

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
     * @param \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject $factories
     * @param string $type
     */
    protected function getFactory(ArrayObject $factories, string $type): ConfigurationFactory
    {
        if (!isset($factories[$type])) {
            throw new InvalidConfigurationException("Unknown handler type $type");
        }

        return $factories[$type];
    }
}
