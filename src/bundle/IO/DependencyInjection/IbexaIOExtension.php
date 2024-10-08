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
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class IbexaIOExtension extends Extension
{
    public const EXTENSION_NAME = 'ibexa_io';

    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject */
    private $metadataHandlerFactories;

    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject */
    private $binarydataHandlerFactories;

    public function __construct()
    {
        $this->metadataHandlerFactories = new ArrayObject();
        $this->binarydataHandlerFactories = new ArrayObject();
    }

    /**
     * Registers a metadata handler configuration $factory for handler with $alias.
     *
     * @param string $alias
     * @param \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory $factory
     */
    public function addMetadataHandlerFactory($alias, ConfigurationFactory $factory)
    {
        $this->metadataHandlerFactories[$alias] = $factory;
    }

    /**
     * Registers a binarydata handler configuration $factory for handler with $alias.
     *
     * @param string $alias
     * @param \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory $factory
     */
    public function addBinarydataHandlerFactory($alias, ConfigurationFactory $factory)
    {
        $this->binarydataHandlerFactories[$alias] = $factory;
    }

    /**
     * @return \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject
     */
    public function getMetadataHandlerFactories()
    {
        return $this->metadataHandlerFactories;
    }

    /**
     * @return \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory[]|\ArrayObject
     */
    public function getBinarydataHandlerFactories()
    {
        return $this->binarydataHandlerFactories;
    }

    public function getAlias(): string
    {
        return self::EXTENSION_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

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
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     * @param string $key Configuration key, either binarydata or metadata
     */
    private function processHandlers(ContainerBuilder $container, $config, $key)
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

    public function getConfiguration(array $config, ContainerBuilder $container): ?ConfigurationInterface
    {
        $configuration = new Configuration();
        $configuration->setMetadataHandlerFactories($this->getMetadataHandlerFactories());
        $configuration->setBinarydataHandlerFactories($this->getBinarydataHandlerFactories());

        return $configuration;
    }
}
