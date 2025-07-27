<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\FragmentPass;
use Ibexa\Bundle\Core\Fragment\DecoratedFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\FragmentListenerFactory;
use Ibexa\Bundle\Core\Fragment\InlineFragmentRenderer;
use Ibexa\Bundle\Core\Fragment\SiteAccessSerializerInterface;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FragmentPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FragmentPass());
    }

    public function testProcess()
    {
        $inlineRendererDef = new Definition(InlineFragmentRenderer::class);
        $inlineRendererDef->addTag('kernel.fragment_renderer');
        $esiRendererDef = new Definition();
        $esiRendererDef->addTag('kernel.fragment_renderer');
        $hincludeRendererDef = new Definition();
        $hincludeRendererDef->addTag('kernel.fragment_renderer');

        $decoratedFragmentRendererDef = new Definition();
        $decoratedFragmentRendererDef->setAbstract(true);

        $this->setDefinition('fragment.listener', new Definition());
        $this->setDefinition('fragment.renderer.inline', $inlineRendererDef);
        $this->setDefinition('fragment.renderer.esi', $esiRendererDef);
        $this->setDefinition('fragment.renderer.hinclude', $hincludeRendererDef);
        $this->setDefinition(DecoratedFragmentRenderer::class, $decoratedFragmentRendererDef);
        $this->setDefinition(FragmentListenerFactory::class, new Definition());

        $this->compile();

        self::assertTrue($this->container->hasDefinition('fragment.listener'));
        $fragmentListenerDef = $this->container->getDefinition('fragment.listener');

        $factoryArray = $fragmentListenerDef->getFactory();
        self::assertInstanceOf(Reference::class, $factoryArray[0]);
        self::assertEquals('buildFragmentListener', $factoryArray[1]);
        self::assertEquals(FragmentListenerFactory::class, $factoryArray[0]);

        self::assertTrue($this->container->hasDefinition('fragment.renderer.inline.inner'));
        self::assertSame($inlineRendererDef, $this->container->getDefinition('fragment.renderer.inline.inner'));
        self::assertFalse($inlineRendererDef->isPublic());
        self::assertTrue($this->container->hasDefinition('fragment.renderer.esi.inner'));
        self::assertSame($esiRendererDef, $this->container->getDefinition('fragment.renderer.esi.inner'));
        self::assertFalse($esiRendererDef->isPublic());
        self::assertTrue($this->container->hasDefinition('fragment.renderer.hinclude.inner'));
        self::assertSame($hincludeRendererDef, $this->container->getDefinition('fragment.renderer.hinclude.inner'));
        self::assertFalse($hincludeRendererDef->isPublic());

        $this->assertContainerBuilderHasServiceDefinitionWithParent('fragment.renderer.inline', DecoratedFragmentRenderer::class);
        $decoratedInlineDef = $this->container->getDefinition('fragment.renderer.inline');
        self::assertSame(['kernel.fragment_renderer' => [[]]], $decoratedInlineDef->getTags());
        self::assertEquals(
            [new Reference('fragment.renderer.inline.inner'), new Reference(SiteAccessSerializerInterface::class)],
            $decoratedInlineDef->getArguments()
        );
        self::assertSame(InlineFragmentRenderer::class, $decoratedInlineDef->getClass());

        $this->assertContainerBuilderHasServiceDefinitionWithParent('fragment.renderer.esi', DecoratedFragmentRenderer::class);
        $decoratedEsiDef = $this->container->getDefinition('fragment.renderer.esi');
        self::assertSame(['kernel.fragment_renderer' => [[]]], $decoratedEsiDef->getTags());
        self::assertEquals(
            [new Reference('fragment.renderer.esi.inner'), new Reference(SiteAccessSerializerInterface::class)],
            $decoratedEsiDef->getArguments()
        );

        $this->assertContainerBuilderHasServiceDefinitionWithParent('fragment.renderer.hinclude', DecoratedFragmentRenderer::class);
        $decoratedHincludeDef = $this->container->getDefinition('fragment.renderer.hinclude');
        self::assertSame(['kernel.fragment_renderer' => [[]]], $decoratedHincludeDef->getTags());
        self::assertEquals(
            [new Reference('fragment.renderer.hinclude.inner'), new Reference(SiteAccessSerializerInterface::class)],
            $decoratedHincludeDef->getArguments()
        );
    }
}
