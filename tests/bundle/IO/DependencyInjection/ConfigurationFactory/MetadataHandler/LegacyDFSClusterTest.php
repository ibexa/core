<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler\LegacyDFSCluster as LegacyDFSClusterConfigurationFactory;
use Ibexa\Core\IO\IOMetadataHandler\LegacyDFSCluster;
use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactoryTestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class LegacyDFSClusterTest extends ConfigurationFactoryTestCase
{
    /**
     * Returns an instance of the tested factory.
     */
    public function provideTestedFactory(): LegacyDFSClusterConfigurationFactory
    {
        return new LegacyDFSClusterConfigurationFactory();
    }

    public function provideExpectedParentServiceId(): string
    {
        return LegacyDFSCluster::class;
    }

    public function provideParentServiceDefinition(): Definition
    {
        return new Definition(null, [null]);
    }

    public function provideHandlerConfiguration(): array
    {
        return ['connection' => 'doctrine.dbal.test_connection'];
    }

    public function testAddConfiguration(): void
    {
        $node = new ArrayNodeDefinition('handler');
        $this->factory->addConfiguration($node);
        self::assertArrayHasKey('connection', $node->getChildNodeDefinitions());
    }

    public function validateConfiguredHandler(string $handlerServiceId): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            $handlerServiceId,
            0,
            new Reference('doctrine.dbal.test_connection')
        );
    }
}
