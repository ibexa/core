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
 * Configuration parser handling all basic configuration (aka "Image").
 */
class Image extends AbstractParser
{
    /**
     * Adds semantic configuration definition.
     *
     * @param NodeBuilder $nodeBuilder Node just under ezpublish.system.<siteaccess>
     */
    public function addSemanticConfig(NodeBuilder $nodeBuilder)
    {
        $nodeBuilder
            ->arrayNode('image_variations')
                ->info('Configuration for your image variations (aka "image aliases")')
                ->example(
                    [
                        'my_image_variation' => [
                            'reference' => '~',
                            'filters' => [
                                [
                                    'name' => 'geometry/scaledownonly',
                                    'params' => [400, 350],
                                ],
                            ],
                        ],
                        'my_cropped_variation' => [
                            'reference' => 'my_image_variation',
                            'filters' => [
                                [
                                    'name' => 'geometry/scalewidthdownonly',
                                    'params' => [300],
                                ],
                                [
                                    'name' => 'geometry/crop',
                                    'params' => [300, 300, 0, 0],
                                ],
                            ],
                        ],
                    ]
                )
                ->useAttributeAsKey('variation_name')
                ->normalizeKeys(false)
                ->prototype('array')
                    ->children()
                        ->scalarNode('reference')
                            ->info('Tells the system which original variation to use as reference image. Defaults to original')
                            ->example('large')
                        ->end()
                        ->arrayNode('filters')
                            ->info('A list of filters to run, each filter must be supported by the active image converters')
                            ->useAttributeAsKey('name')
                            ->normalizeKeys(false)
                            ->prototype('array')
                                ->info('Array/Hash of parameters to pass to the filter')
                                ->useAttributeAsKey('options')
                                ->beforeNormalization()
                                    ->ifTrue(
                                        static function ($v): bool {
                                            // Check if passed array only contains a "params" key (BC with <=5.3).
                                            return is_array($v) && count($v) === 1 && isset($v['params']);
                                        }
                                    )
                                    ->then(
                                        static function ($v) {
                                            // If we have the "params" key, just use the value.
                                            return $v['params'];
                                        }
                                    )
                                ->end()
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                        ->arrayNode('post_processors')
                            ->info('Post processors as defined in LiipImagineBundle. See https://github.com/liip/LiipImagineBundle/blob/master/Resources/doc/filters.md#post-processors')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->useAttributeAsKey('name')
                                ->prototype('variable')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->scalarNode('variation_handler_identifier')
                ->info('Variation handler to be used. Defaults to built-in alias variations.')
                ->example('alias')
            ->end()
            ->scalarNode('image_host')
                ->info('Images host. All system images URLs are prefixed with given host if configured.')
                ->example('https://ibexa.co')
            ->end();
    }

    public function preMap(
        array $config,
        ContextualizerInterface $contextualizer
    ) {
        $contextualizer->mapConfigArray('image_variations', $config);
        $contextualizer->mapSetting('image_host', $config);
        $contextualizer->mapSetting('variation_handler_identifier', $config);
    }

    public function mapConfig(
        array &$scopeSettings,
        $currentScope,
        ContextualizerInterface $contextualizer
    ) {}
}
