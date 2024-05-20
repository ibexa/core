<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\URLHandlerPass;
use Ibexa\Bundle\Core\URLChecker\URLHandlerRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class URLHandlerPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(URLHandlerRegistry::class, new Definition());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new URLHandlerPass());
    }

    public function testRegisterURLHandler()
    {
        $serviceId = 'service_id';
        $scheme = 'http';
        $definition = new Definition();
        $definition->addTag('ibexa.url_checker.handler', ['scheme' => $scheme]);
        $this->setDefinition($serviceId, $definition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            URLHandlerRegistry::class,
            'addHandler',
            [$scheme, new Reference($serviceId)]
        );
    }

    public function testRegisterURLHandlerNoScheme()
    {
        $this->expectException(\LogicException::class);

        $serviceId = 'service_id';
        $scheme = 'http';
        $definition = new Definition();
        $definition->addTag('ibexa.url_checker.handler');
        $this->setDefinition($serviceId, $definition);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            URLHandlerRegistry::class,
            'addHandler',
            [$scheme, new Reference($serviceId)]
        );
    }
}
