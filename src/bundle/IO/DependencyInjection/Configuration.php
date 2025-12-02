<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\IO\DependencyInjection;

use ArrayObject;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\Configuration as SiteAccessConfiguration;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * @internal
 *
 * @phpstan-type THandlerConfigurationFactoryList \ArrayObject<string, \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory>
 *
 * @phpstan-import-type TRootNode from SiteAccessConfiguration
 */
class Configuration implements ConfigurationInterface
{
    /** @phpstan-var THandlerConfigurationFactoryList */
    private ArrayObject $metadataHandlerFactories;

    /** @phpstan-var THandlerConfigurationFactoryList */
    private ArrayObject $binarydataHandlerFactories;

    /**
     * @phpstan-param THandlerConfigurationFactoryList $factories
     */
    public function setMetadataHandlerFactories(ArrayObject $factories): void
    {
        $this->metadataHandlerFactories = $factories;
    }

    /**
     * @phpstan-param THandlerConfigurationFactoryList $factories
     */
    public function setBinarydataHandlerFactories(ArrayObject $factories): void
    {
        $this->binarydataHandlerFactories = $factories;
    }

    /**
     * @phpstan-return TreeBuilder<'array'>
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (!isset($this->binarydataHandlerFactories, $this->metadataHandlerFactories)) {
            throw new LogicException('IO configuration handler factories need to be initialized');
        }

        $treeBuilder = new TreeBuilder(IbexaIOExtension::EXTENSION_NAME);

        $rootNode = $treeBuilder->getRootNode();

        $this->addHandlersSection(
            $rootNode,
            'metadata_handlers',
            'Handlers for files metadata, that read & write files metadata (size, modification time...)',
            $this->metadataHandlerFactories
        );
        $this->addHandlersSection(
            $rootNode,
            'binarydata_handlers',
            'Handlers for files binary data. Reads & write files binary content',
            $this->binarydataHandlerFactories
        );

        $rootNode->children()->end();

        return $treeBuilder;
    }

    /**
     * @phpstan-param TRootNode $node
     * @phpstan-param THandlerConfigurationFactoryList $factories
     */
    private function addHandlersSection(
        ArrayNodeDefinition $node,
        string $name,
        string $info,
        ArrayObject $factories
    ): void {
        $handlersNodeBuilder = $node
            ->children()
                ->arrayNode($name)
                    ->info($info)
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                    ->performNoDeepMerging()
                    ->children();

        foreach ($factories as $factoryName => $factory) {
            $factoryNode = $handlersNodeBuilder->arrayNode($factoryName)->canBeUnset();
            $factory->addConfiguration($factoryNode);
        }
    }
}
