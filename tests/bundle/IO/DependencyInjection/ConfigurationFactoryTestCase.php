<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */

namespace Ibexa\Tests\Bundle\IO\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

/**
 * Abstract class for testing ConfigurationFactory implementations.
 *
 * The part about the container can rely on the matthiasnoback/SymfonyDependencyInjectionTest assertContainer* methods.
 */
abstract class ConfigurationFactoryTestCase extends AbstractContainerBuilderTestCase
{
    /** @var \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory */
    protected $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->provideTestedFactory();
    }

    public function testGetParentServiceId()
    {
        self::assertEquals(
            $this->provideExpectedParentServiceId(),
            $this->factory->getParentServiceId()
        );
    }

    public function testAddConfiguration()
    {
        $node = new ArrayNodeDefinition('handler');
        $this->factory->addConfiguration($node);
        self::assertInstanceOf(ArrayNodeDefinition::class, $node);

        // @todo customized testing of configuration node ?
    }

    public function testConfigureHandler()
    {
        $handlerConfiguration =
            $this->provideHandlerConfiguration() +
            ['name' => 'my_test_handler', 'type' => 'test_handler'];

        $handlerServiceId = $this->registerHandler($handlerConfiguration['name']);

        $this->factory->configureHandler($this->container, $this->container->getDefinition($handlerServiceId), $handlerConfiguration);

        $this->validateConfiguredHandler($handlerServiceId);
    }

    /**
     * Registers the handler in the container, like the pass would have done.
     */
    private function registerHandler($name): string
    {
        $this->setDefinition($this->provideExpectedParentServiceId(), $this->provideParentServiceDefinition());
        $handlerServiceId = sprintf('%s.%s', $this->provideExpectedParentServiceId(), $name);
        $this->setDefinition($handlerServiceId, $this->provideParentServiceDefinition());

        return $handlerServiceId;
    }

    /**
     * Returns an instance of the tested factory.
     *
     * @return \Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory
     */
    abstract public function provideTestedFactory();

    /**
     * Returns the expected parent service id.
     */
    abstract public function provideExpectedParentServiceId();

    /**
     * Provides the parent service definition, as defined in the bundle's services definition.
     * Required so that getArguments / replaceCalls work correctly.
     *
     * @return \Symfony\Component\DependencyInjection\Definition
     */
    abstract public function provideParentServiceDefinition();

    /**
     * Provides the configuration array given to the handler, and initializes the container.
     * The name and type index are automatically set to respectively 'my_handler' and 'my_handler_test'.
     *
     * The method can also configure the container via $this->container.
     *
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    abstract public function provideHandlerConfiguration();

    /**
     * Lets you test the handler definition after it was configured.
     *
     * Use the assertContainer* methods from matthiasnoback/SymfonyDependencyInjectionTest.
     *
     * @param string $handlerServiceId id of the service that was registered by the compiler pass
     */
    abstract public function validateConfiguredHandler($handlerServiceId);
}
