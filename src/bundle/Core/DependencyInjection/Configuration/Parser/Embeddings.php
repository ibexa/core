<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\AbstractParser;
use Ibexa\Bundle\Core\DependencyInjection\Configuration\SiteAccessAware\ContextualizerInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;

/**
 * Configuration parser for embedding models.
 *
 * Example configuration:
 * ```yaml
 * ibexa:
 *   system:
 *      default: # configuration per siteaccess or siteaccess group
 *          embedding_models:
 *              name: "text-embedding-3-small"
 *              dimensions: 1536
 *              field_suffix: "3small"
 *              embedding_provider: "ibexa_openai"
 *          default_embedding_model: text-embedding-ada-002
 * ```
 */
class Embeddings extends AbstractParser
{
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder
            ->arrayNode('embedding_models')
                ->normalizeKeys(false)
                ->info('Defines available embedding models')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')->isRequired()->end()
                        ->integerNode('dimensions')->isRequired()->end()
                        ->scalarNode('field_suffix')->isRequired()->end()
                        ->scalarNode('embedding_provider')->isRequired()->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('default_embedding_model')
                ->info('Default embedding model identifier')
                ->defaultValue('text-embedding-ada-002')
            ->end();
    }

    /**
     * @param array<mixed> $config
     */
    public function preMap(array $config, ContextualizerInterface $contextualizer): void
    {
        $contextualizer->mapConfigArray('embedding_models', $config);
        $contextualizer->mapSetting('default_embedding_model', $config);
    }

    /**
     * @param array<mixed> $scopeSettings
     */
    public function mapConfig(array &$scopeSettings, $currentScope, ContextualizerInterface $contextualizer): void
    {
        // Nothing to do here.
    }
}
