<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\Compiler;

use ArrayObject;
use Ibexa\Bundle\IO\DependencyInjection\Compiler\IOConfigurationPass;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @covers \Ibexa\Bundle\IO\DependencyInjection\Compiler\IOConfigurationPass
 */
final class IOConfigurationPassTest extends AbstractCompilerPassTestCase
{
    protected ConfigurationFactory & MockObject $metadataConfigurationFactoryMock;

    protected ConfigurationFactory & MockObject $binarydataConfigurationFactoryMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container->setParameter('ibexa.io.metadata_handlers', []);
        $this->container->setParameter('ibexa.io.binarydata_handlers', []);

        $this->container->setDefinition('ibexa.core.io.binarydata_handler.registry', new Definition());
        $this->container->setDefinition('ibexa.core.io.metadata_handler.registry', new Definition());
        $this->container->setDefinition('ibexa.core.io.binarydata_handler.flysystem.default', new Definition());
        $this->container->setDefinition('ibexa.core.io.metadata_handler.flysystem.default', new Definition());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $this->metadataConfigurationFactoryMock = $this->createMock(ConfigurationFactory::class);
        $this->binarydataConfigurationFactoryMock = $this->createMock(ConfigurationFactory::class);

        $container->addCompilerPass(
            new IOConfigurationPass(
                // workaround for ArrayObject TValue not being template-covariant (can't pass a mock)
                /** @phpstan-ignore argument.type */
                new ArrayObject(
                    ['test_handler' => $this->metadataConfigurationFactoryMock]
                ),
                /** @phpstan-ignore argument.type */
                new ArrayObject(
                    ['test_handler' => $this->binarydataConfigurationFactoryMock]
                )
            )
        );
    }

    /**
     * Tests that the default handlers are available when nothing is configured.
     */
    public function testDefaultHandlers(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.core.io.binarydata_handler.registry',
            'setHandlersMap',
            [['default' => 'ibexa.core.io.binarydata_handler.flysystem.default']]
        );

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall(
            'ibexa.core.io.metadata_handler.registry',
            'setHandlersMap',
            [['default' => 'ibexa.core.io.metadata_handler.flysystem.default']]
        );
    }

    public function testBinarydataHandler(): void
    {
        $this->container->setParameter(
            'ibexa.io.binarydata_handlers',
            ['my_handler' => ['name' => 'my_handler', 'type' => 'test_handler']]
        );

        $this->binarydataConfigurationFactoryMock
            ->expects(self::once())
            ->method('getParentServiceId')
            ->willReturn('test.io.binarydata_handler.test_handler');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'test.io.binarydata_handler.test_handler.my_handler',
            'test.io.binarydata_handler.test_handler'
        );
    }

    public function testMetadataHandler(): void
    {
        $this->container->setParameter(
            'ibexa.io.metadata_handlers',
            ['my_handler' => ['name' => 'my_handler', 'type' => 'test_handler']]
        );

        $this->metadataConfigurationFactoryMock
            ->expects(self::once())
            ->method('getParentServiceId')
            ->willReturn('test.io.metadata_handler.test_handler');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithParent(
            'test.io.metadata_handler.test_handler.my_handler',
            'test.io.metadata_handler.test_handler'
        );
    }

    public function testUnknownMetadataHandler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown handler');

        $this->container->setParameter(
            'ibexa.io.metadata_handlers',
            ['test' => ['type' => 'unknown']]
        );

        $this->compile();
    }

    public function testUnknownBinarydataHandler(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Unknown handler');

        $this->container->setParameter(
            'ibexa.io.binarydata_handlers',
            ['test' => ['type' => 'unknown']]
        );

        $this->compile();
    }
}
