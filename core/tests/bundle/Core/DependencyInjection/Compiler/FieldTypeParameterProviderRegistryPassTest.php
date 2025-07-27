<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\FieldTypeParameterProviderRegistryPass;
use Ibexa\Core\MVC\Symfony\FieldType\View\ParameterProviderRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FieldTypeParameterProviderRegistryPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(ParameterProviderRegistry::class, new Definition());
    }

    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FieldTypeParameterProviderRegistryPass());
    }

    /**
     * @dataProvider tagsProvider
     */
    public function testRegisterFieldType(string $tag)
    {
        $fieldTypeIdentifier = 'field_type_identifier';
        $serviceId = 'service_id';
        $def = new Definition();
        $def->addTag($tag, ['alias' => $fieldTypeIdentifier]);
        $this->setDefinition($serviceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ParameterProviderRegistry::class,
            'setParameterProvider',
            [new Reference($serviceId), $fieldTypeIdentifier]
        );
    }

    /**
     * @dataProvider tagsProvider
     *
     * @param string $tag
     */
    public function testRegisterFieldTypeNoAlias(string $tag)
    {
        $this->expectException(\LogicException::class);

        $fieldTypeIdentifier = 'field_type_identifier';
        $serviceId = 'service_id';
        $def = new Definition();
        $def->addTag($tag);
        $this->setDefinition($serviceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ParameterProviderRegistry::class,
            'setParameterProvider',
            [new Reference($serviceId), $fieldTypeIdentifier]
        );
    }

    public function tagsProvider(): array
    {
        return [
            [FieldTypeParameterProviderRegistryPass::FIELD_TYPE_PARAMETER_PROVIDER_SERVICE_TAG],
        ];
    }
}
