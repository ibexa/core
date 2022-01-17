<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Processes services tagged as ibexa.query_type, and registers them with ezpublish.query_type.registry.
 */
final class QueryTypePass implements CompilerPassInterface
{
    public const QUERY_TYPE_SERVICE_TAG = 'ibexa.query_type';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ezpublish.query_type.registry')) {
            return;
        }

        $queryTypes = [];

        $serviceTags = $container->findTaggedServiceIds(self::QUERY_TYPE_SERVICE_TAG);
        foreach ($serviceTags as $taggedServiceId => $tags) {
            $queryTypeDefinition = $container->getDefinition($taggedServiceId);
            $queryTypeClass = $container->getParameterBag()->resolveValue($queryTypeDefinition->getClass());

            foreach ($tags as $attributes) {
                $name = $attributes['alias'] ?? $queryTypeClass::getName();
                $queryTypes[$name] = new Reference($taggedServiceId);
            }
        }

        $aggregatorDefinition = $container->getDefinition('ezpublish.query_type.registry');
        $aggregatorDefinition->addMethodCall('addQueryTypes', [$queryTypes]);
    }
}

class_alias(QueryTypePass::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\QueryTypePass');
