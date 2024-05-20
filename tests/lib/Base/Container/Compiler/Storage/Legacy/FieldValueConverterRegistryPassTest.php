<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Core\Base\Container\Compiler\Storage\Legacy;

use Ibexa\Core\Base\Container\Compiler\Storage\Legacy\FieldValueConverterRegistryPass;
use Ibexa\Core\Persistence\Legacy\Content\FieldValue\ConverterRegistry;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FieldValueConverterRegistryPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setDefinition(ConverterRegistry::class, new Definition());
    }

    /**
     * Register the compiler pass under test, just like you would do inside a bundle's load()
     * method:.
     *
     *   $container->addCompilerPass(new MyCompilerPass());
     */
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new FieldValueConverterRegistryPass());
    }

    public function testRegisterConverterNoLazy()
    {
        $fieldTypeIdentifier = 'fieldtype_identifier';
        $serviceId = 'some_service_id';
        $class = 'Some\Class';

        $def = new Definition();
        $def->setClass($class);
        $def->addTag(
            FieldValueConverterRegistryPass::CONVERTER_SERVICE_TAG,
            ['alias' => $fieldTypeIdentifier]
        );
        $this->setDefinition($serviceId, $def);

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            ConverterRegistry::class,
            'register',
            [$fieldTypeIdentifier, new Reference($serviceId)]
        );
    }
}
