<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\ApiLoader\SearchEngineIndexerFactory;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterSearchEngineIndexerPass implements CompilerPassInterface
{
    public const string SEARCH_ENGINE_INDEXER_SERVICE_TAG = 'ibexa.search.engine.indexer';

    protected string $factoryId = SearchEngineIndexerFactory::class;

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition($this->factoryId)) {
            return;
        }

        $searchEngineIndexerFactoryDefinition = $container->getDefinition($this->factoryId);

        $serviceTags = $container->findTaggedServiceIds(self::SEARCH_ENGINE_INDEXER_SERVICE_TAG);
        foreach ($serviceTags as $serviceId => $attributes) {
            foreach ($attributes as $attribute) {
                if (!isset($attribute['alias'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" needs an "alias" attribute to identify the search engine',
                            $serviceId,
                            self::SEARCH_ENGINE_INDEXER_SERVICE_TAG
                        )
                    );
                }

                $searchEngineIndexerFactoryDefinition->addMethodCall(
                    'registerSearchEngineIndexer',
                    [
                        new Reference($serviceId),
                        $attribute['alias'],
                    ]
                );
            }
        }
    }
}
