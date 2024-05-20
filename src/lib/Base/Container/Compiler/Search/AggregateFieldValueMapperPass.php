<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Core\Base\Container\Compiler\Search;

use Ibexa\Core\Search\Common\FieldValueMapper\Aggregate;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass will register Search Engine field value mappers.
 */
class AggregateFieldValueMapperPass implements CompilerPassInterface
{
    public const TAG = 'ibexa.search.common.field_value.mapper';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(Aggregate::class)) {
            return;
        }

        $aggregateFieldValueMapperDefinition = $container->getDefinition(Aggregate::class);
        $taggedServiceIds = $container->findTaggedServiceIds(self::TAG);
        foreach ($taggedServiceIds as $id => $tags) {
            foreach ($tags as $tagAttributes) {
                $aggregateFieldValueMapperDefinition->addMethodCall(
                    'addMapper',
                    [new Reference($id), $tagAttributes['maps'] ?? null]
                );
            }
        }
    }
}
