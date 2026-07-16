<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\ViewProvidersPass;
use Ibexa\Core\MVC\Symfony\View\Provider\Registry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class ViewProvidersPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(Registry::class, new Definition());
    }

    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ViewProvidersPass());
    }

    /**
     * @dataProvider addViewProviderProvider
     */
    public function testAddViewProvider(
        $declaredPriority,
        $expectedPriority
    ) {
        $def = new Definition();

        $attributes = ['type' => 'Test\View'];
        if ($declaredPriority !== null) {
            $attributes['priority'] = $declaredPriority;
        }
        $def->addTag('ibexa.view.provider', $attributes);
        $serviceId = 'service_id';
        $this->setDefinition($serviceId, $def);

        $this->compile();
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            Registry::class,
            'setViewProviders',
            [
                ['Test\View' => [new Reference($serviceId)]],
            ]
        );
    }

    public function addViewProviderProvider()
    {
        return [
            [null, 0],
            [0, 0],
            [57, 57],
            [-23, -23],
            [-255, -255],
            [-256, -255],
            [-1000, -255],
            [255, 255],
            [256, 255],
            [1000, 255],
        ];
    }
}
