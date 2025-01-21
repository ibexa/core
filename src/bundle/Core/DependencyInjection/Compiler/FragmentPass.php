<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\Fragment\DecoratedFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\FragmentListenerFactory;
use Ibexa\Bundle\Core\Fragment\InlineFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\SiteAccessSerializerInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\EventListener\FragmentListener;

/**
 * Tweaks Symfony fragment framework.
 */
class FragmentPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (
            !(
                $container->hasDefinition('fragment.listener')
                && $container->hasDefinition(DecoratedFragmentRenderer::class)
            )
        ) {
            return;
        }

        $fragmentListenerDef = $container->findDefinition('fragment.listener');
        $fragmentListenerDef
            ->setFactory([new Reference(FragmentListenerFactory::class), 'buildFragmentListener'])
            ->addArgument(FragmentListener::class);

        // Looping over all fragment renderers to decorate them
        // This is to make sure they are siteaccess aware (siteaccess is serialized in rendered path).
        foreach ($container->findTaggedServiceIds('kernel.fragment_renderer') as $id => $attributes) {
            $renamedId = "$id.inner";
            $definition = $container->getDefinition($id);
            $public = $definition->isPublic();
            $tags = $definition->getTags();
            $definition->setPublic(false);
            $container->setDefinition($renamedId, $definition);

            $decoratedDef = new ChildDefinition(DecoratedFragmentRenderer::class);
            $decoratedDef->setArguments([new Reference($renamedId), new Reference(SiteAccessSerializerInterface::class)]);
            $decoratedDef->setPublic($public);
            $decoratedDef->setTags($tags);
            // Special treatment for inline fragment renderer, to fit ESI renderer constructor type hinting (forced to InlineFragmentRenderer)
            if ($id === 'fragment.renderer.inline') {
                $decoratedDef->setClass(InlineFragmentRenderer::class);
            }

            $container->setDefinition($id, $decoratedDef);
        }
    }
}
