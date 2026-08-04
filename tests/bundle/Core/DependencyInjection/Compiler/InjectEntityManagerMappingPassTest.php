<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\DependencyInjection\Compiler\InjectEntityManagerMappingsPass;
use Ibexa\Bundle\Core\Doctrine\ManagedTablesSchemaAssetFilter;
use Ibexa\Tests\Bundle\Core\DependencyInjection\Stub\AttributeEntityBundle\AttributeEntityBundle;
use Ibexa\Tests\Bundle\Core\DependencyInjection\Stub\XmlEntityBundle\XmlEntityBundle;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class InjectEntityManagerMappingPassTest extends AbstractCompilerPassTestCase
{
    private const BUNDLES = [
        'AttributeEntityBundle' => AttributeEntityBundle::class,
        'XmlEntityBundle' => XmlEntityBundle::class,
    ];
    private const ENTITY_MANAGERS = ['ibexa_connection' => 'doctrine.orm.ibexa_connection_entity_manager'];
    private const ENTITY_MAPPINGS = [
        'AttributeEntityBundle' => [
            'is_bundle' => true,
            'type' => 'attribute',
            'dir' => 'Entity',
            'prefix' => '\Ibexa\Tests\Bundle\Core\DependencyInjection\Stub\AttributeEntityBundle\Entity',
        ],
        'XmlEntityBundle' => [
            'is_bundle' => true,
            'type' => 'xml',
            'dir' => 'config',
            'prefix' => '\Ibexa\Tests\Bundle\Core\DependencyInjection\Stub\XmlEntityBundle\XmlEntityBundle\Entity',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->setDefinition('doctrine.orm.ibexa_connection_metadata_driver', new Definition());
        $this->setDefinition('doctrine.orm.ibexa_connection_configuration', new Definition());
        $this->setDefinition('doctrine.dbal.connection_connection.configuration', new Definition());
        $this->setDefinition(ManagedTablesSchemaAssetFilter::class, new Definition(ManagedTablesSchemaAssetFilter::class));
        $this->setParameter('doctrine.orm.metadata.attribute.class', 'Vendor/Doctrine/Metadata/Driver/AttributeDriver');
        $this->setParameter('doctrine.orm.metadata.xml.class', 'Vendor/Doctrine/Metadata/Driver/XmlDriver');
        $this->setParameter('kernel.bundles', self::BUNDLES);

        $this->setParameter('doctrine.entity_managers', self::ENTITY_MANAGERS);
        $this->setParameter('ibexa.orm.entity_mappings', self::ENTITY_MAPPINGS);
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new InjectEntityManagerMappingsPass());
    }

    public function testInjectEntityMapping(): void
    {
        $this->compile();

        $expectedDriverPaths = [
            'AttributeEntityBundle' => [
                realpath(__DIR__ . '/../Stub/AttributeEntityBundle/' . self::ENTITY_MAPPINGS['AttributeEntityBundle']['dir']),
            ],
            'XmlEntityBundle' => [
                realpath(__DIR__ . '/../Stub/XmlEntityBundle/' . self::ENTITY_MAPPINGS['XmlEntityBundle']['dir']) => sprintf('\\%s\Entity', XmlEntityBundle::class),
            ],
        ];

        foreach (self::ENTITY_MANAGERS as $name => $serviceId) {
            $this->assertContainerBuilderHasService("doctrine.orm.{$name}_metadata_driver");

            foreach (self::ENTITY_MAPPINGS as $mappingName => $config) {
                $metadataDriver = "doctrine.orm.{$name}_{$config['type']}_metadata_driver";
                $this->assertContainerBuilderHasServiceDefinitionWithArgument(
                    $metadataDriver,
                    0,
                    $expectedDriverPaths[$mappingName]
                );
                $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
                    "doctrine.orm.{$name}_metadata_driver",
                    'addDriver',
                    [new Reference($metadataDriver), $config['prefix']]
                );
            }
        }
    }

    public function testProtectsLegacySchemaFromOrmSchemaSync(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            ManagedTablesSchemaAssetFilter::class,
            'doctrine.dbal.schema_filter',
            ['connection' => 'connection']
        );
    }

    /**
     * The filter must not be installed with Configuration::setSchemaAssetsFilter(), as that
     * would discard any filter set by DoctrineBundle's DbalSchemaFilterPass, and with it any
     * "doctrine.dbal.<connection>.schema_filter" a project configured.
     */
    public function testDoesNotOverwriteTheConnectionSchemaAssetsFilter(): void
    {
        $this->compile();

        $methodCalls = $this->container
            ->getDefinition('doctrine.dbal.connection_connection.configuration')
            ->getMethodCalls();

        self::assertSame([], $methodCalls);
    }
}
