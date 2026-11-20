<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\Compiler;

use ArrayObject;
use Ibexa\Bundle\IO\ApiLoader\HandlerRegistry;
use Ibexa\Bundle\IO\DependencyInjection\Compiler\IOConfigurationPass;
use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler\Flysystem as FlysystemConfigurationFactory;
use Ibexa\Core\IO\Flysystem\RangeReader\RangeReaderInterface;
use Ibexa\Core\IO\IOBinarydataHandler;
use Ibexa\Core\IO\RangeAwareIOBinarydataHandlerInterface;
use Ibexa\Core\IO\UrlDecorator;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Compiles a container with the real parent handler definition from io.yml,
 * so the named $rangeReader argument is verified after child definitions are resolved.
 */
final class RangeReaderWiringTest extends TestCase
{
    private const BINARYDATA_HANDLER_PARENT_ID = 'ibexa.core.io.binarydata_handler.flysystem';
    private const URL_DECORATOR_ID = 'ibexa.core.io.default_url_decorator';
    private const DEFAULT_BINARYDATA_HANDLER_ID = 'ibexa.core.io.binarydata_handler.flysystem.default';

    public function testConfiguredRangeReaderIsInjectedIntoBinarydataHandler(): void
    {
        $resource = fopen('php://memory', 'rb');
        $rangeReader = $this->createMock(RangeReaderInterface::class);
        $rangeReader
            ->expects(self::once())
            ->method('readStreamRange')
            ->with('my/file.mp4', 7, 3)
            ->willReturn($resource);

        $container = $this->buildContainer(['adapter' => 'test', 'range_reader' => 'app.range_reader']);
        $container->set('app.range_reader', $rangeReader);

        $handler = $this->getConfiguredHandler($container);

        self::assertInstanceOf(RangeAwareIOBinarydataHandlerInterface::class, $handler);
        self::assertSame($resource, $handler->getResourceRange('my/file.mp4', 7, 3));
    }

    public function testBinarydataHandlerWithoutRangeReaderReturnsNoRange(): void
    {
        $handler = $this->getConfiguredHandler($this->buildContainer(['adapter' => 'test']));

        self::assertInstanceOf(RangeAwareIOBinarydataHandlerInterface::class, $handler);
        self::assertNull($handler->getResourceRange('my/file.mp4', 7, 3));
    }

    /**
     * @param array<string, mixed> $handlerConfig
     */
    private function buildContainer(array $handlerConfig): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            self::BINARYDATA_HANDLER_PARENT_ID,
            $this->loadBundleServiceDefinition(self::BINARYDATA_HANDLER_PARENT_ID)
        );
        $container->setDefinition(self::URL_DECORATOR_ID, (new Definition(UrlDecorator::class))->setSynthetic(true));
        $container->setDefinition('app.range_reader', (new Definition(RangeReaderInterface::class))->setSynthetic(true));
        $container->setDefinition('oneup_flysystem.test_adapter', new Definition(InMemoryFilesystemAdapter::class));
        $container->setDefinition('ibexa.core.io.flysystem.base_filesystem', (new Definition(Filesystem::class))->setAbstract(true));
        $container->setDefinition(self::DEFAULT_BINARYDATA_HANDLER_ID, (new Definition(IOBinarydataHandler::class))->setSynthetic(true));
        $container->setDefinition('ibexa.core.io.metadata_handler.registry', new Definition(HandlerRegistry::class));
        $container->setDefinition('ibexa.core.io.binarydata_handler.registry', (new Definition(HandlerRegistry::class))->setPublic(true));
        $container->setParameter('ibexa.io.binarydata_handlers', [
            'my_handler' => ['name' => 'my_handler', 'type' => 'flysystem'] + $handlerConfig,
        ]);

        $configurationFactory = new FlysystemConfigurationFactory();
        $configurationFactory->setContainer($container);
        $container->addCompilerPass(
            new IOConfigurationPass(null, new ArrayObject(['flysystem' => $configurationFactory]))
        );
        $container->compile();

        $container->set(self::URL_DECORATOR_ID, $this->createStub(UrlDecorator::class));
        $container->set(self::DEFAULT_BINARYDATA_HANDLER_ID, $this->createStub(IOBinarydataHandler::class));

        return $container;
    }

    private function getConfiguredHandler(ContainerBuilder $container): object
    {
        $registry = $container->get('ibexa.core.io.binarydata_handler.registry');
        self::assertInstanceOf(HandlerRegistry::class, $registry);

        return $registry->getConfiguredHandler('my_handler');
    }

    private function loadBundleServiceDefinition(string $serviceId): Definition
    {
        $bundleServices = new ContainerBuilder();
        (new YamlFileLoader(
            $bundleServices,
            new FileLocator(__DIR__ . '/../../../../../src/bundle/IO/Resources/config')
        ))->load('io.yml');

        return $bundleServices->getDefinition($serviceId);
    }
}
