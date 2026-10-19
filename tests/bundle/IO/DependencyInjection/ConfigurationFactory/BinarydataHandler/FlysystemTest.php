<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory\BinarydataHandler\Flysystem;
use Ibexa\Tests\Bundle\IO\DependencyInjection\ConfigurationFactory\BaseFlysystemTest;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class FlysystemTest extends BaseFlysystemTest
{
    public function provideTestedFactory(): Flysystem
    {
        return new Flysystem();
    }

    /**
     * Returns the expected parent service id.
     */
    public function provideExpectedParentServiceId()
    {
        return 'ibexa.core.io.binarydata_handler.flysystem';
    }

    public function testConfigureHandlerWithRangeReader(): void
    {
        $this->setDefinition('oneup_flysystem.test_adapter', new Definition());
        $this->setDefinition('my_handler', new Definition(null, [null]));

        $this->factory->configureHandler(
            $this->container->getDefinition('my_handler'),
            ['name' => 'my_handler', 'type' => 'flysystem', 'adapter' => 'test', 'range_reader' => 'app.range_reader']
        );

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'my_handler',
            '$rangeReader',
            new Reference('app.range_reader')
        );
    }

    public function testConfigureHandlerWithoutRangeReader(): void
    {
        $this->setDefinition('oneup_flysystem.test_adapter', new Definition());
        $this->setDefinition('my_handler', new Definition(null, [null]));

        $this->factory->configureHandler(
            $this->container->getDefinition('my_handler'),
            ['name' => 'my_handler', 'type' => 'flysystem', 'adapter' => 'test']
        );

        self::assertArrayNotHasKey('$rangeReader', $this->container->getDefinition('my_handler')->getArguments());
    }

    /**
     * @dataProvider provideForAddConfiguration
     *
     * @param array<string, mixed> $input
     */
    public function testAddConfigurationAcceptsRangeReader(
        array $input,
        ?string $expected
    ): void {
        self::assertSame($expected, $this->processConfiguration($input)['range_reader'] ?? null);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, ?string}>
     */
    public function provideForAddConfiguration(): iterable
    {
        yield 'not set' => [['adapter' => 'aws_s3'], null];
        yield 'null' => [['adapter' => 'aws_s3', 'range_reader' => null], null];
        yield 'service id' => [['adapter' => 'aws_s3', 'range_reader' => 'app.range_reader'], 'app.range_reader'];
    }

    /**
     * @dataProvider provideInvalidRangeReader
     *
     * @param mixed $rangeReader
     */
    public function testAddConfigurationRejectsInvalidRangeReader($rangeReader): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->processConfiguration(['adapter' => 'aws_s3', 'range_reader' => $rangeReader]);
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public function provideInvalidRangeReader(): iterable
    {
        yield 'empty string' => [''];
        yield 'true' => [true];
        yield 'false' => [false];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function processConfiguration(array $input): array
    {
        $treeBuilder = new TreeBuilder('handler');
        $this->factory->addConfiguration($treeBuilder->getRootNode());

        return (new Processor())->process($treeBuilder->buildTree(), [$input]);
    }
}

class_alias(FlysystemTest::class, 'eZ\Bundle\EzPublishIOBundle\Tests\DependencyInjection\ConfigurationFactory\BinarydataHandler\FlysystemTest');
