<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\MetadataHandler\LegacyDFSCluster as LegacyDFSClusterConfigurationFactory;
use Ibexa\Core\IO\IOMetadataHandler\LegacyDFSCluster;
use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactoryTestCase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class LegacyDFSClusterTest extends ConfigurationFactoryTestCase
{
    public function provideTestedFactory(): ConfigurationFactory
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

    /**
     * Lets you test the handler definition after it was configured.
     *
     * Use the assertContainer* methods from matthiasnoback/SymfonyDependencyInjectionTest.
     *
     * @param string $handlerServiceId id of the service that was registered by the compiler pass
     */
    public function validateConfiguredHandler(string $handlerServiceId): void
    {
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            $handlerServiceId,
            0,
            new Reference('doctrine.dbal.test_connection')
        );
    }
}
