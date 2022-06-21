<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Configuration\Parser\Repository;

use Ibexa\Bundle\Core\DependencyInjection\Configuration\RepositoryConfigParserInterface;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

final class FieldGroups implements RepositoryConfigParserInterface
{
    public function addSemanticConfig(NodeBuilder $nodeBuilder): void
    {
        $nodeBuilder->append($this->getNode());
    }

    public function getNode(): NodeDefinition
    {
        $node = new ArrayNodeDefinition('field_groups');
        $node
            ->info('Definitions of fields groups.')
            ->children()
                ->arrayNode('list')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('default')
                    ->defaultValue('%ibexa.site_access.config.default.content.field_groups.default%')
                ->end()
            ->end();

        return $node;
    }
}
