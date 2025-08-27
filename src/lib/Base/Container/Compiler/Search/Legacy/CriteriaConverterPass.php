<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Core\Base\Container\Compiler\Search\Legacy;

use Ibexa\Core\Persistence\Legacy\URL\Query\CriteriaConverter;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * This compiler pass will register Legacy Search Engine criterion handlers.
 */
class CriteriaConverterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $this->setHandlersForConverter(
            $container,
            'ibexa.search.legacy.gateway.criteria_converter.content',
            'ibexa.search.legacy.gateway.criterion_handler.content'
        );

        $this->setHandlersForConverter(
            $container,
            'ibexa.search.legacy.gateway.criteria_converter.location',
            'ibexa.search.legacy.gateway.criterion_handler.location'
        );

        $this->setHandlersForConverter(
            $container,
            'ibexa.core.trash.search.legacy.gateway.criteria_converter',
            'ibexa.search.legacy.trash.gateway.criterion.handler'
        );

        $this->setHandlersForConverter(
            $container,
            CriteriaConverter::class,
            'ibexa.storage.legacy.url.criterion.handler'
        );
    }

    private function setHandlersForConverter(
        ContainerBuilder $container,
        string $serviceId,
        string $handlersTag
    ): void {
        if (!$container->hasDefinition($serviceId)) {
            return;
        }

        $handlers = $container->findTaggedServiceIds($handlersTag);
        $container
            ->getDefinition($serviceId)
            ->setArgument('$handlers', $handlers);
    }
}

class_alias(CriteriaConverterPass::class, 'eZ\Publish\Core\Base\Container\Compiler\Search\Legacy\CriteriaConverterPass');
