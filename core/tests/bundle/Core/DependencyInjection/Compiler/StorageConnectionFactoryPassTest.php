<?php

/**
 * @copyright Copyright (C) Ibexa AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace Ibexa\Tests\Bundle\Core\DependencyInjection\Compiler;

use Ibexa\Bundle\Core\ApiLoader\StorageConnectionFactory;
use Ibexa\Bundle\Core\DependencyInjection\Compiler\StorageConnectionFactoryPass;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @covers \Ibexa\Bundle\Core\DependencyInjection\Compiler\StorageConnectionFactoryPass
 */
final class StorageConnectionFactoryPassTest extends AbstractCompilerPassTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $doctrineConnections = ['default' => 'doctrine.dbal.default_connection'];

        $this->container->setParameter('doctrine.connections', $doctrineConnections);
        $this->setDefinition(
            StorageConnectionFactory::class,
            new Definition(
                StorageConnectionFactory::class,
                [
                    '$doctrineConnections' => $doctrineConnections,
                    '$serviceLocator' => null,
                ]
            )
        );
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new StorageConnectionFactoryPass());
    }

    public function testProcess(): void
    {
        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithServiceLocatorArgument(
            StorageConnectionFactory::class,
            '$serviceLocator',
            [
                'default' => new Reference('doctrine.dbal.default_connection'),
            ]
        );
    }
}
