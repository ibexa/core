<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\IO\DependencyInjection;

use Ibexa\Bundle\IO\DependencyInjection\ConfigurationFactory;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractContainerBuilderTestCase;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Abstract class for testing ConfigurationFactory implementations.
 *
 * The part about the container can rely on the matthiasnoback/SymfonyDependencyInjectionTest assertContainer* methods.
 */
abstract class ConfigurationFactoryTestCase extends AbstractContainerBuilderTestCase
{
    protected ConfigurationFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->provideTestedFactory();
    }

    public function testGetParentServiceId(): void
    {
        self::assertEquals(
            $this->provideExpectedParentServiceId(),
            $this->factory->getParentServiceId()
        );
    }

    public function testAddConfiguration(): void
    {
        $node = new ArrayNodeDefinition('handler');
        $this->factory->addConfiguration($node);
        self::assertNotEmpty($node->getChildNodeDefinitions());

        // @todo customized testing of configuration node ?
    }

    public function testConfigureHandler(): void
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
    private function registerHandler(string $name): string
    {
        $this->setDefinition($this->provideExpectedParentServiceId(), $this->provideParentServiceDefinition());
        $handlerServiceId = sprintf('%s.%s', $this->provideExpectedParentServiceId(), $name);
        $this->setDefinition($handlerServiceId, $this->provideParentServiceDefinition());

        return $handlerServiceId;
    }

    /**
     * Returns an instance of the tested factory.
     */
    abstract public function provideTestedFactory(): ConfigurationFactory;

    /**
     * Returns the expected parent service id.
     */
    abstract public function provideExpectedParentServiceId();

    /**
     * Provides the parent service definition, as defined in the bundle's services definition.
     * Required so that getArguments / replaceCalls work correctly.
     */
    abstract public function provideParentServiceDefinition(): Definition;

    /**
     * Provides the configuration array given to the handler, and initializes the container.
     * The name and type index are automatically set to respectively 'my_handler' and 'my_handler_test'.
     *
     * The method can also configure the container via $this->container.
     *
     * @return array<string, string>
     */
    abstract public function provideHandlerConfiguration(): array;

    /**
     * Lets you test the handler definition after it was configured.
     *
     * Use the assertContainer* methods from matthiasnoback/SymfonyDependencyInjectionTest.
     *
     * @param string $handlerServiceId id of the service that was registered by the compiler pass
     */
    abstract public function validateConfiguredHandler(string $handlerServiceId): void;
}
