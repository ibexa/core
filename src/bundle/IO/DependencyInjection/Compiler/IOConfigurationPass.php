<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\DependencyInjection\Compiler;

use ArrayObject;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 *
 * @phpstan-import-type THandlerConfigurationFactoryList from \Ibexa\Bundle\IO\DependencyInjection\Configuration
 */
final readonly class IOConfigurationPass implements CompilerPassInterface
{
    /**
     * @phpstan-param THandlerConfigurationFactoryList $metadataHandlerFactories
     * @phpstan-param THandlerConfigurationFactoryList $binarydataHandlerFactories
     */
    public function __construct(
        private ArrayObject $metadataHandlerFactories,
        private ArrayObject $binarydataHandlerFactories
    ) {
    }

    public function process(ContainerBuilder $container): void
    {
        $this->processHandlerFactories(
            $container,
            $this->metadataHandlerFactories,
            'ibexa.io.metadata_handlers',
            'ibexa.core.io.metadata_handler.registry',
            'ibexa.core.io.metadata_handler.flysystem.default'
        );

        $this->processHandlerFactories(
            $container,
            $this->binarydataHandlerFactories,
            'ibexa.io.binarydata_handlers',
            'ibexa.core.io.binarydata_handler.registry',
            'ibexa.core.io.binarydata_handler.flysystem.default'
        );
    }

    /**
     * @param \Symfony\Component\DependencyInjection\Definition $factory The factory service that should receive the list of handlers
     * @param array<string, mixed> $configuredHandlers Handlers configuration declared via semantic config
     * @param string $defaultHandler default handler id
     *
     * @phpstan-param THandlerConfigurationFactoryList $factories Map of alias => handler service id
     */
    private function processHandlers(
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
     * @phpstan-param THandlerConfigurationFactoryList $factories
     */
    private function getFactory(ArrayObject $factories, string $type): ConfigurationFactory
    {
        if (!isset($factories[$type])) {
            throw new InvalidConfigurationException("Unknown handler type $type");
        }

        return $factories[$type];
    }

    /**
     * @phpstan-param THandlerConfigurationFactoryList $ioHandlerConfigurationFactories
     */
    private function processHandlerFactories(
        ContainerBuilder $container,
        ArrayObject $ioHandlerConfigurationFactories,
        string $handlerListParameterName,
        string $registryServiceID,
        string $defaultFlysystemServiceID
    ): void {
        $ioHandlerList = $container->hasParameter($handlerListParameterName) ?
            $container->getParameter($handlerListParameterName) :
            [];
        if (!is_array($ioHandlerList)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Parameter \'%s\' must be an array, %s given',
                    $handlerListParameterName,
                    get_debug_type($ioHandlerList)
                )
            );
        }

        $this->processHandlers(
            $container,
            $container->getDefinition($registryServiceID),
            $ioHandlerList,
            $ioHandlerConfigurationFactories,
            $defaultFlysystemServiceID
        );
    }
}
