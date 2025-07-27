<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Debug\DependencyInjection\Compiler;

use Ibexa\Bundle\Debug\Collector\IbexaCoreCollector;
use Ibexa\Bundle\Debug\DependencyInjection\Compiler\DataCollectorPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DataCollectorPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(IbexaCoreCollector::class, new Definition());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DataCollectorPass());
    }

    public function testAddCollector()
    {
        $defA = new Definition();
        $defA->addTag('ibexa.debug.data_collector', [
            'panelTemplate' => 'panel_a.html.twig',
            'toolbarTemplate' => 'toolbar_a.html.twig',
            'priority' => 5,
        ]);
        $this->setDefinition('collector_a', $defA);

        $defB = new Definition();
        $defB->addTag('ibexa.debug.data_collector', [
            'panelTemplate' => 'panel_b.html.twig',
            'toolbarTemplate' => 'toolbar_b.html.twig',
            'priority' => 10,
        ]);
        $this->setDefinition('collector_b', $defB);

        $this->compile();

        self::assertContainerBuilderHasServiceDefinitionWithMethodCall(
            IbexaCoreCollector::class,
            'addCollector',
            [new Reference('collector_b'), 'panel_b.html.twig', 'toolbar_b.html.twig']
        );

        self::assertContainerBuilderHasServiceDefinitionWithMethodCall(
            IbexaCoreCollector::class,
            'addCollector',
            [new Reference('collector_a'), 'panel_a.html.twig', 'toolbar_a.html.twig']
        );

        $calls = $this->container->getDefinition(IbexaCoreCollector::class)->getMethodCalls();

        self::assertCount(2, $calls);
        self::assertSame('addCollector', $calls[0][0]);
        self::assertEquals(new Reference('collector_b'), $calls[0][1][0]);

        self::assertSame('addCollector', $calls[1][0]);
        self::assertEquals(new Reference('collector_a'), $calls[1][1][0]);
    }
}
