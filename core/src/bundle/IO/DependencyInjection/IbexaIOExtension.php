<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\IO\DependencyInjection;

use ArrayObject;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class IbexaIOExtension extends Extension
{
    public const string EXTENSION_NAME = 'ibexa_io';

    /** @var \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> */
    private ArrayObject $metadataHandlerFactories;

    /** @var \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory> */
    private ArrayObject $binarydataHandlerFactories;

    public function __construct()
    {
        $this->metadataHandlerFactories = new ArrayObject();
        $this->binarydataHandlerFactories = new ArrayObject();
    }

    /**
     * Registers a metadata handler configuration $factory for handler with $alias.
     */
    public function addMetadataHandlerFactory(string $alias, ConfigurationFactory $factory): void
    {
        $this->metadataHandlerFactories[$alias] = $factory;
    }

    /**
     * Registers a binary data handler configuration $factory for handler with $alias.
     */
    public function addBinarydataHandlerFactory(string $alias, ConfigurationFactory $factory): void
    {
        $this->binarydataHandlerFactories[$alias] = $factory;
    }

    /**
     * @return \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory>
     */
    public function getMetadataHandlerFactories(): ArrayObject
    {
        return $this->metadataHandlerFactories;
    }

    /**
     * @return \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory>
     */
    public function getBinarydataHandlerFactories(): ArrayObject
    {
        return $this->binarydataHandlerFactories;
    }

    public function getAlias(): string
    {
        return self::EXTENSION_NAME;
    }

    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        /** @var \Ibexa\Bundle\IO\DependencyInjection\Configuration $configuration */
        $configuration = $this->getConfiguration($configs, $container);

        $config = $this->processConfiguration($configuration, $configs);

        $loader->load('io.yml');
        $loader->load('default_settings.yml');

        $this->processHandlers($container, $config, 'metadata_handlers');
        $this->processHandlers($container, $config, 'binarydata_handlers');
    }

    /**
     * Processes the config key $key, and registers the result in ez_io.$key.
     *
     * @param array<mixed> $config
     * @param string $key Configuration key, either binary data or metadata
     */
    private function processHandlers(ContainerBuilder $container, array $config, string $key): void
    {
        $handlers = [];
        if (isset($config[$key])) {
            foreach ($config[$key] as $name => $value) {
                if (isset($handlers[$name])) {
                    throw new InvalidConfigurationException("A $key called $name already exists");
                }
                $handlerConfig = current($value);
                $handlerConfig['type'] = key($value);
                $handlerConfig['name'] = $name;
                $handlers[$name] = $handlerConfig;
            }
        }
        $container->setParameter("ibexa.io.{$key}", $handlers);
    }

    /**
     * @param array<mixed> $config
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        $configuration = new Configuration();
        $configuration->setMetadataHandlerFactories($this->getMetadataHandlerFactories());
        $configuration->setBinarydataHandlerFactories($this->getBinarydataHandlerFactories());

        return $configuration;
    }
}
