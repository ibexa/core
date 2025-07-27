<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Base\Container\Compiler\Search\Legacy;

use Ibexa\Core\Base\Container\Compiler\Search\Legacy\CriterionFieldValueHandlerRegistryPass;
use Ibexa\Core\Search\Legacy\Content\Common\Gateway\CriterionHandler\FieldValue\HandlerRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class CriterionFieldValueHandlerRegistryPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(
            HandlerRegistry::class,
            new Definition()
        );
    }

    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new CriterionFieldValueHandlerRegistryPass());
    }

    public function testRegisterValueHandler()
    {
        $fieldTypeIdentifier = 'field_type_identifier';
        $serviceId = 'service_id';
        $def = new Definition();
        $def->addTag(
            'ibexa.search.legacy.gateway.criterion_handler.field_value',
            ['alias' => $fieldTypeIdentifier]
        );
        $this->setDefinition($serviceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            HandlerRegistry::class,
            'register',
            [$fieldTypeIdentifier, new Reference($serviceId)]
        );
    }

    public function testRegisterValueHandlerNoAlias()
    {
        $this->expectException(\LogicException::class);

        $fieldTypeIdentifier = 'field_type_identifier';
        $serviceId = 'service_id';
        $def = new Definition();
        $def->addTag('ibexa.search.legacy.gateway.criterion_handler.field_value');
        $this->setDefinition($serviceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            HandlerRegistry::class,
            'register',
            [$fieldTypeIdentifier, new Reference($serviceId)]
        );
    }
}
