<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\EventListener\ConfigScopeListener;
use Ibexa\Core\MVC\Symfony\View\CustomLocationControllerChecker;
use Ibexa\Core\MVC\Symfony\View\Provider\Registry;
use LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Registers services tagged as "ibexa.view.provider" into the view_provider registry.
 */
class ViewProvidersPass implements CompilerPassInterface
{
    private const VIEW_PROVIDER_TAG = 'ibexa.view.provider';

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $rawViewProviders = [];
        foreach ($container->findTaggedServiceIds(self::VIEW_PROVIDER_TAG) as $serviceId => $tags) {
            foreach ($tags as $attributes) {
                // Priority range is between -255 (the lowest) and 255 (the highest)
                $priority = isset($attributes['priority']) ? max(min((int)$attributes['priority'], 255), -255) : 0;

                if (!isset($attributes['type'])) {
                    throw new LogicException(
                        sprintf(
                            'Service "%s" tagged with "%s" service tag needs a "type" attribute',
                            $serviceId,
                            self::VIEW_PROVIDER_TAG
                        )
                    );
                }
                $type = $attributes['type'];

                $rawViewProviders[$type][$priority][] = new Reference($serviceId);
            }
        }

        $viewProviders = [];
        foreach ($rawViewProviders as $type => $viewProvidersPerPriority) {
            krsort($viewProvidersPerPriority);
            foreach ($viewProvidersPerPriority as $priorityViewProviders) {
                if (!isset($viewProviders[$type])) {
                    $viewProviders[$type] = [];
                }
                $viewProviders[$type] = array_merge($viewProviders[$type], $priorityViewProviders);
            }
        }

        if ($container->hasDefinition(Registry::class)) {
            $container->getDefinition(Registry::class)->addMethodCall(
                'setViewProviders',
                [$viewProviders]
            );
        }

        $flattenedViewProviders = [];
        foreach ($viewProviders as $type => $typeViewProviders) {
            foreach ($typeViewProviders as $typeViewProvider) {
                $flattenedViewProviders[] = $typeViewProvider;
            }
        }

        if ($container->hasDefinition(ConfigScopeListener::class)) {
            $container->getDefinition(ConfigScopeListener::class)->addMethodCall(
                'setViewProviders',
                [$flattenedViewProviders]
            );
        }

        // 5.4.5 BC service after location view deprecation
        if ($container->hasDefinition(CustomLocationControllerChecker::class)) {
            $container->getDefinition(CustomLocationControllerChecker::class)->addMethodCall(
                'addViewProviders',
                [$viewProviders['Ibexa\Core\MVC\Symfony\View\ContentView']]
            );
        }
    }
}

class_alias(ViewProvidersPass::class, 'eZ\Bundle\EzPublishCoreBundle\DependencyInjection\Compiler\ViewProvidersPass');
